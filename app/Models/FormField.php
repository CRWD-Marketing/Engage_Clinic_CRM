<?php

namespace App\Models;

use App\Support\CmsForms\FormDesign;
use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    /**
     * Supported field types => builder label.
     */
    const TYPES = [
        'short_text' => 'Short Text',
        'long_text' => 'Long Text',
        'email' => 'Email',
        'phone' => 'Phone',
        'number' => 'Number',
        'date' => 'Date',
        'dropdown' => 'Dropdown',
        'radio' => 'Multiple Choice',
        'checkbox' => 'Checkbox',
        'file' => 'File Upload',
        'consent' => 'Consent Checkbox',
        // Layout blocks - shown on the form, never collect an answer.
        'section' => 'Section Heading',
        'paragraph' => 'Text Block',
        'divider' => 'Divider',
    ];

    const LAYOUT_TYPES = ['section', 'paragraph', 'divider'];

    /** Types whose choices come from `options`. A checkbox with no options is a single yes/no tick. */
    const OPTION_TYPES = ['dropdown', 'radio', 'checkbox'];

    const FILE_MAX_KB = 10240;

    const FILE_MIMES = ['pdf', 'jpg', 'jpeg', 'png', 'heic', 'doc', 'docx'];

    protected $fillable = [
        'form_id',
        'type',
        'label',
        'name',
        'placeholder',
        'help_text',
        'is_required',
        'options',
        'settings',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
        'settings' => 'array',
        'sort_order' => 'integer',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function isLayout(): bool
    {
        return in_array($this->type, self::LAYOUT_TYPES, true);
    }

    /**
     * Layout settings with defaults filled in (see FormDesign::FIELD_DEFAULTS).
     */
    public function layout(): array
    {
        return FormDesign::sanitizeField($this->settings);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, self::OPTION_TYPES, true) && ! empty($this->options);
    }

    /**
     * Is this a checkbox group (several choices) rather than a single tick?
     */
    public function isMultiChoice(): bool
    {
        return $this->type === 'checkbox' && ! empty($this->options);
    }
}
