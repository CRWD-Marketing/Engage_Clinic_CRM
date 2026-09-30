<?php

namespace App\Http\Controllers\CmsForm;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormSubmissionValue;
use App\Models\User;
use App\Services\CmsForms\ConversionException;
use App\Services\CmsForms\FormSubmissionService;
use App\Services\CmsForms\LeadConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CMS -> Forms -> [form] -> Responses: what clients actually submitted.
 */
class FormSubmissionController extends Controller
{
    public function __construct(private LeadConversionService $conversion)
    {
    }

    public function index(Request $request, Form $form)
    {
        $filters = $this->validatedFilters($request);

        $submissions = $this->filteredQuery($form, $filters)
            ->with(['values', 'form'])
            ->latest('submitted_at')->latest('id')
            ->paginate(20)->withQueryString();
        $submissions->getCollection()->each(function (FormSubmission $s) {
            $identity = $this->conversion->identity($s);
            $s->setAttribute('identity', $identity);
            $s->setAttribute('preview', $this->preview($s, $identity));
        });

        $all = $form->submissions()->toBase()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $statusCounts = collect(FormSubmission::getStatuses())->mapWithKeys(fn ($label, $key) => [$key => (int) ($all[$key] ?? 0)])->all();
        $total = array_sum($statusCounts);

        return view('cms_forms.responses', [
            'form' => $form,
            'submissions' => $submissions,
            'filters' => $filters + ['status' => 'all'],
            'statuses' => FormSubmission::getStatuses(),
            'statusCounts' => $statusCounts,
            'totalCount' => $total,
            'stats' => [
                'this_week' => $form->submissions()->where('submitted_at', '>=', now()->subDays(7))->count(),
                'conversion_rate' => $total ? (int) round($statusCounts[FormSubmission::STATUS_CONVERTED] / $total * 100) : 0,
                'last_at' => $form->submissions()->max('submitted_at'),
            ],
        ]);
    }

    /**
     * Every response matching the current filters as a CSV, one column per
     * question (by the label the client saw when they answered).
     */
    public function export(Request $request, Form $form)
    {
        $filters = $this->validatedFilters($request);
        $submissions = $this->filteredQuery($form, $filters)->with('values')->latest('submitted_at')->latest('id')->get();

        $columns = $submissions->flatMap->values
            ->sortBy('sort_order')
            ->unique('field_name')
            ->mapWithKeys(fn (FormSubmissionValue $v) => [$v->field_name => $v->field_label]);

        $filename = Str::slug($form->title ?: 'form').'-responses-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($submissions, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8 names correctly
            fputcsv($out, array_merge(['Response #', 'Submitted', 'Status', 'Lead'], $columns->values()->all()));
            foreach ($submissions as $s) {
                $answers = $s->values->keyBy('field_name');
                fputcsv($out, array_merge(
                    [$s->id, $s->submitted_at->format('Y-m-d H:i'), $s->status_label, $s->lead_id ? LeadConversionService::reference($s->lead_id) : ''],
                    $columns->keys()->map(fn ($name) => $this->csvSafe($answers->get($name)?->displayText() ?? ''))->all(),
                ));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(FormSubmission $submission)
    {
        $submission->load(['form.fields', 'values', 'lead', 'convertedByUser', 'reviewedByUser']);
        $user = auth()->user();
        $siblings = FormSubmission::where('form_id', $submission->form_id);

        return view('cms_forms.response', [
            'submission' => $submission,
            'form' => $submission->form,
            'identity' => $this->conversion->identity($submission),
            'sections' => $this->sections($submission),
            'device' => $this->device($submission->user_agent),
            'leadDefaults' => $this->conversion->defaults($submission),
            'leadRef' => $submission->lead_id ? LeadConversionService::reference($submission->lead_id) : null,
            'canConvert' => $this->canConvert($user),
            'canAssign' => $user->canDo('assign_lead_owner'),
            'assignableUsers' => User::where('is_active', true)->whereIn('role', ['SALES_STAFF', 'FULL_ADMIN'])->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'services' => \App\Models\Service::where('is_active', true)->orderBy('name')->pluck('name'),
            'neighbours' => [
                'newer' => (clone $siblings)->where('id', '>', $submission->id)->min('id'),
                'older' => (clone $siblings)->where('id', '<', $submission->id)->max('id'),
            ],
            'position' => [
                'index' => (clone $siblings)->where('id', '>', $submission->id)->count() + 1,
                'total' => (clone $siblings)->count(),
            ],
        ]);
    }

    public function updateStatus(Request $request, FormSubmission $submission)
    {
        $request->validate([
            'status' => 'required|string|in:'.implode(',', array_keys(FormSubmission::getSelectableStatuses())),
        ]);

        if ($submission->isConverted()) {
            return back()->withErrors(['message' => 'This response has already been converted to a lead.']);
        }

        $updates = ['status' => $request->status];
        if ($request->status === FormSubmission::STATUS_REVIEWED) {
            $updates += ['reviewed_at' => now(), 'reviewed_by' => auth()->id()];
        }
        $submission->update($updates);

        return back()->with('success', 'Marked as '.strtolower($submission->status_label).'.');
    }

    public function convertToLead(Request $request, FormSubmission $submission)
    {
        $user = auth()->user();
        abort_unless($this->canConvert($user), 403, 'Your access level can’t create leads.');

        $input = $request->only(array_keys($this->conversion->rules()));
        if (! empty($input['assigned_to']) && ! $user->canDo('assign_lead_owner')) {
            return back()->withErrors(['assigned_to' => 'Your access level can’t assign a lead owner.'])->withInput();
        }

        try {
            $lead = $this->conversion->convert($submission, $input, $user);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (ConversionException $e) {
            return redirect()->route('cms.forms.responses.show', $submission)->withErrors(['message' => $e->getMessage()]);
        }

        return redirect()
            ->route('cms.forms.responses.show', $submission)
            ->with('converted', LeadConversionService::reference($lead));
    }

    public function downloadFile(FormSubmission $submission, FormSubmissionValue $value)
    {
        abort_unless($value->form_submission_id === $submission->id && $value->file_path, 404);
        abort_unless(Storage::disk(FormSubmissionService::UPLOAD_DISK)->exists($value->file_path), 404, 'File not found.');

        return Storage::disk(FormSubmissionService::UPLOAD_DISK)->download($value->file_path, $value->file_original_name ?: 'upload');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => 'nullable|string|max:200',
            'status' => 'nullable|string|in:all,'.implode(',', array_keys(FormSubmission::getStatuses())),
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
        ]);
    }

    private function filteredQuery(Form $form, array $filters)
    {
        $query = $form->submissions();

        if (($filters['status'] ?? 'all') !== 'all') {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['from'])) {
            $query->where('submitted_at', '>=', $filters['from'].' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $query->where('submitted_at', '<=', $filters['to'].' 23:59:59');
        }
        if (filled($filters['search'] ?? null)) {
            $search = trim($filters['search']);
            $id = (int) ltrim($search, '#');
            $query->where(function ($q) use ($search, $id) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $q->whereHas('values', fn ($v) => $v->where(fn ($w) => $w->where('value', 'LIKE', $like)->orWhere('file_original_name', 'LIKE', $like)));
                if ($id > 0) {
                    $q->orWhere('id', $id);
                }
            });
        }

        return $query;
    }

