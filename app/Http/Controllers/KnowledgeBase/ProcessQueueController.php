<?php

namespace App\Http\Controllers\KnowledgeBase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Lets an external scheduler (e.g. a free cron-ping service like
 * cron-job.org) drive the AI Employee's delayed queue on hosting plans with
 * no server-side cron/persistent worker access. Hitting this URL runs
 * `queue:work --stop-when-empty`, draining whatever delayed jobs have come
 * due, then exits - functionally the same as a real cron job, just
 * triggered by an external HTTP ping instead of the hosting panel.
 *
 * Only makes sense with QUEUE_CONNECTION=database (or another real queue
 * driver) - under `sync`, jobs already run immediately on dispatch and this
 * endpoint has nothing queued to do.
 */
class ProcessQueueController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = config('ai_employee.cron_secret');

        if (! $secret || ! hash_equals($secret, (string) $request->query('token'))) {
            abort(403);
        }

        Log::info('AI Employee queue processor triggered externally.');

        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 50,
            '--tries' => 3,
        ]);

        return response()->json(['status' => 'processed']);
    }
}
