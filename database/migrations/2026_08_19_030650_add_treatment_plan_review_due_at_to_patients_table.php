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
        Schema::table('patients', function (Blueprint $table) {
            // Standard ABA/clinical practice is a periodic (often 90-day) treatment
            // plan review - this is when the next one is due.
            $table->date('treatment_plan_review_due_at')->nullable()->after('programme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('treatment_plan_review_due_at');
        });
    }
};
