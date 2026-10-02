<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vendor registrations submitted from the public website
     * (/vendor-registration) and managed in CRM -> Vendors.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable()->unique(); // Vendor ID, e.g. ENG-VND-2026-0001

            // Company details
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('website')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('emirate');

            // Contact person
            $table->string('contact_name');
            $table->string('position');
            $table->string('phone');
            $table->string('email');
            $table->string('alt_contact')->nullable();
            $table->string('finance_email')->nullable();

            // Category & services
            $table->string('category');
            $table->string('category_other')->nullable();
            $table->string('primary_service');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('years_in_business')->nullable();
            $table->string('referred_by')->nullable();

            // Commercial terms (as proposed by the vendor)
            $table->string('pricing');
            $table->decimal('monthly_cost', 12, 2)->nullable();
            $table->string('payment_terms');
            $table->string('payment_method');
            $table->boolean('vat_registered')->default(false);
            $table->string('trn', 15)->nullable();
            $table->boolean('accepts_po')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();

            // Bank details - iban is stored encrypted (see Vendor::$casts)
            $table->string('bank_name');
            $table->string('account_holder');
            $table->text('iban');
            $table->string('swift', 11)->nullable();

            // Declaration
            $table->string('signatory_name');
            $table->timestamp('declared_at')->nullable();

            // CRM management
            $table->string('status')->default('under_review'); // prospect, under_review, active, on_hold, suspended, terminated
            $table->boolean('is_critical')->default(false);
            $table->foreignId('internal_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('viewed_at')->nullable(); // null = not opened by staff yet
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('type'); // trade_licence, vat_certificate, insurance, professional_licence, agreement, other, bank_letter
            $table->string('document_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path');
            $table->string('file_original_name');
            $table->string('file_mime')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
        Schema::dropIfExists('vendors');
    }
};
