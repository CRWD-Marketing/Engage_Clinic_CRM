<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A parallel {module_key: level} map alongside `modules` - a module being
     * granted at all is still the yes/no gate `modules` already decides;
     * this only refines HOW much of it, once granted. A key missing here
     * means "full", so every existing role template / user grant keeps
     * behaving exactly as it did before this column existed.
     */
    public function up(): void
    {
        Schema::table('role_templates', function (Blueprint $table) {
            $table->json('module_levels')->nullable()->after('modules');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('module_levels')->nullable()->after('modules');
        });
    }

    public function down(): void
    {
        Schema::table('role_templates', function (Blueprint $table) {
            $table->dropColumn('module_levels');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('module_levels');
        });
    }
};
