<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MySQL/MariaDB (without explicit_defaults_for_timestamp) silently give
     * the first NOT NULL TIMESTAMP column in a table
     * "DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" - so every
     * status change / conversion rewrote form_submissions.submitted_at with
     * the database server's clock. Drop that behaviour, then restore the real
     * submission time from created_at (both are written in the same insert).
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE form_submissions MODIFY submitted_at TIMESTAMP NULL DEFAULT NULL');
        DB::statement('UPDATE form_submissions SET submitted_at = created_at WHERE created_at IS NOT NULL');
    }

    public function down(): void
    {
        // Intentionally not restoring the auto-updating column.
    }
};
