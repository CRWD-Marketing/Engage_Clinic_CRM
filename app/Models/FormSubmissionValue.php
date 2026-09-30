<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * One answer inside a submission. field_name/label/type are snapshotted at
 * submission time so later edits to the form never change what the client
 * actually answered.
 */
class FormSubmissionValue extends Model
{
    protected $fillable = [
        'form_submission_id',
        'form_field_id',
        'field_name',
        'field_label',
        'field_type',
        'value',
        'file_path',
        'file_original_name',
        'file_size',
        'file_mime',
        'sort_order',
    ];

    public function submission()
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function isFile(): bool
    {
        return $this->field_type === 'file';
    }

    /**
     * Multi-select answers are stored as a JSON array.
     */
    public function listValue(): array
    {
        $decoded = json_decode((string) $this->value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function isEmpty(): bool
    {
        if ($this->isFile()) {
            return ! $this->file_path;
        }

        return $this->value === null || $this->value === '' || $this->value === '[]';
    }

    /**
     * Plain-text rendering of the answer - used for search, lead mapping and
     * the response table.
     */
    public function displayText(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        return match ($this->field_type) {
            'file' => (string) $this->file_original_name,
            'consent' => $this->value === '1' ? 'Agreed' : 'Not agreed',
            'checkbox' => str_starts_with((string) $this->value, '[')
                ? implode(', ', $this->listValue())
                : ($this->value === '1' ? 'Yes' : 'No'),
            'date' => $this->formattedDate(),
            default => (string) $this->value,
        };
    }

    private function formattedDate(): string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $this->value)->format('F j, Y');
        } catch (\Throwable) {
            return (string) $this->value;
        }
    }
}
