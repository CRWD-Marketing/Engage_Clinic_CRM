<?php

namespace App\Services\CmsForms;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Validates a public submission against the form's own field definitions
 * and stores it. Inputs arrive as fields[<field name>].
 */
class FormSubmissionService
{
    public const UPLOAD_DISK = 'local';

    public function __construct(private LeadConversionService $conversion)
    {
    }

    /**
     * @throws ValidationException
     */
    public function store(Form $form, Request $request): FormSubmission
    {
        $form->loadMissing('fields');

        $rules = [];
        $attributes = [];
        foreach ($form->inputFields() as $field) {
            $key = "fields.{$field->name}";
            $rules += $this->rulesFor($field, $key);
            $attributes[$key] = $field->label;
            $attributes["{$key}.*"] = $field->label;
        }

        $validated = Validator::make($request->all(), $rules, [
            'accepted' => $form->inputFields()->contains('type', 'consent') ? 'You must agree to continue.' : 'The :attribute field must be ticked.',
        ], $attributes)->validate();

        $answers = $validated['fields'] ?? [];
        $stored = [];

        try {
            $submission = DB::transaction(function () use ($form, $request, $answers, &$stored) {
                $submission = $form->submissions()->create([
                    'status' => FormSubmission::STATUS_NEW,
                    'submitted_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                ]);

                foreach ($form->inputFields() as $field) {
                    $value = $answers[$field->name] ?? null;
                    $row = [
                        'form_field_id' => $field->id,
                        'field_name' => $field->name,
                        'field_label' => $field->label,
                        'field_type' => $field->type,
                        'sort_order' => $field->sort_order,
                        'value' => null,
                    ];

                    if ($field->type === 'file') {
                        if ($value instanceof UploadedFile) {
                            $path = $value->store("cms-forms/{$form->id}", self::UPLOAD_DISK);
                            $stored[] = $path;
                            $row += [
                                'file_path' => $path,
                                'file_original_name' => substr(basename($value->getClientOriginalName()), 0, 255),
                                'file_size' => $value->getSize(),
                                'file_mime' => $value->getClientMimeType(),
                            ];
                        }
                    } else {
                        $row['value'] = $this->normalise($field, $value);
                    }

                    $submission->values()->create($row);
                }

                return $submission;
            });
        } catch (\Throwable $e) {
            // Don't leave orphaned uploads behind a submission that was never saved.
            foreach ($stored as $path) {
                Storage::disk(self::UPLOAD_DISK)->delete($path);
            }
            throw $e;
        }

        if ($form->auto_create_lead) {
            try {
                $this->conversion->convert($submission, $this->conversion->defaults($submission), null);
            } catch (\Throwable $e) {
                // The client's submission is already safe; staff can still convert it by hand.
                report($e);
            }
        }

        return $submission;
    }

    private function rulesFor(FormField $field, string $key): array
    {
        $presence = $field->is_required ? 'required' : 'nullable';
        $options = $field->options ?? [];

        return match ($field->type) {
            'short_text' => [$key => [$presence, 'string', 'max:500']],
            'long_text' => [$key => [$presence, 'string', 'max:5000']],
            'email' => [$key => [$presence, 'email', 'max:255']],
            'phone' => [$key => [$presence, 'string', 'max:30', 'regex:/^\+?[0-9\s().-]{6,30}$/']],
            'number' => [$key => [$presence, 'numeric']],
            'date' => [$key => [$presence, 'date_format:Y-m-d']],
            'dropdown', 'radio' => [$key => [$presence, 'string', Rule::in($options)]],
            'checkbox' => $field->isMultiChoice()
                ? [$key => [$presence, 'array', ...($field->is_required ? ['min:1'] : [])], "{$key}.*" => ['string', Rule::in($options)]]
                : [$key => $field->is_required ? ['accepted'] : ['nullable', 'in:0,1,on,yes,true']],
            'consent' => [$key => $field->is_required ? ['accepted'] : ['nullable', 'in:0,1,on,yes,true']],
            'file' => [$key => [$presence, 'file', 'max:'.FormField::FILE_MAX_KB, 'mimes:'.implode(',', FormField::FILE_MIMES)]],
            default => [$key => ['nullable']],
        };
    }

    private function normalise(FormField $field, mixed $value): ?string
    {
        if (in_array($field->type, ['consent', 'checkbox'], true) && ! $field->isMultiChoice()) {
            return in_array($value, [1, '1', 'on', 'yes', 'true', true], true) ? '1' : '0';
        }

        if (is_array($value)) {
            return $value ? json_encode(array_values($value)) : null;
        }

        $value = is_string($value) ? trim($value) : $value;

        return $value === null || $value === '' ? null : (string) $value;
    }
}
