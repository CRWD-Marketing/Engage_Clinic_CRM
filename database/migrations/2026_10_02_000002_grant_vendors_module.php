<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Templates the Vendors module ships with (mirrors RoleTemplate::SYSTEM).
     * Users hold their own copy of their template's modules, so the grant is
     * copied onto anyone currently on one of these templates as well -
     * otherwise existing admins would never see the new module.
     */
    private const TEMPLATES = ['full_admin'];

    private const MODULE = 'vendors';

    public function up(): void
    {
        if (! Schema::hasTable('role_templates')) {
            return;
        }

        foreach (DB::table('role_templates')->whereIn('key', self::TEMPLATES)->get() as $template) {
            DB::table('role_templates')->where('id', $template->id)
                ->update(['modules' => json_encode($this->with(json_decode($template->modules, true)))]);

            foreach (DB::table('users')->where('role_template_id', $template->id)->whereNotNull('modules')->get(['id', 'modules']) as $user) {
                DB::table('users')->where('id', $user->id)
                    ->update(['modules' => json_encode($this->with(json_decode($user->modules, true)))]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_templates')) {
            return;
        }

        foreach (['role_templates', 'users'] as $table) {
            foreach (DB::table($table)->whereNotNull('modules')->get(['id', 'modules']) as $row) {
                $modules = json_decode($row->modules, true) ?: [];
                if (in_array(self::MODULE, $modules, true)) {
                    DB::table($table)->where('id', $row->id)
                        ->update(['modules' => json_encode(array_values(array_diff($modules, [self::MODULE])))]);
                }
            }
        }
    }

    private function with(?array $modules): array
    {
        $modules = $modules ?: [];

        return in_array(self::MODULE, $modules, true) ? $modules : [...$modules, self::MODULE];
    }
};
