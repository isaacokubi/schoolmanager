<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class VercelCronController extends Controller
{
    /**
     * Process a bounded batch of Laravel database jobs when invoked by a
     * trusted scheduler. Vercel sends CRON_SECRET as a Bearer token.
     */
    public function queue(Request $request)
    {
        $secret = trim((string) env('CRON_SECRET'));
        $provided = trim((string) $request->bearerToken());

        if ($secret === '' || !hash_equals($secret, $provided)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $lock = Cache::lock('schoolmanager:vercel:queue-worker', 110);

        if (!$lock->get()) {
            return response()->json([
                'ok' => true,
                'status' => 'already_running',
            ], 202);
        }

        try {
            Artisan::call('queue:work', [
                'connection' => 'database',
                '--queue' => 'default',
                '--stop-when-empty' => true,
                '--max-jobs' => 10,
                '--timeout' => 60,
                '--tries' => 3,
                '--sleep' => 1,
            ]);

            return response()->json([
                'ok' => true,
                'status' => 'processed',
                'output' => trim(Artisan::output()),
            ]);
        } finally {
            $lock->release();
        }
    }
}
