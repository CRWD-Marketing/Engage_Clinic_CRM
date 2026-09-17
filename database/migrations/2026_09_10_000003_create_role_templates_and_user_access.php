<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles & access: role templates are the starting set of modules/actions;
     * each user holds their own copy (so editing a template never silently
     * changes people already assigned, and a user can be granted more than
     * their role). The legacy `role` enum stays as the "base role" behind a
     * template so every existing role check keeps working.
     */
    public function up(): void
    {
        Schema::create('role_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->string('base_role', 40);
            $table->json('modules');
            $table->json('actions');
            $table->boolean('is_system')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_template_id')->nullable()->after('role')->constrained('role_templates')->nullOnDelete();
            $table->json('modules')->nullable()->after('role_template_id');
            $table->json('actions')->nullable()->after('modules');
            $table->timestamp('invited_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_template_id']);
            $table->dropColumn(['role_template_id', 'modules', 'actions', 'invited_at']);
        });

        Schema::dropIfExists('role_templates');
    }
};
