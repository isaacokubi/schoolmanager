<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MpesaController;

Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');
