<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Patient;
use App\Services\Billing\DocumentNumbers;
use App\Services\Billing\InvoiceBuilder;
use App\Services\Billing\InvoicePresenter;
use App\Services\Billing\SessionLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Month-end: every unbilled delivered session in a period, grouped by family,
 * one invoice per family in a single operation.
 */
class BulkRunController extends Controller
{
    public function __construct(private SessionLedger $ledger, private InvoiceBuilder $builder)
    {
    }

    public function preview(Request $request)
    {
        return response()->json(['groups' => $this->groups($request->input('from'), $request->input('to'), $request->input('payer'))]);
    }

    public function issue(Request $request)
    {
        abort_unless(auth()->user()->canDo('create_invoice'), 403, 'Invoices are raised by Finance.');

        $validator = Validator::make($request->all(), [
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'payer' => ['nullable', 'string'],
            'patient_ids' => ['required', 'array', 'min:1'],
            'patient_ids.*' => ['integer', 'exists:patients,id'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $groups = collect($this->groups($request->from, $request->to, $request->payer))
            ->whereIn('patient_id', array_map('intval', $request->patient_ids));

        if ($groups->isEmpty()) {
            return response()->json(['message' => 'Nothing to invoice — every session in this period has already been billed.'], 422);
        }

        $issued = DB::transaction(function () use ($groups) {
            $run = DocumentNumbers::nextRun();
            $out = [];
            foreach ($groups as $g) {
                $patient = Patient::with(['lead', 'authorizations'])->find($g['patient_id']);
                $composed = $this->builder->compose($patient, collect($g['rows']));
                if ($composed['lines']) {
                    $out[] = InvoicePresenter::row($this->builder->issue($patient, $composed, $run, auth()->id()));
                }
            }

            return ['run' => $run, 'invoices' => $out];
        });

        $count = count($issued['invoices']);

        if ($count) {
            Activity::log(
                'bulk_run',
                "Bulk run {$issued['run']} — {$count} invoice".($count === 1 ? '' : 's')." raised for ".Carbon::parse($request->from)->format('d M Y').' → '.Carbon::parse($request->to)->format('d M Y'),
                route('billing.index')
            );
        }

        return response()->json([
            'message' => "{$issued['run']}: {$count} invoice".($count === 1 ? '' : 's').' raised.',
            'run' => $issued['run'],
            'invoices' => $issued['invoices'],
        ], 201);
    }

    /**
     * One reviewable row per family: sessions, hours, net, VAT, total, payer
     * split and how many sessions the cancellation policy adjusted.
     */
    private function groups(?string $from, ?string $to, ?string $payer): array
    {
        $from = $from ?: now()->startOfMonth()->toDateString();
        $to = $to ?: now()->toDateString();
        $payer = $payer && $payer !== 'all' ? $payer : null;

        return Patient::with(['lead', 'authorizations'])->get()->map(function (Patient $p) use ($from, $to, $payer) {
            $profile = $this->ledger->profile($p);
            $rows = $this->ledger->forPatient($p, $from, $to)
                ->reject(fn ($r) => $r['invoiced'] || $r['bill_hours'] <= 0);
            if ($payer) {
                $rows = $rows->filter(fn ($r) => strcasecmp($r['payer'], $payer) === 0);
            }
            if ($rows->isEmpty()) {
                return null;
            }
            $c = $this->builder->compose($p, $rows, $profile);

            return [
                'patient_id' => $p->id,
                'patient' => $profile['child'],
                'parent' => $profile['bill_to'],
                'payer' => $c['payer'],
                'sessions' => $c['session_count'],
                'hours' => $c['bill_hours'],
                'net' => $c['net'],
                'vat' => $c['vat'],
                'total' => $c['total'],
                'insurer_share' => $c['insurer_share'],
                'family_share' => $c['family_share'],
                'adjusted' => $rows->filter(fn ($r) => $r['attendance'] !== 'completed')->count(),
                'rows' => $rows->values()->all(),
            ];
        })->filter()->sortByDesc('total')->values()->all();
    }
}
