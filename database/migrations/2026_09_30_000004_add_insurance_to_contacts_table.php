<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The family's health insurance, picked on the website's contact form and
 * Free Consultation booking. Plain text like leads.insurance (the list comes
 * from the active Insurances in Billing) so it carries over on convert.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('insurance', 50)->nullable()->after('interested_in');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('insurance');
        });
    }
};
