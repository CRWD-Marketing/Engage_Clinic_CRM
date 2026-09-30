<?php

namespace App\Services\CmsForms;

use App\Models\Activity;
use App\Models\FormSubmission;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Notifications\LeadAssigned;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Turns a form response into a regular CRM Lead - the same Lead model,
 * pipeline (starts in "New"), activity log and assignment notification the
 * Leads module uses. The response is kept and linked, never replaced.
 */
class LeadConversionService
{
    public static function reference(Lead|int $lead): string
    {
        return sprintf('LD-%06d', $lead instanceof Lead ? $lead->id : $lead);
    }

    /**
     * Pre-fill for the Convert to Lead modal, guessed from each answer's
     * field type, name and label.
     */
    public function defaults(FormSubmission $submission): array
    {
        $submission->loadMissing('values', 'form');

        $out = [
            'parent_guardian_name' => '',
            'child_name' => '',
            'child_age' => '',
            'email' => '',
            'phone' => '',
            'interested_in' => '',
            'notes' => '',
            'source' => Str::limit((string) $submission->form?->title, 50, ''),
        ];
        $notes = [];

        foreach ($submission->values as $value) {
            $text = trim($value->displayText());
            $target = $this->guessTarget($value->field_type, "{$value->field_name} {$value->field_label}");
            if (! $target || $text === '') {
                continue;
            }
            if ($target === 'notes') {
                $notes[] = $text;
            } elseif ($target === 'interested_in' && count($choices = $value->listValue()) > 0) {
                // A lead has one Service: take the first ticked, keep the full list in the notes.
                if ($out['interested_in'] === '') {
                    $out['interested_in'] = Str::limit((string) $choices[0], 100, '');
                }
                if (count($choices) > 1) {
                    $notes[] = "{$value->field_label} {$text}";
                }
            } elseif ($out[$target] === '') {
                $out[$target] = $text;
            }
        }

        $out['notes'] = implode("\n\n", $notes);

        return $out;
    }

    /**
     * The name/email pair shown in the responses table.
     */
    public function identity(FormSubmission $submission): array
    {
        $d = $this->defaults($submission);

        return ['name' => $d['parent_guardian_name'] ?: $d['child_name'], 'email' => $d['email'], 'phone' => $d['phone']];
    }

    private function guessTarget(string $type, string $key): ?string
    {
        $key = strtolower($key);

        return match (true) {
            $type === 'email' => 'email',
            $type === 'phone' => 'phone',
            in_array($type, ['file', 'consent'], true) => null,
            (bool) preg_match('/child|kid|son|daughter/', $key) && str_contains($key, 'name') => 'child_name',
            (bool) preg_match('/(^|[\s_])age($|[\s_])/', $key) => 'child_age',
            str_contains($key, 'name') => 'parent_guardian_name',
            (bool) preg_match('/service|program|interest|therapy/', $key) => 'interested_in',
            $type === 'long_text' || (bool) preg_match('/message|note|comment|question|detail/', $key) => 'notes',
            default => null,
        };
    }

    /**
     * Same limits LeadController::store() applies to a new lead.
     */
    public function rules(): array
    {
        return [
            'parent_guardian_name' => 'required|string|max:255',
            'child_name' => 'nullable|string|max:255',
            'child_age' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'interested_in' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'source' => 'required|string|max:50',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws ConversionException when already converted or a conversion is in flight
     */
    public function convert(FormSubmission $submission, array $input, ?User $by): Lead
    {
        $data = Validator::make($input, $this->rules(), [
            'parent_guardian_name.required' => 'A name is required to create the lead.',
        ])->validate();
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        // Same double-click guard as Contacts' status email: a repeated
        // submit must never produce two leads for one response.
        $lock = Cache::lock("form-submission-convert-{$submission->id}", 30);
        if (! $lock->get()) {
            throw new ConversionException('This response is already being converted.');
        }

        try {
            return DB::transaction(function () use ($submission, $data, $by) {
                $fresh = FormSubmission::with('form')->lockForUpdate()->findOrFail($submission->id);
                if ($fresh->isConverted()) {
                    throw new ConversionException('This response has already been converted to a lead.', $fresh->lead_id);
                }

                $formTitle = (string) $fresh->form?->title;

                $lead = Lead::create([
                    'parent_guardian_name' => $data['parent_guardian_name'],
                    'child_name' => $data['child_name'] ?? null,
                    'child_age' => $data['child_age'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'interested_in' => $data['interested_in'] ?? null,
                    'notes' => $data['notes'] ?? '',
                    'source' => $data['source'],
                    'lead_form_name' => Str::limit($formTitle, 255, ''),
                    'status' => Lead::STATUS_NEW,
                    'assigned_to' => $data['assigned_to'] ?? null,
                ]);

                $lead->activities()->create([
                    'user_id' => $by?->id,
                    'type' => LeadActivity::TYPE_NOTE,
                    'body' => "Created from form response #{$fresh->id} ({$formTitle}).",
                ]);

                if ($lead->assigned_to) {
                    $owner = User::find($lead->assigned_to);
                    $lead->activities()->create([
                        'user_id' => $by?->id,
                        'type' => LeadActivity::TYPE_ASSIGNMENT,
                        'body' => 'Assigned to '.trim($owner->first_name.' '.$owner->last_name),
                    ]);
                    if ((int) $owner->id !== (int) $by?->id) {
                        $owner->notify(new LeadAssigned($lead));
                    }
                }

                $fresh->update([
                    'lead_id' => $lead->id,
                    'status' => FormSubmission::STATUS_CONVERTED,
                    'converted_at' => now(),
                    'converted_by' => $by?->id,
                ]);

                Activity::log(
                    'form_response_converted',
                    "Form response #{$fresh->id} ({$formTitle}) converted to lead ".self::reference($lead),
                    route('cms.forms.responses.show', $fresh),
                    $by?->id
                );

                return $lead;
            });
        } finally {
            $lock->release();
        }
    }
}
