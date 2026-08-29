<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_employee_settings', function (Blueprint $table) {
            $table->id();
            // Global kill switch. Starts false on purpose: the feature must be
            // deliberately turned on by an admin after confirming a queue worker
            // is actually running - see the AI Employee plan's operational note.
            $table->boolean('is_enabled')->default(false);
            $table->string('model_name')->nullable(); // overrides config('ai_employee.llm.model') when set
            $table->unsignedTinyInteger('max_history_messages')->default(10);
            $table->unsignedTinyInteger('max_kb_entries')->default(5);
            // Delay applied fresh to EVERY inbound message before the AI
            // responds to it - each message independently waits this many
            // seconds, not a one-time delay for the whole conversation. Also
            // naturally prevents a burst of quick customer messages from each
            // getting their own immediate back-to-back AI reply.
            $table->unsignedSmallInteger('response_delay_seconds')->default(60);
            $table->text('system_prompt_override')->nullable();
            $table->timestamps();
        });

        // Single-row settings table - seed the one row now so
        // AiEmployeeSettings::current() never has to handle a missing row.
        DB::table('ai_employee_settings')->insert([
            'id' => 1,
            'is_enabled' => false,
            'max_history_messages' => 10,
            'max_kb_entries' => 5,
            'response_delay_seconds' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_employee_settings');
    }
};
