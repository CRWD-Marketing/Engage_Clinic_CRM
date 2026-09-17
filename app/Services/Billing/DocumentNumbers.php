<?php

namespace App\Services\Billing;

use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PreAuthorization;
use Illuminate\Support\Facades\DB;

/**
 * Sequential document numbers. Each allocator reads the current maximum
 * under a row lock, so two saves in flight can't take the same number -
 * call these inside the transaction that persists the document.
 */
class DocumentNumbers
{
    public static function nextInvoice(): string
    {
        $year = now()->format('Y');
        $max = (int) DB::table('invoices')
            ->where('invoice_number', 'like', "INV-{$year}-%")
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) AS m")
            ->value('m');

        return sprintf('INV-%s-%04d', $year, $max + 1);
    }

    /**
     * Read-only peek for the preview - the number actually issued is
     * allocated again inside the save transaction and may differ if someone
     * else saves first.
     */
    public static function peekInvoice(): string
    {
        $year = now()->format('Y');
        $max = (int) DB::table('invoices')
            ->where('invoice_number', 'like', "INV-{$year}-%")
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) AS m")
            ->value('m');

        return sprintf('INV-%s-%04d', $year, $max + 1);
    }

    public static function nextReceipt(): string
    {
        $max = (int) DB::table('payments')
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(receipt_number, '-', -1) AS UNSIGNED)) AS m")
            ->value('m');

        return sprintf('RCT-%03d', $max + 1);
    }

    public static function nextClaim(): string
    {
        $max = (int) DB::table('insurance_claims')
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(reference, '-', -1) AS UNSIGNED)) AS m")
            ->value('m');

        return 'CLM-'.max($max + 1, 2001);
    }

    public static function nextPreAuth(): string
    {
        $year = now()->format('Y');
        $max = (int) DB::table('pre_authorizations')
            ->where('reference', 'like', "PA-{$year}-%")
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(reference, '-', -1) AS UNSIGNED)) AS m")
            ->value('m');

        return sprintf('PA-%s-%d', $year, max($max + 1, 82500));
    }

    public static function nextRun(): string
    {
        $year = now()->format('Y');
        $max = (int) DB::table('invoices')
            ->where('batch_reference', 'like', "RUN-{$year}-%")
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(batch_reference, '-', -1) AS UNSIGNED)) AS m")
            ->value('m');

        return sprintf('RUN-%s-%02d', $year, $max + 1);
    }
}
