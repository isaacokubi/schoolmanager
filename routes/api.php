<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\VercelCronController;

Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');
Route::get('/cron/queue', [VercelCronController::class, 'queue'])->name('cron.queue');
