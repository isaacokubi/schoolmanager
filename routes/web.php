<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthController;

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

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
