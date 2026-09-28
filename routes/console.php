<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes / Scheduled Tasks
|--------------------------------------------------------------------------
|
| Requires the server cron to run Laravel's scheduler every minute:
| * * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
|
*/

Schedule::command('careers:purge-applications')->daily();

// The Supervisor only ever touches a session *before* it happens (reschedule,
// cancel, mark a no-show) - nothing left "scheduled" once its time passes is
// meant to be logged as attended manually, this just catches the status up.
Schedule::command('calendar:auto-complete-sessions')->everyFifteenMinutes();

// Drains the queue (AI Employee replies, etc.) via the same cron tick that
// runs the scheduler - shared hosting (Hostinger) generally can't keep a
// persistent `queue:work` daemon alive, so jobs otherwise dispatch
// successfully and just sit in `jobs` forever with nothing to process them.
// A plain `queue:work --stop-when-empty` here would only drain once, right
// at the start of the minute, then exit - a job that becomes ready to run
// later in that same minute (e.g. an AI Employee reply whose short
// response_delay_seconds elapses at :35s) would then sit until the *next*
// minute's tick, up to ~60s late on top of its own configured delay.
// queue:work-tight re-drains every ~10s for the rest of the minute instead,
// so a ready job is picked up within ~10s, not up to 60.
Schedule::command('queue:work-tight --interval=10 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

// Failed queue jobs (e.g. AI Employee replies that exhausted their retries)
// accumulate in failed_jobs with no automatic cleanup otherwise - unbounded
// over the life of the app. Keeps 30 days for debugging, prunes older.
Schedule::command('queue:prune-failed-jobs --hours=720')->daily();
