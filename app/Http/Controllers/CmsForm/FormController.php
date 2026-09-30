<?php

namespace App\Http\Controllers\CmsForm;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionValue;
use App\Services\CmsForms\FormBuilderService;
use App\Services\CmsForms\FormSubmissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\CmsForms\FormDesign;
use App\Support\CmsForms\FormTemplates;
use Illuminate\Http\Request;

/**
 * CMS -> Forms: create, build, publish and manage custom forms.
 */
class FormController extends Controller
{
    public function __construct(private FormBuilderService $builder)
    {
    }

    public function index()
    {
        $forms = Form::with('fields')
            ->withCount([
                'submissions',
                'submissions as new_submissions_count' => fn ($q) => $q->where('status', FormSubmission::STATUS_NEW),
                'submissions as converted_submissions_count' => fn ($q) => $q->whereNotNull('lead_id'),
            ])
            ->withMax('submissions', 'submitted_at')
            ->latest()
            ->get();

        $stats = [
            'forms' => $forms->count(),
            'published' => $forms->where('status', Form::STATUS_PUBLISHED)->count(),
            'drafts' => $forms->where('status', Form::STATUS_DRAFT)->count(),
            'responses' => $forms->sum('submissions_count'),
            'new' => $forms->sum('new_submissions_count'),
            'this_week' => FormSubmission::where('submitted_at', '>=', now()->subDays(7))->count(),
        ];

        return view('cms_forms.index', [
            'forms' => $forms,
            'stats' => $stats,
            'templates' => FormTemplates::options(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'template' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_keys(FormTemplates::options()))],
        ]);

        $key = $request->input('template', 'blank');
        $template = FormTemplates::get($key);
        $title = trim((string) $request->input('title')) ?: $template['name'];

        // Every template field and style can be edited, moved or removed in the designer.
        $form = Form::create([
            'title' => $title,
            'slug' => Form::uniqueSlug($title),
            'status' => Form::STATUS_DRAFT,
            'design' => $template['design'] ? FormDesign::sanitize($template['design']) : null,
            'created_by' => auth()->id(),
        ]);

        foreach (FormTemplates::fields($key) as $i => $field) {
            $form->fields()->create($field + ['sort_order' => $i, 'settings' => FormDesign::sanitizeField($field['settings'] ?? [])]);
        }

