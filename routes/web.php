<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\PaymentController;

/*
|--------------------------------------------------------------------------
| Small project route map
|--------------------------------------------------------------------------
| The application demonstrates only two school features:
| 1. Student records.
| 2. School-fee collection using Safaricom M-Pesa STK Push.
|
| Login is kept only to protect the administrator pages.
*/

Route::get('/', function () {
    // Send visitors to login, and signed-in staff straight to student records.
    return redirect()->route(auth()->check() ? 'admin.students.index' : 'login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.submit');
});

Route::middleware(['auth', 'admin.role'])->prefix('admin')->group(function () {
    // Keep the old dashboard URL as a simple redirect, not a third feature.
    Route::get('/', function () {
        return redirect()->route('admin.students.index');
    })->name('admin.dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Feature 1: add, list, edit and archive student records.
    Route::get('/students', [StudentController::class, 'index'])->name('admin.students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->name('admin.students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('admin.students.store');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('admin.students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('admin.students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('admin.students.destroy');

    // Feature 2: review payment history and send an M-Pesa STK prompt.
    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/payments/create', [PaymentController::class, 'create'])->name('admin.payments.create');
    Route::post('/payments/mpesa', [MpesaController::class, 'stkPush'])
        ->middleware('throttle:5,1')
        ->name('admin.payments.mpesa');
    Route::post('/payments/mpesa/query/{payment}', [MpesaController::class, 'query'])
        ->middleware('throttle:5,1')
        ->name('admin.payments.mpesa.query');
});
