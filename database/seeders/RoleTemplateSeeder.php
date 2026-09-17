<?php

namespace Database\Seeders;

use App\Models\RoleTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleTemplateSeeder extends Seeder
{
    /**
     * The eight system access levels, then hand every existing user the
     * template matching their base role (copying its modules/actions onto
     * them). Idempotent: users who already hold a template are left alone.
     */
    public function run(): void
    {
        $order = 0;
        foreach (RoleTemplate::SYSTEM as $key => $def) {
            RoleTemplate::updateOrCreate(
                ['key' => $key],
                $def + ['is_system' => true, 'sort_order' => $order++]
            );
        }

        User::whereNull('role_template_id')->get()->each(function (User $user) {
            $template = RoleTemplate::systemForRole($user->role) ?? RoleTemplate::where('key', 'basic')->first();
            if ($template) {
                $user->applyTemplate($template);
            }
        });

        // Clinical Standard Access (therapists) was mistakenly seeded with
        // booking rights - strip it from anyone still holding that exact
        // system default (a per-user grant beyond it is left untouched).
        $clinicalStandard = RoleTemplate::where('key', 'clinical_standard')->first();
        if ($clinicalStandard) {
            User::where('role_template_id', $clinicalStandard->id)->get()->each(function (User $user) {
                $actions = $user->actions ?? [];
                if (in_array('book_modify_session', $actions, true)) {
                    $user->forceFill([
                        'actions' => array_values(array_diff($actions, ['book_modify_session'])),
                    ])->save();
                }
            });
        }

        // When a module is added to the system later, users already holding a
        // copy of their template would silently lack it - grant any module
        // their template now has that they've never been given or refused
        // (i.e. that didn't exist when their copy was taken). Custom per-user
        // trims of modules that already existed are left alone.
        User::whereNotNull('role_template_id')->with('roleTemplate')->get()->each(function (User $user) {
            $held = $user->modules ?? [];
            $templateModules = $user->roleTemplate?->modules ?? [];
            $legacyKeys = ['dashboard', 'leads', 'whatsapp', 'patients', 'calendar', 'therapists', 'billing', 'reports', 'packages', 'roles_access'];
            $newKeys = array_diff(array_keys(RoleTemplate::MODULES), $legacyKeys);
            // ...and anything the legacy role config already let them reach,
            // so moving to templates never takes away access they had before.
            $legacyGrants = config("role_permissions.{$user->role}", []);
            $grant = array_intersect($templateModules, array_merge($newKeys, $legacyGrants));

            $merged = array_values(array_unique(array_merge($held, $grant)));
            if ($merged !== $held) {
                $user->forceFill(['modules' => $merged])->save();
            }
        });
    }
}
