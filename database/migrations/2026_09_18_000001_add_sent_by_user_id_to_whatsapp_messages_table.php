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
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            // Who actually sent an outbound reply - null for an AI-generated one
            // (is_ai_generated already says that) or for anything sent before
            // this column existed. Lets the inbox show "AI" vs. a specific staff
            // member's name/avatar on each outbound message and conversation
            // preview, instead of just an undifferentiated "you".
            $table->foreignId('sent_by_user_id')->nullable()->after('is_ai_generated')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sent_by_user_id');
        });
    }
};
