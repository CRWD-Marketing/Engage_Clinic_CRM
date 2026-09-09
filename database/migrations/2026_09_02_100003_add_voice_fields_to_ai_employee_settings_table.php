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
        Schema::table('ai_employee_settings', function (Blueprint $table) {
            $table->text('voice_greeting')->nullable()->after('system_prompt_override');
            $table->string('human_handoff_phone_number')->nullable()->after('voice_greeting');
            // Separate from is_enabled on purpose: a bad live call is a worse
            // failure mode than a bad chat reply, so voice must be independently
            // toggleable rather than sharing chat's kill switch.
            $table->boolean('voice_enabled')->default(false)->after('human_handoff_phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_employee_settings', function (Blueprint $table) {
            $table->dropColumn(['voice_greeting', 'human_handoff_phone_number', 'voice_enabled']);
        });
    }
};
