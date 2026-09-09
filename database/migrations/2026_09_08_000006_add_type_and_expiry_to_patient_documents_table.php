<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documents are now filed as metadata records (name, type, expiry) with
     * no required upload - file_path/original_filename become optional,
     * kept only for the handful of legacy rows that do have a real file.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE patient_documents MODIFY file_path VARCHAR(255) NULL');
        DB::statement('ALTER TABLE patient_documents MODIFY original_filename VARCHAR(255) NULL');

        Schema::table('patient_documents', function (Blueprint $table) {
            $table->string('type')->nullable()->after('name');
            $table->date('expires_at')->nullable()->after('file_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_documents', function (Blueprint $table) {
            $table->dropColumn(['type', 'expires_at']);
        });

        DB::statement('ALTER TABLE patient_documents MODIFY file_path VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE patient_documents MODIFY original_filename VARCHAR(255) NOT NULL');
    }
};
