<?php

namespace App\Models;

use App\Support\CmsForms\FormDesign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A CMS form built in CMS -> Forms (e.g. an event registration). Clients
 * fill it in on its public URL (/forms/{slug}); each fill-in is a
 * FormSubmission that staff can review and convert into a Lead.
 */
class Form extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';

    const DEFAULT_SUCCESS_MESSAGE = 'Thank you! Your registration has been received. Our team will be in touch shortly.';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'status',
        'success_message',
        'auto_create_lead',
        'design',
        'created_by',
        'published_at',
        'autosave',
        'autosaved_at',
        'autosaved_by',
    ];

    protected $casts = [
        'auto_create_lead' => 'boolean',
        'design' => 'array',
        'published_at' => 'datetime',
        'autosave' => 'array',
        'autosaved_at' => 'datetime',
    ];

    protected $hidden = ['autosave'];

    public function fields()
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function publicUrl(): string
    {
        return route('forms.public.show', $this->slug);
    }

    public function embedCode(): string
    {
        $src = e($this->publicUrl().'?embed=1');

        return '<iframe src="'.$src.'" title="'.e($this->title).'" style="width:100%;min-height:720px;border:0;" loading="lazy"></iframe>';
    }

    /**
     * The form's theme with defaults filled in (see FormDesign).
     */
    public function designSettings(): array
    {
        return FormDesign::resolve($this->design);
    }

    public function inputFields()
    {
        return $this->fields->reject(fn (FormField $f) => $f->isLayout())->values();
    }

    /**
     * What the delete-confirmation dialog needs, as JSON for a
     * data-delete-form attribute. Uses the *_count attributes the Forms list
     * loads, falling back to a query elsewhere.
     */
    public function deleteDialogJson(): string
    {
        return json_encode([
            'title' => $this->title,
            'action' => route('cms.forms.destroy', $this),
            'responses' => (int) ($this->submissions_count ?? $this->submissions()->count()),
            'converted' => (int) ($this->converted_submissions_count ?? $this->submissions()->whereNotNull('lead_id')->count()),
            'published' => $this->isPublished(),
            'unpublishAction' => route('cms.forms.unpublish', $this),
        ]);
    }

    public function successMessage(): string
    {
        return $this->success_message ?: self::DEFAULT_SUCCESS_MESSAGE;
    }

    /**
     * A URL slug derived from $title that no other form uses.
     */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'form';
        $base = Str::limit($base, 100, '');
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
