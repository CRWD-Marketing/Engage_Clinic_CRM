<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Finance Flow SOP stages 1-3: the quotation that precedes every
     * engagement (location, payment mode, service, agreed rate and quantity,
     * terms, validity), the customer's confirmation of it, and the payment
     * confirmation that clears the client for session scheduling. A
     * prepayment received against a quotation is drawn down by the invoices
     * raised later, so those invoices arrive already settled.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number', 20)->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name')->nullable(); // parent/guardian, snapshot
            $table->string('location', 100);
            $table->string('payment_mode', 20); // Insurance | Self pay
            $table->string('payer', 100)->nullable(); // insurer, when payment_mode is Insurance
            $table->string('service', 100);
            $table->string('pricing_basis', 40); // Per hour | Per session | Package | Insurance approved rate
            $table->string('billing_unit', 20)->default('hour'); // hour | session
            $table->decimal('quantity', 8, 2);
            $table->decimal('rate', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('payment_terms')->nullable();
            $table->date('valid_until');
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('sent_at')->nullable();
            $table->string('sent_via', 20)->nullable(); // WhatsApp | Email
            $table->timestamp('confirmed_at')->nullable();
            $table->string('confirmation_method', 20)->nullable(); // Written | Verbal
            $table->boolean('prepayment_policy_received')->default(false);
            $table->boolean('pos_agreement_received')->default(false);
            $table->boolean('noc_received')->default(false);
            $table->boolean('liability_acknowledged')->default(false);
            $table->string('payment_method', 40)->nullable();
            $table->decimal('amount_received', 10, 2)->default(0);
            $table->decimal('pos_fee_amount', 10, 2)->default(0); // 2% + VAT on card, shown separately
            $table->string('payment_reference')->nullable();
            $table->date('payment_received_on')->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->foreignId('payment_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quotation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 40);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('quotation_id')->nullable()->after('invoice_id')->constrained('quotations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['quotation_id']);
            $table->dropColumn('quotation_id');
        });
        Schema::dropIfExists('quotation_events');
        Schema::dropIfExists('quotations');
    }
};
