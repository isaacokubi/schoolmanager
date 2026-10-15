<?php

// PRESENTATION NOTE: api.
// This file defines application routes or automated tests. The comments explain
// the purpose of the route/test setup; they do not change request handling or assertions.

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MpesaController;

// Safaricom Daraja sends the result of an STK Push to this callback.
Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');
