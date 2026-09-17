<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'job_title',
        'email',
        'phone_number',
        'timezone',
        'message_signature',
        'password',

        'department',
        'manager_id',
        'role',

        'start_date',
        'notes',

        'is_active',
        'invited_at',
        'last_login_at',
        'email_verified_at',

        'role_template_id',
        'modules',
        'module_levels',
        'actions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'invited_at' => 'datetime',
            'start_date' => 'date',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'modules' => 'array',
            'module_levels' => 'array',
            'actions' => 'array',
        ];
    }

    /**
     * Boot the model — generate a public UUID on creation.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->public_id ??= (string) Str::uuid();
        });
    }

    /**
     * Use public_id for route-model binding instead of the numeric id.
     */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * Get the user's manager.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the users managed by this user.
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * Full name accessor.
     */
    public function getFullNameAttribute(): string
    {
        return trim(
            $this->first_name . ' ' .
            ($this->middle_name ? $this->middle_name . ' ' : '') .
            $this->last_name
        );
    }

    public function roleTemplate(): BelongsTo
    {
        return $this->belongsTo(RoleTemplate::class);
    }

    /**
     * The modules this user actually holds: their own copy if one was granted
     * (Roles & access), else their template's, else nothing data-driven -
     * canAccessFeature() then falls back to the legacy role config.
     */
    public function effectiveModules(): ?array
    {
        return $this->modules ?? $this->roleTemplate?->modules;
    }

    public function effectiveActions(): ?array
    {
        return $this->actions ?? $this->roleTemplate?->actions;
    }

    public function effectiveModuleLevels(): array
    {
        return $this->module_levels ?? $this->roleTemplate?->module_levels ?? [];
    }

    /**
     * How much of a granted module this user can reach - "full" unless
     * something more specific (own/view/edit) was set, so a module that
     * predates this concept, or was never given a level, behaves exactly as
     * it always did. Meaningless for a module the user doesn't hold at all.
     */
    public function levelFor(string $module): string
    {
        return $this->effectiveModuleLevels()[$module] ?? 'full';
    }

    /**
     * Check if the user can reach a given sidebar/feature key. Data-driven
     * (Roles & access) when the user holds a module list; otherwise the
     * role -> features map in config/role_permissions.php.
     */
    public function canAccessFeature(string $feature): bool
    {
        $modules = $this->effectiveModules();

        $key = RoleTemplate::MODULE_ALIASES[$feature] ?? $feature;

        if ($modules !== null && array_key_exists($key, RoleTemplate::MODULES)) {
            return in_array($key, $modules, true);
        }

        return in_array($feature, config("role_permissions.{$this->role}", []), true);
    }

    /**
     * Whether the user may perform one of the named actions (see
     * RoleTemplate::ACTIONS). Without a data-driven grant, falls back to the
     * system template for their base role so nothing is locked out before the
     * templates are seeded.
     */
    public function canDo(string $action): bool
    {
        $actions = $this->effectiveActions();

        if ($actions === null) {
            $actions = collect(RoleTemplate::SYSTEM)->firstWhere('base_role', $this->role)['actions'] ?? [];
        }

        return in_array($action, $actions, true);
    }

    /**
     * Copy a template's modules/actions onto the user. Editing the template
     * later doesn't change them - per-user grants can go beyond the role.
     */
    public function applyTemplate(RoleTemplate $template): void
    {
        $this->forceFill([
            'role_template_id' => $template->id,
            'role' => $template->base_role,
            'modules' => $template->modules,
            'module_levels' => $template->module_levels,
            'actions' => $template->actions,
        ])->save();
    }

    /**
     * suspended (deactivated) / invited (never signed in yet) / active.
     */
    public function accessStatus(): string
    {
        if (! $this->is_active) {
            return 'suspended';
        }
        if ($this->invited_at && ! $this->last_login_at) {
            return 'invited';
        }

        return 'active';
    }

    /**
     * Human-readable role label, e.g. "FULL_ADMIN" -> "Full Admin".
     */
    public function roleLabel(): string
    {
        return Str::title(str_replace('_', ' ', strtolower($this->role)));
    }
}