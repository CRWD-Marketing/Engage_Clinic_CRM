<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_sessions', function (Blueprint $table) {
            $table->id();

            // Assigned therapist / staff
            $table->foreignId('therapist_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Links to the Lead (acts as the "patient" record)
            $table->foreignId('patient_id')
                ->nullable()
                ->constrained('leads')
                ->nullOnDelete();

            $table->enum('activity_type', [
                'ABA',
                'Speech',
                'OT',
                'Assessment',
                'Supervision',
                'Parent training',
            ]);

            $table->date('session_date');
            $table->time('start_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->time('end_time');

            $table->string('room')->nullable();

            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])
                ->default('scheduled');

            // Tracks whether a coordinator has followed up on a no-show; null means still pending.
            $table->timestamp('follow_up_completed_at')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['therapist_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_sessions');
    }
};