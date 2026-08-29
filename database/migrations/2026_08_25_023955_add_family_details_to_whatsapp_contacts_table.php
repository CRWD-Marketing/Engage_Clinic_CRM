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
            // Manual overrides for the auto-detected lead hints shown in the Family
            // details panel - null means "not yet manually set, fall back to what
            // extractLeadHints() scanned from the conversation text".
            $table->string('child_name')->nullable()->after('avatar_url');
            $table->string('interested_in')->nullable()->after('child_name');
            $table->string('insurance')->nullable()->after('interested_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_contacts', function (Blueprint $table) {
            $table->dropColumn(['child_name', 'interested_in', 'insurance']);
        });
    }
};
