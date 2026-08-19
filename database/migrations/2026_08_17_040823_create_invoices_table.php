<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // e.g. "INV-2026-0412"
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();

            $table->string('bill_to')->nullable(); // parent/guardian name, snapshot at issue time
            $table->string('payer'); // Daman, Daman Enhanced, Thiqa, ADNIC, Self-pay...
            $table->unsignedTinyInteger('coverage_percent')->default(0);
            $table->date('period'); // first-of-month the invoice covers
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('claim_reference')->nullable(); // e.g. "CLM-2090", null for self-pay

            $table->string('status')->default('draft'); // draft | submitted | pending_info | paid | rejected

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('insurance_coverage_amount', 10, 2)->default(0);
            $table->decimal('patient_responsibility', 10, 2)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
