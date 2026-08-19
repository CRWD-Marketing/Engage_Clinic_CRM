<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    /**
     * Create an invoice with its line items, computing totals server-side so
     * the stored amounts can't drift from what the line items actually add up to.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'bill_to' => 'nullable|string|max:255',
            'payer' => 'required|string|max:100',
            'coverage_percent' => 'required|integer|min:0|max:100',
            'period' => 'required|date_format:Y-m',
            'line_items' => 'required|array|min:1',
            'line_items.*.service_id' => 'required|exists:services,id',
            'line_items.*.therapist_id' => 'required|exists:users,id',
            'line_items.*.sessions' => 'required|integer|min:1',
            'line_items.*.rate' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $services = Service::whereIn('id', collect($data['line_items'])->pluck('service_id'))->get()->keyBy('id');

        $invoice = DB::transaction(function () use ($data, $services, $request) {
            $subtotal = 0;
            $lineItems = [];

            foreach ($data['line_items'] as $item) {
                $service = $services->get($item['service_id']);
                $amount = round($item['sessions'] * $item['rate'], 2);
                $subtotal += $amount;

                $lineItems[] = [
                    'service_id' => $service?->id,
                    'therapist_id' => $item['therapist_id'],
                    'description' => $service?->name ?? 'Service',
                    'cpt_code' => $service?->cpt_code,
                    'sessions' => $item['sessions'],
                    'rate' => $item['rate'],
                    'amount' => $amount,
                ];
            }

            $isSelfPay = $data['payer'] === 'Self-pay';
            $coveragePercent = $isSelfPay ? 0 : $data['coverage_percent'];
            $coverageAmount = round($subtotal * $coveragePercent / 100, 2);
            $patientResponsibility = round($subtotal - $coverageAmount, 2);

            $issueDate = Carbon::today();

            $invoice = Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'patient_id' => $data['patient_id'],
                'bill_to' => $data['bill_to'] ?: null,
                'payer' => $data['payer'],
                'coverage_percent' => $coveragePercent,
                'period' => Carbon::createFromFormat('Y-m', $data['period'])->startOfMonth(),
                'issue_date' => $issueDate,
                'due_date' => $issueDate->copy()->addDays(14),
                'claim_reference' => $isSelfPay ? null : $this->nextClaimReference(),
                'status' => 'draft',
                'subtotal' => $subtotal,
                'insurance_coverage_amount' => $coverageAmount,
                'patient_responsibility' => $patientResponsibility,
                'created_by' => $request->user()?->id,
            ]);

            $invoice->lineItems()->createMany($lineItems);

            return $invoice;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'invoice' => $invoice->load('lineItems', 'patient.lead'),
                'print_url' => route('billing.invoices.show', $invoice),
            ], 201);
        }

        return redirect()->route('billing.index')->with('success', 'Invoice created.');
    }

    /**
     * Printable invoice template.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['patient.lead', 'lineItems.therapist', 'creator']);

        return view('billing.print', compact('invoice'));
    }

    /**
     * Inline status change from the claims table (Submitted / Pending info / Paid / Rejected).
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', Invoice::STATUSES),
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $invoice->update(['status' => $request->status]);

        return response()->json(['success' => true, 'invoice' => $invoice]);
    }

    private function nextInvoiceNumber(): string
    {
        $year = now()->year;
        $count = Invoice::whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-%d-%04d', $year, $count);
    }

    private function nextClaimReference(): string
    {
        $count = Invoice::whereNotNull('claim_reference')->count() + 1;

        return sprintf('CLM-%04d', 2000 + $count);
    }
}