        return redirect()->route('cms.forms.edit', $form);
    }

    public function edit(Form $form)
    {
        $form->load('fields');

        return view('cms_forms.builder', [
            'form' => $form,
            'fieldTypes' => FormField::TYPES,
            'responseCount' => $form->submissions()->count(),
            'autosave' => $form->autosave ? [
                'data' => $form->autosave,
                'saved_at' => $form->autosaved_at?->toIso8601String(),
                'by' => $form->autosaved_by && $form->autosaved_by !== auth()->id()
                    ? trim((string) \App\Models\User::whereKey($form->autosaved_by)->value('first_name')) ?: 'Someone else'
                    : null,
            ] : null,
        ]);
    }

    /**
     * Save the builder (JSON). With publish=true the form also goes live.
     */
    public function update(Request $request, Form $form)
    {
        $payload = $request->only(['title', 'slug', 'description', 'success_message', 'auto_create_lead', 'design', 'fields']);
        $payload['fields'] ??= [];

        $form = $this->builder->save($form, $payload);
        // The form now holds the designer's work - nothing left to restore.
        $form->forceFill(['autosave' => null, 'autosaved_at' => null, 'autosaved_by' => null])->saveQuietly();

        if ($request->boolean('publish') && ! $form->isPublished()) {
            if ($form->inputFields()->isEmpty()) {
                return response()->json(['message' => 'Add at least one question before publishing.', 'errors' => ['fields' => ['Add at least one question clients can answer before publishing.']]], 422);
            }
            $this->markPublished($form);
        }

        return response()->json([
            'message' => $form->isPublished() ? 'Form saved - changes are live.' : 'Draft saved.',
            'form' => $this->builderPayload($form->fresh('fields')),
        ]);
    }

    /**
     * Designer autosave: keeps the work in progress without touching the form
     * itself, so a live form's public page doesn't change mid-edit and a draft
     * that doesn't validate yet is never lost. Stored cleaned (the designer
     * renders it back into its canvas), cleared by the next real save.
     */
    public function autosave(Request $request, Form $form)
    {
        $request->validate(['fields' => 'nullable|array|max:150', 'design' => 'nullable|array']);

        $str = fn ($v, int $max) => is_scalar($v) ? mb_substr((string) $v, 0, $max) : '';
        $snapshot = [
            'title' => $str($request->input('title'), 255),
            'slug' => $str($request->input('slug'), 120),
            'description' => $str($request->input('description'), 5000),
            'success_message' => $str($request->input('success_message'), 2000),
            'auto_create_lead' => $request->boolean('auto_create_lead'),
            'design' => FormDesign::sanitize((array) $request->input('design', [])),
            'fields' => collect((array) $request->input('fields', []))
                ->filter(fn ($f) => is_array($f) && array_key_exists($f['type'] ?? null, FormField::TYPES))
                ->map(fn (array $f) => [
                    'id' => is_numeric($f['id'] ?? null) ? (int) $f['id'] : null,
                    'type' => $f['type'],
                    'label' => $str($f['label'] ?? '', 255),
                    'name' => $str($f['name'] ?? '', 100),
                    'placeholder' => $str($f['placeholder'] ?? '', 255),
                    'help_text' => $str($f['help_text'] ?? '', 500),
                    'is_required' => filter_var($f['is_required'] ?? false, FILTER_VALIDATE_BOOL),
                    'options' => collect((array) ($f['options'] ?? []))->filter(fn ($o) => is_scalar($o))->map(fn ($o) => $str($o, 255))->take(50)->values()->all(),
                    'settings' => FormDesign::sanitizeField((array) ($f['settings'] ?? [])),
                ])->values()->all(),
        ];

        $form->forceFill(['autosave' => $snapshot, 'autosaved_at' => now(), 'autosaved_by' => auth()->id()])->saveQuietly();

        return response()->json(['saved_at' => $form->autosaved_at->toIso8601String()]);
    }

    public function discardAutosave(Form $form)
    {
        $form->forceFill(['autosave' => null, 'autosaved_at' => null, 'autosaved_by' => null])->saveQuietly();

        return response()->json(['ok' => true]);
    }

    public function preview(Form $form)
    {
        $form->load('fields');

        return view('cms_forms.public', ['form' => $form, 'preview' => true, 'embed' => false]);
    }

    public function publish(Form $form)
    {
        if ($form->fields()->whereNotIn('type', FormField::LAYOUT_TYPES)->doesntExist()) {
            return back()->withErrors(['message' => 'Add at least one question before publishing.']);
        }
        $this->markPublished($form);

        return back()->with('success', "\"{$form->title}\" is published.");
    }

    public function unpublish(Form $form)
    {
        $form->update(['status' => Form::STATUS_DRAFT]);
        Activity::log('form_unpublished', "Form \"{$form->title}\" unpublished", route('cms.forms.edit', $form));

        return back()->with('success', "\"{$form->title}\" is unpublished - its public link no longer accepts responses.");
    }

    public function duplicate(Form $form)
    {
        $form->load('fields');
        $copy = $this->builder->duplicate($form, auth()->id());

        return redirect()->route('cms.forms.edit', $copy)->with('success', 'Form duplicated as a draft.');
    }

    public function destroy(Request $request, Form $form)
    {
        abort_if(auth()->user()->role === 'COORDINATOR', 403, 'Coordinators cannot delete forms.');

        $count = $form->submissions()->count();

        // Deleting a form with responses also deletes what clients submitted,
        // so the confirmation dialog makes staff tick an explicit
        // acknowledgement - checked here too, so a stray request can't skip it.
        if ($count > 0 && ! $request->boolean('confirm_delete_responses')) {
            return back()->withErrors(['message' => "Confirm that the {$count} ".str('response')->plural($count)." should be deleted too before deleting \"{$form->title}\"."]);
        }

        $title = $form->title;
        $files = FormSubmissionValue::whereIn('form_submission_id', $form->submissions()->select('id'))
            ->whereNotNull('file_path')->pluck('file_path');

        DB::transaction(function () use ($form) {
            // Values cascade with their submission; leads created from these
            // responses are separate CRM records and stay in the pipeline.
            $form->submissions()->delete();
            $form->delete();
        });

        Storage::disk(FormSubmissionService::UPLOAD_DISK)->delete($files->all());

        Activity::log('form_deleted', $count
            ? "Form \"{$title}\" deleted with {$count} ".str('response')->plural($count)
            : "Form \"{$title}\" deleted");

        return redirect()->route('cms.forms.index')->with('success', $count
            ? "\"{$title}\" and its {$count} ".str('response')->plural($count).' were deleted.'
            : "\"{$title}\" deleted.");
    }

    private function markPublished(Form $form): void
    {
        $form->update(['status' => Form::STATUS_PUBLISHED, 'published_at' => now()]);
        Activity::log('form_published', "Form \"{$form->title}\" published", route('cms.forms.edit', $form));
    }

    public static function builderPayload(Form $form): array
    {
        return [
            'id' => $form->id,
            'title' => $form->title,
            'slug' => $form->slug,
            'description' => $form->description,
            'success_message' => $form->success_message,
            'auto_create_lead' => $form->auto_create_lead,
            'status' => $form->status,
            'design' => $form->designSettings(),
            'public_url' => $form->publicUrl(),
            'embed_code' => $form->embedCode(),
            'fields' => $form->fields->map(fn (FormField $f) => [
                'id' => $f->id,
                'type' => $f->type,
                'label' => $f->label,
                'name' => $f->name,
                'placeholder' => $f->placeholder,
                'help_text' => $f->help_text,
                'is_required' => $f->is_required,
                'options' => $f->options ?? [],
                'settings' => $f->layout(),
            ])->values(),
        ];
    }
}
