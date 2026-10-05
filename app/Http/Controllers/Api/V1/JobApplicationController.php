<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Career\JobApplicationController as WebJobApplicationController;
use App\Models\JobApplication;
use App\Models\JobApplicationNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Job applications for the mobile app. The web actions redirect back to the
 * page, so each one is run as-is and the changed application is returned
 * instead; validation uses the web's own rules first, so a bad request gets
 * a 422 rather than a redirect. The resume download is the web's own action.
 */
class JobApplicationController extends WebJobApplicationController
{
    /** GET /careers/applications — career/applications/index.blade.php's data. */
    public function index(Request $request): JsonResponse
    {
        $d = parent::index($request)->getData();

        return response()->json([
            'applications' => $d['applications']->map(fn (JobApplication $a) => self::row($a))->values(),
            'statuses' => $d['statuses'],
            'status_counts' => (object) $d['statusCounts']->all(),
            'total_count' => $d['totalCount'],
            'new_count' => $d['newCount'],
        ]);
    }

    /** PATCH /careers/applications/{id}/status */
    public function updateStatus(Request $request, JobApplication $jobApplication)
    {
        Validator::make($request->all(), ['status' => 'required|string|in:new,reviewed,interviewing,hired,rejected'])->validate();
        parent::updateStatus($request, $jobApplication);
        $fresh = $jobApplication->fresh(['notesLog.user']);

        return response()->json(['message' => "Status set to {$fresh->status_label}.", 'application' => self::row($fresh)]);
    }

    /** POST /careers/applications/{id}/notes */
    public function addNote(Request $request, JobApplication $jobApplication)
    {
        Validator::make($request->all(), ['body' => 'required|string|max:2000'])->validate();
        parent::addNote($request, $jobApplication);

        return response()->json(['message' => 'Note added.', 'application' => self::row($jobApplication->fresh(['notesLog.user']))], 201);
    }

    /** DELETE /careers/applications/{id} — also removes the stored resume. */
    public function destroy(JobApplication $jobApplication)
    {
        parent::destroy($jobApplication);

        return response()->json(['message' => 'Application deleted successfully!']);
    }

    /** One application as the list and the detail panel show it. */
    public static function row(JobApplication $a): array
    {
        return [
            'id' => $a->id,
            'job_posting_id' => $a->job_posting_id,
            'job_title' => $a->job_title,
            'first_name' => $a->first_name,
            'last_name' => $a->last_name,
            'full_name' => $a->full_name,
            'email' => $a->email,
            'years_experience' => $a->years_experience,
            'cover_letter' => $a->cover_letter,
            'status' => $a->status,
            'status_label' => $a->status_label,
            'has_resume' => (bool) $a->resume_path,
            'resume_name' => $a->resume_path ? ($a->resume_original_name ?: 'resume.pdf') : null,
            'created_at' => $a->created_at?->toIso8601String(),
            'notes' => $a->notesLog->map(fn (JobApplicationNote $n) => [
                'id' => $n->id,
                'body' => $n->body,
                'author_name' => $n->author_name,
                'created_at' => $n->created_at?->toIso8601String(),
            ])->values(),
        ];
    }
}
