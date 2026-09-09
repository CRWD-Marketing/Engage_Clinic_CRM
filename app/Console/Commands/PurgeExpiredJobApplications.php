<?php

namespace App\Console\Commands;

use App\Models\JobApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * HR data-retention policy: job applications (including the stored resume
 * file) are kept for 2 months after submission, then purged entirely.
 */
class PurgeExpiredJobApplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'careers:purge-applications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete job applications (and their resumes) older than 2 months';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expired = JobApplication::where('created_at', '<=', now()->subMonths(2))->get();

        foreach ($expired as $application) {
            if ($application->resume_path) {
                Storage::disk('local')->delete($application->resume_path);
            }

            $application->delete();
        }

        $this->info("Purged {$expired->count()} expired job application(s).");

        return self::SUCCESS;
    }
}
