<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MpesaController;

Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');

/*
 * Protected queue endpoint for an external scheduler such as Vercel Cron.
 * The endpoint can execute application jobs, so require CRON_SECRET.
 */
Route::get('/cron/queue', function (Request $request) {
    $secret = trim((string) env('CRON_SECRET', ''));

    if ($secret === '') {
        return response()->json([
            'ok' => false,
            'message' => 'Cron endpoint is not configured.',
        ], 503);
    }

    $provided = trim((string) $request->header('X-Cron-Secret', ''));

    if ($provided === '') {
        $authorization = trim((string) $request->header('Authorization', ''));

        if (strpos($authorization, 'Bearer ') === 0) {
            $provided = trim(substr($authorization, 7));
        }
    }

    if ($provided === '' || !hash_equals($secret, $provided)) {
        return response()->json([
            'ok' => false,
            'message' => 'Unauthorized.',
        ], 401);
    }

    $exitCode = Artisan::call('queue:work', [
        '--once' => true,
        '--stop-when-empty' => true,
    ]);

    return response()->json([
        'ok' => $exitCode === 0,
        'message' => $exitCode === 0
            ? 'Queue worker completed.'
            : 'Queue worker exited with an error.',
        'exit_code' => $exitCode,
        'output' => trim(Artisan::output()),
    ], $exitCode === 0 ? 200 : 500);
})->name('cron.queue');
