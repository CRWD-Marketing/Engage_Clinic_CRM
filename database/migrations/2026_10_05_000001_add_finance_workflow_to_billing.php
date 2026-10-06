<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Finance Flow SOP (sessions to invoicing): an invoice now moves Draft →
     * Pending Finance verification → Approved → Finalized → Dispatched instead
     * of being issued the moment it's saved; every dispatch (email or
     * WhatsApp) and every step of that chain is kept as its own record; and an
     * insurance claim tracks the portal submission itself (portal, insurer's
     * claim reference, supporting documents) rather than assuming it happened
     * at issue.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('workflow_status', 30)->default('draft')->index()->after('status');
            $table->timestamp('submitted_at')->nullable()->after('workflow_status');
            $table->foreignId('submitted_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('submitted_by');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('correction_note')->nullable()->after('approved_by');
            $table->timestamp('finalized_at')->nullable()->after('correction_note');
            $table->foreignId('finalized_by')->nullable()->after('finalized_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('invoice_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 40);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20); // email | whatsapp
            $table->string('kind', 20)->default('invoice'); // invoice | reminder
            $table->string('sent_to');
            $table->timestamp('sent_at');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('receipt_confirmed_at')->nullable();
            $table->foreignId('receipt_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->date('submitted_on')->nullable()->change();
            $table->string('portal', 60)->nullable()->after('insurer');
            $table->string('payer_reference', 60)->nullable()->after('reference'); // insurer's own, e.g. CL-ON-000-0000XXX
            $table->date('service_date')->nullable()->after('period_label');
            $table->foreignId('medical_report_document_id')->nullable()->after('notes')->constrained('patient_documents')->nullOnDelete();
            $table->foreignId('assessment_report_document_id')->nullable()->after('medical_report_document_id')->constrained('patient_documents')->nullOnDelete();
        });

        // Everything raised before this workflow existed was issued straight
        // away - it has already left the building, so it lands at the end of
        // the chain rather than back in a Draft queue.
        foreach (DB::table('invoices')->orderBy('id')->get() as $inv) {
            DB::table('invoices')->where('id', $inv->id)->update([
                'workflow_status' => $inv->sent_at ? 'dispatched' : 'finalized',
                'finalized_at' => $inv->created_at,
                'finalized_by' => $inv->created_by,
            ]);
            if ($inv->sent_at && $inv->sent_to) {
                DB::table('invoice_dispatches')->insert([
                    'invoice_id' => $inv->id,
                    'channel' => 'email',
                    'kind' => 'invoice',
                    'sent_to' => $inv->sent_to,
                    'sent_at' => $inv->sent_at,
                    'created_at' => $inv->sent_at,
                    'updated_at' => $inv->sent_at,
                ]);
            }
        }

        // Verification is Finance's step: grant it wherever Finance / Full
        // Admin access is already held, on the templates and on any user
        // whose actions were copied from them.
        $grant = function (?string $json): ?string {
            $actions = json_decode((string) $json, true);
            if (! is_array($actions) || in_array('approve_invoice', $actions, true)) {
                return null;
            }
            $actions[] = 'approve_invoice';

            return json_encode(array_values($actions));
        };

        if (Schema::hasTable('role_templates')) {
            foreach (DB::table('role_templates')->whereIn('base_role', ['FULL_ADMIN', 'FINANCE_STAFF'])->get() as $t) {
                if ($new = $grant($t->actions)) {
                    DB::table('role_templates')->where('id', $t->id)->update(['actions' => $new]);
                }
            }
        }
        if (Schema::hasColumn('users', 'actions')) {
            foreach (DB::table('users')->whereIn('role', ['FULL_ADMIN', 'FINANCE_STAFF'])->whereNotNull('actions')->get() as $u) {
                if ($new = $grant($u->actions)) {
                    DB::table('users')->where('id', $u->id)->update(['actions' => $new]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->dropForeign(['medical_report_document_id']);
            $table->dropForeign(['assessment_report_document_id']);
            $table->dropColumn(['portal', 'payer_reference', 'service_date', 'medical_report_document_id', 'assessment_report_document_id']);
        });
        Schema::dropIfExists('invoice_dispatches');
        Schema::dropIfExists('invoice_events');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['finalized_by']);
            $table->dropColumn(['workflow_status', 'submitted_at', 'submitted_by', 'approved_at', 'approved_by', 'correction_note', 'finalized_at', 'finalized_by']);
        });
    }
};
