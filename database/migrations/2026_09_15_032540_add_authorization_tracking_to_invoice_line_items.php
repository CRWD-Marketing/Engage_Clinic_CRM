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
        Schema::table('invoice_line_items', function (Blueprint $table) {
            // Which specific authorization this hour drew against, if any -
            // durable record instead of re-deriving it from covers_services
            // matching after the fact. Null means self-pay / no authorization.
            $table->foreignId('patient_authorization_id')->nullable()->after('service_id')
                ->constrained()->nullOnDelete();

            // True when this hour was billed after its authorization's
            // remaining balance (or expiry) was already exhausted, so it was
            // charged to the family instead of the insurer.
            $table->boolean('exceeds_authorization')->default(false)->after('coverage_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patient_authorization_id');
            $table->dropColumn('exceeds_authorization');
        });
    }
};
