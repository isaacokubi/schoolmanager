<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MpesaController;

// Safaricom Daraja sends the result of an STK Push to this callback.
Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');
