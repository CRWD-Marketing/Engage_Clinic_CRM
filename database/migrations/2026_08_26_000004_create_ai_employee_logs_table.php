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
        Schema::create('ai_employee_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inbound_message_id')->nullable()->constrained('whatsapp_messages')->nullOnDelete();
            $table->foreignId('outbound_message_id')->nullable()->constrained('whatsapp_messages')->nullOnDelete();
            // Ordered list of knowledge_base_entries ids used to build the reply,
            // e.g. [4, 11, 12]. Kept as JSON rather than a pivot table - nothing
            // needs "all AI responses that used entry X" at this scale.
            $table->json('knowledge_base_entry_ids')->nullable();
            $table->boolean('escalated')->default(false);
            $table->string('status'); // success | escalated | error
            $table->text('error_message')->nullable();
            $table->unsignedInteger('llm_latency_ms')->nullable();
            $table->timestamps();

            $table->index('whatsapp_contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_employee_logs');
    }
};
