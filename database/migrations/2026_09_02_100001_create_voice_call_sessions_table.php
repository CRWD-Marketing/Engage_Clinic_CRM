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
        Schema::create('voice_call_sessions', function (Blueprint $table) {
            $table->id();
            // Every voice call gets its own WhatsappContact row (channel='voice',
            // wa_id="voice:{E.164 number}") rather than reusing a chat contact
            // with the same phone number - see VoiceBridgeController::startCall().
            $table->foreignId('whatsapp_contact_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('twilio');
            $table->string('provider_call_sid')->unique(); // Twilio's CallSid - guards against a retried call-start being processed twice
            $table->string('from_number');
            $table->string('to_number');
            $table->string('status')->default('in_progress'); // in_progress | completed | escalated | failed | no_answer
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('recording_url')->nullable();
            $table->boolean('escalated')->default(false);
            $table->string('escalation_reason')->nullable();
            $table->boolean('transferred_to_human')->default(false);
            $table->json('summary')->nullable(); // {intent, sentiment, outcome, follow_up_required, notes} - filled by GenerateVoiceCallSummary
            $table->string('summary_status')->default('pending'); // pending | processing | completed | failed
            $table->timestamps();

            $table->index('status');
            $table->index('escalated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_call_sessions');
    }
};
