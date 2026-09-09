<?php

namespace App\Http\Controllers\Career;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class JobApplicationController extends Controller
{
    /**
     * Display a listing of applications, with a right-hand detail panel -
     * mirrors ContactController::index.
     */
    public function index(Request $request)
    {
        $allApplications = JobApplication::with(['jobPosting', 'notesLog.user'])->latest()->get();

        $status = $request->query('status');
        $applications = ($status && $status !== 'all')
            ? $allApplications->where('status', $status)->values()
            : $allApplications;

        $activeApplication = $request->filled('application')
            ? $allApplications->firstWhere('id', (int) $request->query('application'))
            : $applications->first();

        $statuses = JobApplication::getStatuses();
        $newCount = $allApplications->where('status', JobApplication::STATUS_NEW)->count();

        return view('career.applications.index', compact('applications', 'activeApplication', 'statuses', 'newCount'));
    }

    /**
     * Update an application's status.
     */
    public function updateStatus(Request $request, JobApplication $jobApplication)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:new,reviewed,interviewing,hired,rejected',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $jobApplication->update(['status' => $request->status]);

        return redirect()->route('job-applications.index', ['application' => $jobApplication->id]);
    }

    /**
     * Append a timestamped note to an application's internal notes log.
     */
    public function addNote(Request $request, JobApplication $jobApplication)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $jobApplication->notesLog()->create([
            'user_id' => auth()->id(),
            'body' => $request->body,
        ]);

        return redirect()->route('job-applications.index', ['application' => $jobApplication->id]);
    }

    /**
     * Delete an application and its stored resume.
     */
    public function destroy(JobApplication $jobApplication)
    {
        if ($jobApplication->resume_path) {
            Storage::disk('local')->delete($jobApplication->resume_path);
        }

        $jobApplication->delete();

        return redirect()
            ->route('job-applications.index')
            ->with('success', 'Application deleted successfully!');
    }

    /**
     * View an applicant's resume inline in the browser (e.g. a PDF opens in
     * a new tab instead of forcing a download).
     */
    public function viewResume(JobApplication $jobApplication)
    {
        abort_unless($jobApplication->resume_path && Storage::disk('local')->exists($jobApplication->resume_path), 404);

        return Storage::disk('local')->response(
            $jobApplication->resume_path,
            $jobApplication->resume_original_name ?: 'resume.pdf'
        );
    }

    /**
     * Download an applicant's resume. The file lives on the private "local"
     * disk (not web-accessible), so this admin-gated route is the only way
     * to retrieve it.
     */
    public function downloadResume(JobApplication $jobApplication)
    {
        abort_unless($jobApplication->resume_path && Storage::disk('local')->exists($jobApplication->resume_path), 404);

        return Storage::disk('local')->download(
            $jobApplication->resume_path,
            $jobApplication->resume_original_name ?: 'resume.pdf'
        );
    }

    /**
     * Get new-application count for the sidebar badge.
     */
    public function getApplicationCount()
    {
        $count = JobApplication::where('status', JobApplication::STATUS_NEW)->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Store an application from the public careers page.
     */
    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_posting_id' => 'required|exists:job_postings,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'years_experience' => 'nullable|string|max:50',
            'cover_letter' => 'nullable|string|max:5000',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $jobPosting = JobPosting::findOrFail($data['job_posting_id']);

        $resumeFile = $request->file('resume');
        $resumeOriginalName = $resumeFile->getClientOriginalName();
        $resumePath = $resumeFile->storeAs(
            'resumes',
            (string) Str::uuid().'.'.$resumeFile->getClientOriginalExtension(),
            'local'
        );

        $application = JobApplication::create([
            'job_posting_id' => $jobPosting->id,
            'job_title' => $jobPosting->title,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'years_experience' => $data['years_experience'] ?? null,
            'cover_letter' => $data['cover_letter'] ?? null,
            'resume_path' => $resumePath,
            'resume_original_name' => $resumeOriginalName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application received successfully!',
            'application' => $application,
        ], 201);
    }
}
