<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Billing\BillingController as WebBillingController;

/**
 * Billing & insurance for the mobile app. The web page is one Blade view fed
 * with ready-made arrays (invoices, claims, aging, pickers); this returns
 * that same data as JSON. Every billing action - payments, credit notes,
 * void, email, claims - is the web controllers' own (they already answer
 * JSON) and is routed directly in routes/api/modules.php.
 */
class BillingController extends WebBillingController
{
    /** GET /billing — everything billing/index.blade.php is rendered with. */
    public function index()
    {
        $d = parent::index()->getData();

        return response()->json([
            'can_invoice' => $d['canInvoice'],
            'month_label' => $d['monthLabel'],
            'payer_summary' => $d['payerSummary'],
            'tiles' => $d['tiles'],
            'invoices' => $d['invoicesForJs'],
            'revenue_by_payer' => $d['revenueByPayer'],
            'claims' => $d['claimsForJs'],
            'claim_aging' => $d['claimAging'],
            'claim_statuses' => $d['claimStatuses'],
            'rejected_alert' => $d['rejectedAlert'],
            'pre_auths' => $d['preAuthsForJs'],
            'aging' => $d['aging'],
            'patients' => $d['patientsForJs'],
            'payers' => $d['payers'],
            'services' => $d['services'],
            'methods' => $d['methods'],
            'cancel_policy' => $d['cancelPolicy'],
            'bulk_defaults' => $d['bulkDefaults'],
            'clinic_name' => $d['clinic']['name'] ?? config('clinic.name'),
        ]);
    }
}
