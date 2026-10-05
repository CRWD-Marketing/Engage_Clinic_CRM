<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Career\JobPostingController as WebJobPostingController;
use App\Models\JobPosting;
use Illuminate\Http\Request;

/**
 * Job postings (the careers page CMS) for the mobile app, using the web
 * controller's own validation and data preparation. The web actions answer
 * with a redirect and a flash message; these answer with that message and
 * the posting.
 */
class JobPostingController extends WebJobPostingController
{
    /** GET /careers/postings — newest first, with how many applied to each. */
    public function index()
    {
        return response()->json([
            'postings' => JobPosting::withCount('applications')->orderByDesc('created_at')->get()->map(fn (JobPosting $p) => self::row($p))->values(),
            'statuses' => JobPosting::getStatuses(),
        ]);
    }

    /** POST /careers/postings */
    public function store(Request $request)
    {
        $posting = JobPosting::create($this->preparePostingData($this->validatePosting($request)->validate()));

        return response()->json(['message' => 'Job posting created successfully!', 'posting' => self::row($posting->loadCount('applications'))], 201);
    }

    /** PUT /careers/postings/{id} */
    public function update(Request $request, JobPosting $job_posting)
    {
        $job_posting->update($this->preparePostingData($this->validatePosting($request)->validate()));

        return response()->json(['message' => 'Job posting updated successfully!', 'posting' => self::row($job_posting->fresh()->loadCount('applications'))]);
    }

    /** DELETE /careers/postings/{id} — applications keep their job title. */
    public function destroy(JobPosting $job_posting)
    {
        parent::destroy($job_posting);

        return response()->json(['message' => 'Job posting deleted successfully!']);
    }

    public static function row(JobPosting $p): array
    {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'employment_type' => $p->employment_type,
            'location' => $p->location,
            'description' => $p->description,
            'requirements' => array_values($p->requirements ?? []),
            'status' => $p->status,
            'applications_count' => (int) ($p->applications_count ?? $p->applications()->count()),
            'created_at' => $p->created_at?->toIso8601String(),
        ];
    }
}
