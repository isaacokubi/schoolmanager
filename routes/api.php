<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MpesaController;

Route::get('/health', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'timestamp' => now()->toIso8601String(),
        ], 200);
    } catch (\Throwable $e) {
        report($e);

        return response()->json([
            'status' => 'degraded',
            'app' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
        ], 503);
    }
})->name('health');

Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');
