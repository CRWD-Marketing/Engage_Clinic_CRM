<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The designer's autosaved work that isn't on the form yet: changes to a
 * live form (kept off the public page until "Save changes"), or a draft
 * that can't be saved as-is (e.g. a dropdown with no options). Cleared by
 * every real save; restored when the designer is reopened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->json('autosave')->nullable()->after('design');
            $table->timestamp('autosaved_at')->nullable()->after('autosave');
            $table->foreignId('autosaved_by')->nullable()->after('autosaved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('autosaved_by');
            $table->dropColumn(['autosave', 'autosaved_at']);
        });
    }
};
