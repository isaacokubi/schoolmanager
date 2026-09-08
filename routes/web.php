<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\Admin\AdmissionManagementController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OperationsController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MpesaController;

Route::view('/', 'home')->name('home');
Route::view('/about', 'pages.about')->name('about');
Route::view('/academics', 'pages.academics')->name('academics');
Route::view('/admissions', 'pages.admissions')->name('admissions');
Route::post('/admissions', [AdmissionController::class, 'store'])->name('admissions.store');
Route::view('/contact', 'pages.contact')->name('contact');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware(['auth', 'admin.role'])->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/reports', [ReportsController::class, 'index'])->name('admin.reports');
    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
    Route::get('/students', [StudentController::class, 'index'])->name('admin.students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->name('admin.students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('admin.students.store');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('admin.students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('admin.students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('admin.students.destroy');
    Route::get('/admissions', [AdmissionManagementController::class, 'index'])->name('admin.admissions.index');
    Route::patch('/admissions/{application}/status', [AdmissionManagementController::class, 'updateStatus'])->name('admin.admissions.status');
    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/payments/create', [PaymentController::class, 'create'])->name('admin.payments.create');
    Route::post('/payments', [PaymentController::class, 'store'])->name('admin.payments.store');
    Route::post('/payments/mpesa', [MpesaController::class, 'stkPush'])->name('admin.payments.mpesa');
    Route::get('/operations', [OperationsController::class, 'index'])->name('admin.operations');
    Route::post('/operations', [OperationsController::class, 'store'])->name('admin.operations.store');
    Route::put('/operations/{id}', [OperationsController::class, 'update'])->name('admin.operations.update');
    Route::delete('/operations/{id}', [OperationsController::class, 'destroy'])->name('admin.operations.destroy');
    Route::post('/operations/attendance', [OperationsController::class, 'attendance'])->name('admin.operations.attendance');
    Route::post('/operations/results', [OperationsController::class, 'result'])->name('admin.operations.results');
});
