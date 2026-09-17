<?php

namespace App\Console\Commands;

use App\Models\CalendarSession;
use Illuminate\Console\Command;

/**
 * Attendance isn't a manual logging step in this clinic's process - the
 * Clinical Supervisor owns the calendar and only ever acts on a session
 * *before* it happens (reschedule, cancel, mark a no-show). Once a
 * scheduled session's time passes with nothing changed, it counts as
 * attended, so this just catches up the status to match.
 */
class AutoCompletePastSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:auto-complete-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark past-due "scheduled" calendar sessions as completed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = CalendarSession::pastDueScheduled()->update(['status' => 'completed']);

        $this->info("Marked {$count} past-due session(s) as completed.");

        return self::SUCCESS;
    }
}
