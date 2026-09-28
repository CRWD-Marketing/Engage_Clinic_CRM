<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Shared hosting (Hostinger) can't keep a persistent `queue:work` daemon
 * alive, so the scheduler drains the queue via a once-a-minute cron tick
 * instead (see routes/console.php). A single drain at the start of that
 * minute means a job that becomes ready to run just after the tick - e.g.
 * an AI Employee reply whose response_delay_seconds just elapsed - can sit
 * for up to ~60 seconds before the next tick picks it up. Re-draining every
 * few seconds for the rest of the minute keeps that wait down to roughly
 * the interval below, without needing a real background daemon.
 */
class QueueWorkTight extends Command
{
    protected $signature = 'queue:work-tight
                            {--interval=10 : Seconds to wait between drain passes}
                            {--max-time=50 : Total seconds to keep re-draining before exiting}';

    protected $description = 'Repeatedly drain the queue every few seconds for a window, instead of once';

    public function handle(): int
    {
        $interval = max(1, (int) $this->option('interval'));
        $deadline = now()->addSeconds((int) $this->option('max-time'));

        do {
            $this->call('queue:work', [
                '--stop-when-empty' => true,
                '--tries' => 3,
            ]);

            if (now()->greaterThanOrEqualTo($deadline)) {
                break;
            }

            sleep($interval);
        } while (now()->lessThan($deadline));

        return self::SUCCESS;
    }
}
