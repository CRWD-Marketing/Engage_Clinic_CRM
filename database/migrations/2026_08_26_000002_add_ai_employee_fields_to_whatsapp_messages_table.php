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
            $table->boolean('is_ai_generated')->default(false)->after('status');

            // null (not AI-related) | pending | processing | completed | failed | skipped
            $table->string('ai_processing_status')->nullable()->after('is_ai_generated');

            // On an inbound message: the outbound AI reply it triggered, if any.
            // Self-referencing, nullable, nullOnDelete so deleting either side never
            // cascades unexpectedly into unrelated conversation history.
            $table->foreignId('triggered_by_message_id')->nullable()->after('ai_processing_status')
                ->constrained('whatsapp_messages')->nullOnDelete();

            $table->text('ai_error')->nullable()->after('triggered_by_message_id');

            $table->index('ai_processing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropForeign(['triggered_by_message_id']);
            $table->dropColumn([
                'is_ai_generated',
                'ai_processing_status',
                'triggered_by_message_id',
                'ai_error',
            ]);
        });
    }
};
