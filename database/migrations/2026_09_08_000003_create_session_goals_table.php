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
        Schema::create('session_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_goal_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['calendar_session_id', 'patient_goal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_goals');
    }
};
