<?php

namespace App\Http\Controllers\Career;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobPostingController extends Controller
{
    /**
     * Display a listing of job postings (CMS).
     */
    public function index()
    {
        $postings = JobPosting::orderByDesc('created_at')->get();

        return view('career.postings.index', compact('postings'));
    }

    /**
     * Show create posting form.
     */
    public function create()
    {
        return view('career.postings.create');
    }

    /**
     * Store new posting.
     */
    public function store(Request $request)
    {
        $validator = $this->validatePosting($request);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        JobPosting::create($this->preparePostingData($validator->validated()));

        return redirect()
            ->route('job-postings.index')
            ->with('success', 'Job posting created successfully!');
    }

    /**
     * Show edit posting form.
     */
    public function edit(JobPosting $job_posting)
    {
        return view('career.postings.edit', ['posting' => $job_posting]);
    }

    /**
     * Update posting.
     */
    public function update(Request $request, JobPosting $job_posting)
    {
        $validator = $this->validatePosting($request);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $job_posting->update($this->preparePostingData($validator->validated()));

        return redirect()
            ->route('job-postings.index')
            ->with('success', 'Job posting updated successfully!');
    }

    /**
     * Delete posting. Applications keep a job_title snapshot, so they remain
     * meaningful even after their posting is removed.
     */
    public function destroy(JobPosting $job_posting)
    {
        $job_posting->delete();

        return redirect()
            ->route('job-postings.index')
            ->with('success', 'Job posting deleted successfully!');
    }

    /**
     * Public careers page listing - only active postings, newest first.
     */
    public function publicIndex()
    {
        $jobPostings = JobPosting::active()->orderByDesc('created_at')->get();

        return view('landing_page.careers', compact('jobPostings'));
    }

    /**
     * Shared validation for store/update.
     */
    private function validatePosting(Request $request)
    {
        return Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'employment_type' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'nullable|array',
            'requirements.*' => 'nullable|string|max:255',
            'status' => 'required|string|in:active,inactive',
        ]);
    }

    /**
     * Requirements arrive from the form as one input per requirement (added
     * one at a time via the "+ Add requirement" repeater); store them as a
     * clean JSON array of non-empty, trimmed values.
     */
    private function preparePostingData(array $data): array
    {
        $data['requirements'] = collect($data['requirements'] ?? [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        return $data;
    }
}
