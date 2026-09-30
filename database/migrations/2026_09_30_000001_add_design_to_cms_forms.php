<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visual designer for CMS Forms: a form-level theme (colours, font,
     * header, field style…) and per-field layout (column span, label
     * position, bold…). Both are sanitised by App\Support\CmsForms\FormDesign;
     * null means "use the defaults", so existing forms keep rendering.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->json('design')->nullable()->after('auto_create_lead');
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('options');
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('settings');
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('design');
        });
    }
};
