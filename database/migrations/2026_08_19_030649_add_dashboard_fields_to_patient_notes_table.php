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
        Schema::table('patient_notes', function (Blueprint $table) {
            $table->boolean('flagged')->default(false)->after('body');
            $table->string('flag_reason')->nullable()->after('flagged');
            $table->timestamp('signed_off_at')->nullable()->after('flag_reason');
            $table->foreignId('signed_off_by')->nullable()->after('signed_off_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_off_by');
            $table->dropColumn(['flagged', 'flag_reason', 'signed_off_at']);
        });
    }
};
