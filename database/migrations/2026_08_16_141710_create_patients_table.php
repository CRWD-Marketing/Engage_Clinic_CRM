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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            // The lead this patient was converted from - leads remain the
            // stable "child identity" used by Calendar/Therapist scheduling,
            // this table is purely the clinical/enrollment extension.
            $table->foreignId('lead_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('diagnosis')->nullable();
            $table->string('programme')->nullable(); // e.g. "ABA 20h/wk + Speech 2h"

            $table->string('insurance_provider')->nullable();
            $table->unsignedInteger('authorized_sessions_total')->nullable();
            $table->unsignedInteger('authorized_sessions_used')->default(0);
            $table->date('authorization_renews_at')->nullable();

            $table->dateTime('enrolled_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
