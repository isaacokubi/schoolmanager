<?php

use Illuminate\Support\Facades\Route;

Route::post('/mpesa/callback', function () {
    return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
})->name('mpesa.callback');
