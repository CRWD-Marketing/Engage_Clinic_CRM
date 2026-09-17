<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Billing rebuilt as a view over the session ledger: invoices freeze
     * per-hour lines derived from calendar sessions (VAT, per-line payer
     * routing), receipts/credit notes/void-reissue live alongside, and
     * claims + pre-authorizations get their own registers.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('vat_amount', 10, 2)->default(0)->after('subtotal');
            $table->decimal('total', 10, 2)->default(0)->after('vat_amount');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('total');
            $table->json('payer_splits')->nullable()->after('patient_responsibility');
            $table->date('period_from')->nullable()->after('period');
            $table->date('period_to')->nullable()->after('period_from');
            $table->decimal('credit_amount', 10, 2)->default(0)->after('amount_paid');
            $table->text('credit_reason')->nullable()->after('credit_amount');
            $table->timestamp('voided_at')->nullable()->after('credit_reason');
            $table->string('void_reason')->nullable()->after('voided_at');
            $table->foreignId('replaces_invoice_id')->nullable()->after('void_reason')->constrained('invoices')->nullOnDelete();
            $table->foreignId('replaced_by_invoice_id')->nullable()->after('replaces_invoice_id')->constrained('invoices')->nullOnDelete();
            $table->string('sent_to')->nullable()->after('replaced_by_invoice_id');
            $table->timestamp('sent_at')->nullable()->after('sent_to');
            $table->timestamp('reminder_sent_at')->nullable()->after('sent_at');
            $table->unsignedSmallInteger('reminders_count')->default(0)->after('reminder_sent_at');
            $table->string('batch_reference', 30)->nullable()->index()->after('reminders_count');
        });

        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->change();
            $table->foreignId('therapist_id')->nullable()->change();
            $table->unsignedSmallInteger('line_no')->default(1)->after('invoice_id');
            $table->foreignId('calendar_session_id')->nullable()->after('line_no')->constrained('calendar_sessions')->nullOnDelete();
            $table->dateTime('session_from')->nullable()->after('description');
            $table->dateTime('session_to')->nullable()->after('session_from');
            $table->string('setting', 40)->nullable()->after('session_to');
            $table->string('note')->nullable()->after('setting');
            $table->string('payer', 100)->nullable()->after('note');
            $table->unsignedTinyInteger('coverage_percent')->default(0)->after('payer');
            $table->decimal('qty', 6, 2)->default(1)->after('coverage_percent');
            $table->decimal('vat_amount', 10, 2)->default(0)->after('amount');
            $table->decimal('total', 10, 2)->default(0)->after('vat_amount');
            $table->decimal('insurer_amount', 10, 2)->default(0)->after('total');
            $table->decimal('family_amount', 10, 2)->default(0)->after('insurer_amount');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number', 20)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('method', 40);
            $table->date('received_on');
            $table->string('reference')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('insurer', 100);
            $table->decimal('amount', 10, 2);
            $table->string('period_label', 40)->nullable();
            $table->string('status', 20)->default('submitted');
            $table->date('submitted_on');
            $table->date('settled_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pre_authorizations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('payer', 100);
            $table->string('service', 100);
            $table->unsignedSmallInteger('hours');
            $table->date('valid_from');
            $table->date('valid_to');
            $table->string('status', 20)->default('requested');
            $table->string('payer_reference')->nullable();
            $table->text('justification')->nullable();
            $table->text('denial_reason')->nullable();
            $table->date('submitted_on');
            $table->foreignId('resubmitted_from_id')->nullable()->constrained('pre_authorizations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('calendar_sessions', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('supervision_notes')->constrained('invoices')->nullOnDelete();
            $table->decimal('cancel_notice_hours', 6, 1)->nullable()->after('cancel_reason');
            $table->timestamp('cancelled_at')->nullable()->after('cancel_notice_hours');
        });

        // Legacy invoices (typed, no VAT): carry subtotal into total so balances
        // compute, turn their amount_paid into a receipt, and register their
        // claim reference in the new claims table.
        $receipt = 0;
        foreach (DB::table('invoices')->orderBy('id')->get() as $inv) {
            DB::table('invoices')->where('id', $inv->id)->update([
                'total' => $inv->subtotal,
                'period_from' => $inv->period,
                'period_to' => $inv->period ? date('Y-m-t', strtotime($inv->period)) : null,
            ]);

            if ((float) $inv->amount_paid > 0) {
                $receipt++;
                DB::table('payments')->insert([
                    'invoice_id' => $inv->id,
                    'receipt_number' => 'RCT-'.str_pad((string) $receipt, 3, '0', STR_PAD_LEFT),
                    'amount' => $inv->amount_paid,
                    'method' => $inv->payment_method ?: 'Bank transfer',
                    'received_on' => $inv->issue_date ?: now()->toDateString(),
                    'reference' => null,
                    'recorded_by' => $inv->created_by,
                    'created_at' => $inv->updated_at ?? now(),
                    'updated_at' => $inv->updated_at ?? now(),
                ]);
            }

            if ($inv->claim_reference && (float) $inv->insurance_coverage_amount > 0 && strcasecmp((string) $inv->payer, 'Self-pay') !== 0) {
                DB::table('insurance_claims')->insert([
                    'reference' => $inv->claim_reference,
                    'invoice_id' => $inv->id,
                    'patient_id' => $inv->patient_id,
                    'insurer' => $inv->payer,
                    'amount' => $inv->insurance_coverage_amount,
                    'period_label' => $inv->period ? date('M Y', strtotime($inv->period)) : null,
                    'status' => in_array($inv->status, ['submitted', 'pending_info', 'rejected'], true) ? $inv->status : ($inv->status === 'paid' ? 'settled' : 'draft'),
                    'submitted_on' => $inv->issue_date ?: now()->toDateString(),
                    'settled_on' => $inv->status === 'paid' ? ($inv->updated_at ? date('Y-m-d', strtotime($inv->updated_at)) : null) : null,
                    'created_at' => $inv->created_at ?? now(),
                    'updated_at' => $inv->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('calendar_sessions', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['invoice_id', 'cancel_notice_hours', 'cancelled_at']);
        });
        Schema::dropIfExists('pre_authorizations');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('payments');
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropForeign(['calendar_session_id']);
            $table->dropColumn(['line_no', 'calendar_session_id', 'session_from', 'session_to', 'setting', 'note', 'payer', 'coverage_percent', 'qty', 'vat_amount', 'total', 'insurer_amount', 'family_amount']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['replaces_invoice_id']);
            $table->dropForeign(['replaced_by_invoice_id']);
            $table->dropColumn(['vat_amount', 'total', 'hourly_rate', 'payer_splits', 'period_from', 'period_to', 'credit_amount', 'credit_reason', 'voided_at', 'void_reason', 'replaces_invoice_id', 'replaced_by_invoice_id', 'sent_to', 'sent_at', 'reminder_sent_at', 'reminders_count', 'batch_reference']);
        });
    }
};