    /**
     * A couple of answers beyond name/email/phone, so the table says what
     * each response is about without opening it.
     */
    private function preview(FormSubmission $submission, array $identity): array
    {
        return $submission->values
            ->reject(fn (FormSubmissionValue $v) => in_array($v->field_type, ['email', 'phone', 'file', 'consent'], true) || $v->isEmpty())
            ->reject(fn (FormSubmissionValue $v) => in_array(trim($v->displayText()), [$identity['name'], $identity['email']], true))
            ->take(2)
            ->map(fn (FormSubmissionValue $v) => ['label' => $v->field_label, 'text' => Str::limit(preg_replace('/\s+/', ' ', $v->displayText()), 70)])
            ->values()->all();
    }

    /**
     * The answers grouped under the form's section headings. Each answer goes
     * under the last heading placed before it; answers before any heading
     * form an untitled first group.
     */
    private function sections(FormSubmission $submission): array
    {
        $headings = $submission->form->fields->where('type', 'section')->sortBy('sort_order')->values();
        $groups = [];

        foreach ($submission->values as $value) {
            $heading = $headings->last(fn ($h) => $h->sort_order < $value->sort_order);
            $key = $heading?->id ?? 0;
            $groups[$key] ??= ['title' => $heading?->label, 'values' => []];
            $groups[$key]['values'][] = $value;
        }

        return array_values($groups);
    }

    /**
     * "Chrome on Windows" from a user-agent string - enough for staff to spot
     * a phone vs desktop submission, no parsing library needed.
     */
    private function device(?string $ua): ?array
    {
        if (! $ua) {
            return null;
        }

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Browser',
        };
        [$os, $mobile] = match (true) {
            str_contains($ua, 'iPhone') => ['iPhone', true],
            str_contains($ua, 'iPad') => ['iPad', true],
            str_contains($ua, 'Android') => ['Android', str_contains($ua, 'Mobile')],
            str_contains($ua, 'Windows') => ['Windows', false],
            str_contains($ua, 'Mac OS X') => ['macOS', false],
            str_contains($ua, 'Linux') => ['Linux', false],
            default => [null, false],
        };

        return [
            'label' => $os ? "{$browser} on {$os}" : $browser,
            'icon' => $mobile ? 'fa-mobile-screen' : 'fa-desktop',
        ];
    }

    /**
     * Stops a client's answer being run as a formula when the CSV is opened
     * in Excel or Sheets.
     */
    private function csvSafe(string $text): string
    {
        return preg_match('/^[=+\-@\t\r]/', $text) ? "'".$text : $text;
    }

    /**
     * Creating a lead is a Leads-module action: needs editable Leads access,
     * and Coordinators are excluded the same way Contacts -> Convert to Lead is.
     */
    private function canConvert(User $user): bool
    {
        return $user->canAccessFeature('leads')
            && $user->levelFor('leads') !== 'view'
            && $user->role !== 'COORDINATOR';
    }
}
