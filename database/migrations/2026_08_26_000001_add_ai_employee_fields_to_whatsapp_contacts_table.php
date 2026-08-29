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
        Schema::table('whatsapp_contacts', function (Blueprint $table) {
            // ai_active | human_assigned | human_takeover | closed
            $table->string('ai_state')->default('ai_active')->after('unread_count');
            $table->foreignId('assigned_user_id')->nullable()->after('ai_state')
                ->constrained('users')->nullOnDelete();

            // Set the moment the AI escalates or a job permanently fails, so staff
            // notice even if nobody has manually changed ai_state yet.
            $table->boolean('needs_human_attention')->default(false)->after('assigned_user_id');
            $table->text('needs_human_reason')->nullable()->after('needs_human_attention');

            $table->foreignId('ai_state_changed_by')->nullable()->after('needs_human_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('ai_state_changed_at')->nullable()->after('ai_state_changed_by');

            $table->index('ai_state');
            $table->index('needs_human_attention');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_contacts', function (Blueprint $table) {
            $table->dropForeign(['assigned_user_id']);
            $table->dropForeign(['ai_state_changed_by']);
            $table->dropColumn([
                'ai_state',
                'assigned_user_id',
                'needs_human_attention',
                'needs_human_reason',
                'ai_state_changed_by',
                'ai_state_changed_at',
            ]);
        });
    }
};
