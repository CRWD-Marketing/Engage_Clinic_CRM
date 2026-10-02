<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Billing\InvoiceController as WebInvoiceController;
use App\Models\Patient;

/**
 * Invoices for the mobile app. Everything here is the web controller's own
 * action (ledger, preview, issue, payments, credit, void, email, top-up all
 * answer JSON already); the family statement is the one the web renders as
 * a page, so it is returned as data here.
 */
class InvoiceController extends WebInvoiceController
{
    /** GET /billing/patients/{patient}/statement — billing/statement.blade.php's data. */
    public function statementJson(Patient $patient)
    {
        $d = $this->statementData($patient);
        $oldest = $d['oldest'];

        return response()->json([
            'patient_id' => $patient->id,
            'child' => $d['profile']['child'],
            'bill_to' => $d['profile']['bill_to'],
            'payer' => $d['profile']['primary_payer'],
            'as_of' => $d['asOf']->format('d M Y'),
            'charged' => round((float) $d['charged'], 2),
            'credited' => round((float) $d['credited'], 2),
            'balance' => round((float) $d['balance'], 2),
            // The oldest invoice still open, for the "due since" line.
            'oldest_open' => $oldest ? [
                'number' => $oldest->invoice_number,
                'due_label' => $oldest->due_date?->format('d M Y'),
                'days_past_due' => $oldest->daysPastDue(),
            ] : null,
            'rows' => $d['rows']->map(fn (array $row) => [
                'date_label' => $row['date_label'],
                'ref' => $row['ref'],
                'desc' => $row['desc'],
                'charge' => round((float) $row['charge'], 2),
                'credit' => round((float) $row['credit'], 2),
                'balance' => round((float) $row['balance'], 2),
            ])->values(),
            'sessions' => $d['sessions']->values(),
            'session_hours' => (int) $d['sessionHours'],
            'session_charged' => round((float) $d['sessionCharged'], 2),
        ]);
    }
}
