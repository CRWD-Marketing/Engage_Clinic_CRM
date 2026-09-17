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

// Drains the queue (AI Employee replies, etc.) every minute via the same cron
// tick that runs the scheduler - shared hosting (Hostinger) generally can't
// keep a persistent `queue:work` daemon alive, so jobs otherwise dispatch
// successfully and just sit in `jobs` forever with nothing to process them.
// --stop-when-empty exits once the queue is drained instead of idling and
// blocking next minute's tick; --max-time is a hard backstop so a run can
// never bump into the next one even if something hangs.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// Failed queue jobs (e.g. AI Employee replies that exhausted their retries)
// accumulate in failed_jobs with no automatic cleanup otherwise - unbounded
// over the life of the app. Keeps 30 days for debugging, prunes older.
Schedule::command('queue:prune-failed-jobs --hours=720')->daily();
