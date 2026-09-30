<?php

namespace App\Services\CmsForms;

use App\Models\Form;
use App\Models\FormField;
use App\Support\CmsForms\FormDesign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Validates and saves what the form builder sends: the form's settings plus
 * its full, ordered field list.
 */
class FormBuilderService
{
    /**
     * @throws ValidationException
     */
    public function save(Form $form, array $payload): Form
    {
        $validator = Validator::make($payload, [
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('forms', 'slug')->ignore($form->id)],
            'description' => 'nullable|string|max:5000',
            'success_message' => 'nullable|string|max:2000',
            'auto_create_lead' => 'boolean',
            'design' => 'nullable|array',
            'fields' => 'present|array|max:150',
            'fields.*.settings' => 'nullable|array',
            'fields.*.id' => 'nullable|integer',
            'fields.*.type' => ['required', Rule::in(array_keys(FormField::TYPES))],
            'fields.*.label' => 'required|string|max:255',
            'fields.*.name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', 'distinct'],
            'fields.*.placeholder' => 'nullable|string|max:255',
            'fields.*.help_text' => 'nullable|string|max:500',
            'fields.*.is_required' => 'boolean',
            'fields.*.options' => 'nullable|array|max:50',
            'fields.*.options.*' => 'nullable|string|max:255',
        ], [
            'slug.regex' => 'The URL may only contain lowercase letters, numbers and hyphens.',
            'slug.unique' => 'Another form already uses this URL.',
            'fields.*.name.regex' => 'Field names must start with a letter and use only a-z, 0-9 and underscores.',
            'fields.*.name.distinct' => 'Each field needs its own field name.',
            'fields.*.label.required' => 'Every field needs a label.',
        ]);

        $validator->after(function ($v) use ($form, $payload) {
            if ($form->isPublished() && ($payload['slug'] ?? null) !== $form->slug) {
                $v->errors()->add('slug', 'This form is live - unpublish it before changing its URL, so shared links keep working.');
            }
            $inputs = collect($payload['fields'] ?? [])->reject(fn ($f) => in_array($f['type'] ?? null, FormField::LAYOUT_TYPES, true));
            if ($form->isPublished() && $inputs->isEmpty()) {
                $v->errors()->add('fields', 'A published form needs at least one question clients can answer.');
            }
            foreach ($payload['fields'] ?? [] as $i => $field) {
                $options = $this->cleanOptions($field['options'] ?? []);
                if (in_array($field['type'] ?? null, ['dropdown', 'radio'], true) && count($options) === 0) {
                    $v->errors()->add("fields.{$i}.options", '"'.($field['label'] ?? 'Field').'" needs at least one option.');
                }
            }
        });

        $data = $validator->validate();

        return DB::transaction(function () use ($form, $data) {
            $form->update([
                'title' => trim($data['title']),
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'success_message' => filled($data['success_message'] ?? null) ? trim($data['success_message']) : null,
                'auto_create_lead' => (bool) ($data['auto_create_lead'] ?? false),
                'design' => array_key_exists('design', $data) ? FormDesign::sanitize($data['design'] ?? []) : $form->design,
            ]);

            $this->syncFields($form, $data['fields']);

            return $form->fresh('fields');
        });
    }

    /**
     * Update fields that still exist, create new ones, delete removed ones.
     * Existing answers keep their own snapshot of the field, so removing a
     * field never loses what a client submitted.
     */
    private function syncFields(Form $form, array $fields): void
    {
        $existing = $form->fields()->get()->keyBy('id');
        $keepIds = collect($fields)->pluck('id')->filter(fn ($id) => $existing->has($id))->all();

        $form->fields()->whereNotIn('id', $keepIds)->delete();

        // Renames can swap names between fields (a -> b, b -> a); park kept
        // fields on a temporary name first so the (form_id, name) unique
        // index never sees a transient duplicate.
        foreach ($keepIds as $id) {
            $existing[$id]->update(['name' => "__tmp_{$id}"]);
        }

        foreach (array_values($fields) as $order => $field) {
            $layout = in_array($field['type'], FormField::LAYOUT_TYPES, true);
            $attributes = [
                'type' => $field['type'],
                'label' => trim($field['label']),
                'name' => $field['name'],
                'placeholder' => $layout ? null : ($field['placeholder'] ?? null),
                'help_text' => $field['help_text'] ?? null,
                'is_required' => ! $layout && (bool) ($field['is_required'] ?? false),
                'options' => in_array($field['type'], FormField::OPTION_TYPES, true) ? $this->cleanOptions($field['options'] ?? []) : null,
                'settings' => FormDesign::sanitizeField($field['settings'] ?? []),
                'sort_order' => $order,
            ];

            $id = $field['id'] ?? null;
            if ($id && $existing->has($id)) {
                $existing[$id]->update($attributes);
            } else {
                $form->fields()->create($attributes);
            }
        }
    }

    private function cleanOptions(array $options): array
    {
        return collect($options)->map(fn ($o) => trim((string) $o))->filter()->unique()->values()->all();
    }

    /**
     * Copy a form and its fields as a new draft.
     */
    public function duplicate(Form $form, ?int $userId): Form
    {
        return DB::transaction(function () use ($form, $userId) {
            $title = $form->title.' (copy)';
            $copy = Form::create([
                'title' => $title,
                'slug' => Form::uniqueSlug($title),
                'description' => $form->description,
                'status' => Form::STATUS_DRAFT,
                'success_message' => $form->success_message,
                'auto_create_lead' => $form->auto_create_lead,
                'design' => $form->design,
                'created_by' => $userId,
            ]);

            foreach ($form->fields as $field) {
                $copy->fields()->create($field->only(['type', 'label', 'name', 'placeholder', 'help_text', 'is_required', 'options', 'settings', 'sort_order']));
            }

            return $copy;
        });
    }
}
