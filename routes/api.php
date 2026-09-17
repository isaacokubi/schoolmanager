<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MpesaController;

Route::get('/health', HealthController::class)->name('health');

Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');
