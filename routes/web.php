<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\Admin\AdminSearchController;
use App\Http\Controllers\Admin\AdmissionManagementController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OperationsController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SchoolMediaController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PortalPaymentController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\TeacherAssessmentController;
use App\Http\Controllers\TeacherPortalController;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/about', [PublicController::class, 'about'])->name('about');
Route::get('/academics', [PublicController::class, 'academics'])->name('academics');
Route::get('/admissions', [PublicController::class, 'admissions'])->name('admissions');
Route::post('/admissions', [AdmissionController::class, 'store'])->middleware('throttle:10,1')->name('admissions.store');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');

// Public media endpoint. Keep /storage/{path} compatible with existing records.
// Images are returned explicitly as binary inline responses. Videos retain
// explicit HTTP Range support for browser playback and seeking.
Route::get('/storage/{path}', function (Request $request, string $path) {
    $disk = Storage::disk('public');

    if (!$disk->exists($path)) {
        abort(404);
    }

    $driver = strtolower((string) config('filesystems.disks.public.driver', 'local'));

    if ($driver !== 'local') {
        return redirect()->away($disk->url($path));
    }

    try {
        $absolutePath = $disk->path($path);
        $mime = strtolower((string) ($disk->mimeType($path) ?: 'application/octet-stream'));
        $size = filesize($absolutePath);
    } catch (\Throwable $e) {
        abort(404);
    }

    if (!is_file($absolutePath) || !is_readable($absolutePath) || $size === false) {
        abort(404);
    }

    $size = (int) $size;

    // Image assets must be sent directly as their real binary content. This
    // avoids the browser interpreting an error/redirect payload as an image.
    if (str_starts_with($mime, 'image/')) {
        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Length' => (string) $size,
            'Content-Disposition' => 'inline; filename="' . basename($absolutePath) . '"',
            'Cache-Control' => 'public, max-age=86400, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    $lastModified = filemtime($absolutePath);
    $etag = sprintf('"%s-%s"', dechex($size), dechex($lastModified ?: 0));
    $commonHeaders = [
        'Content-Type' => $mime,
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'public, max-age=86400, must-revalidate',
        'ETag' => $etag,
        'X-Content-Type-Options' => 'nosniff',
    ];

    if ($request->isMethod('HEAD')) {
        return response('', 200, array_merge($commonHeaders, [
            'Content-Length' => (string) $size,
        ]));
    }

    $rangeHeader = trim((string) $request->header('Range', ''));

    if ($rangeHeader === '') {
        return response()->stream(function () use ($absolutePath): void {
            $handle = fopen($absolutePath, 'rb');

            if ($handle === false) {
                return;
            }

            try {
                while (!feof($handle)) {
                    echo fread($handle, 1024 * 1024);
                    flush();
                }
            } finally {
                fclose($handle);
            }
        }, 200, array_merge($commonHeaders, [
            'Content-Length' => (string) $size,
        ]));
    }

    if (!preg_match('/^bytes=(\d*)-(\d*)$/', $rangeHeader, $matches)) {
        return response('', 416, array_merge($commonHeaders, [
            'Content-Range' => "bytes */{$size}",
        ]));
    }

    $start = $matches[1] === '' ? null : (int) $matches[1];
    $end = $matches[2] === '' ? null : (int) $matches[2];

    if ($start === null) {
        $suffixLength = $end ?? 0;

        if ($suffixLength <= 0) {
            return response('', 416, array_merge($commonHeaders, [
                'Content-Range' => "bytes */{$size}",
            ]));
        }

        $suffixLength = min($suffixLength, $size);
        $start = $size - $suffixLength;
        $end = $size - 1;
    } else {
        if ($start >= $size) {
            return response('', 416, array_merge($commonHeaders, [
                'Content-Range' => "bytes */{$size}",
            ]));
        }

        $end = $end === null ? $size - 1 : min($end, $size - 1);

        if ($end < $start) {
            return response('', 416, array_merge($commonHeaders, [
                'Content-Range' => "bytes */{$size}",
            ]));
        }
    }

    $length = $end - $start + 1;

    return response()->stream(function () use ($absolutePath, $start, $length): void {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            return;
        }

        try {
            fseek($handle, $start);
            $remaining = $length;

            while ($remaining > 0 && !feof($handle)) {
                $chunk = fread($handle, min(1024 * 1024, $remaining));

                if ($chunk === false || $chunk === '') {
                    break;
                }

                echo $chunk;
                $remaining -= strlen($chunk);
                flush();
            }
        } finally {
            fclose($handle);
        }
    }, 206, array_merge($commonHeaders, [
        'Content-Length' => (string) $length,
        'Content-Range' => "bytes {$start}-{$end}/{$size}",
    ]));
})->where('path', '.*')->name('media.file');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::get('/register', [PortalAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [PortalAuthController::class, 'register'])->middleware('throttle:5,1')->name('register.submit');
});

Route::middleware(['auth', 'admin.role'])->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/search', AdminSearchController::class)->name('admin.search');
    Route::get('/reports', [ReportsController::class, 'index'])->name('admin.reports');
    Route::get('/students', [StudentController::class, 'index'])->name('admin.students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->name('admin.students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('admin.students.store');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('admin.students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('admin.students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('admin.students.destroy');
    Route::get('/admissions', [AdmissionManagementController::class, 'index'])->name('admin.admissions.index');
    Route::patch('/admissions/{application}/status', [AdmissionManagementController::class, 'updateStatus'])->name('admin.admissions.status');
    Route::get('/operations', [OperationsController::class, 'index'])->name('admin.operations');
    Route::post('/operations', [OperationsController::class, 'store'])->name('admin.operations.store');
    Route::put('/operations/{id}', [OperationsController::class, 'update'])->name('admin.operations.update');
    Route::delete('/operations/{id}', [OperationsController::class, 'destroy'])->name('admin.operations.destroy');
    Route::post('/operations/attendance', [OperationsController::class, 'attendance'])->name('admin.operations.attendance');
    Route::post('/operations/results', [OperationsController::class, 'result'])->name('admin.operations.results');
    Route::post('/report-cards/{student}/{exam}/notify', [ReportCardController::class, 'notify'])->name('admin.report-cards.notify');
    Route::get('/report-cards/{student}/{exam}', [ReportCardController::class, 'show'])->name('admin.report-cards.show');
    Route::get('/report-cards/{student}/{exam}/download', [ReportCardController::class, 'download'])->name('admin.report-cards.download');

    Route::middleware('admin.only')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings');
        Route::get('/media', [SchoolMediaController::class, 'index'])->name('admin.media.index');
        Route::post('/media', [SchoolMediaController::class, 'store'])->name('admin.media.store');
        Route::put('/media/{id}', [SchoolMediaController::class, 'update'])->name('admin.media.update');
        Route::delete('/media/{id}', [SchoolMediaController::class, 'destroy'])->name('admin.media.destroy');
        Route::put('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
        Route::get('/signature', [SignatureController::class, 'show'])->name('signature.index');
        Route::put('/signature', [SignatureController::class, 'update'])->name('signature.update');
        Route::delete('/signature', [SignatureController::class, 'remove'])->name('signature.remove');
        Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
        Route::get('/payments/create', [PaymentController::class, 'create'])->name('admin.payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->name('admin.payments.store');
        Route::post('/payments/mpesa', [MpesaController::class, 'stkPush'])->name('admin.payments.mpesa');
        Route::post('/payments/mpesa/query/{payment}', [MpesaController::class, 'query'])->name('admin.payments.mpesa.query');
    });
});

Route::middleware(['auth', 'portal.role:pupil,parent,sponsor,teacher'])->prefix('portal')->group(function () {
    Route::get('/', [PortalController::class, 'dashboard'])->name('portal.dashboard');
    Route::post('/logout', [PortalController::class, 'logout'])->name('portal.logout');
    Route::get('/payments', [PortalPaymentController::class, 'index'])->name('portal.payments');
    Route::post('/payments/mpesa', [PortalPaymentController::class, 'pay'])->middleware('throttle:5,1')->name('portal.payments.pay');
    Route::get('/signature', [SignatureController::class, 'show'])->middleware('portal.role:teacher')->name('portal.signature.index');
    Route::put('/signature', [SignatureController::class, 'update'])->middleware('portal.role:teacher')->name('portal.signature.update');
    Route::delete('/signature', [SignatureController::class, 'remove'])->middleware('portal.role:teacher')->name('portal.signature.remove');
    Route::get('/teacher/assessments', [TeacherAssessmentController::class, 'index'])->middleware('portal.role:teacher')->name('portal.teacher-assessments');
    Route::post('/teacher/assessments', [TeacherAssessmentController::class, 'store'])->middleware('portal.role:teacher')->name('portal.teacher-assessments.store');
    Route::get('/teacher/learners', [TeacherPortalController::class, 'learners'])->middleware('portal.role:teacher')->name('portal.teacher-learners');
    Route::get('/teacher/attendance', [TeacherPortalController::class, 'attendance'])->middleware('portal.role:teacher')->name('portal.teacher-attendance');
    Route::post('/teacher/attendance', [TeacherPortalController::class, 'storeAttendance'])->name('portal.teacher-attendance.store');
    Route::get('/report-cards/{student}/{exam}', [ReportCardController::class, 'show'])->name('portal.report-cards.show');
    Route::post('/report-cards/{student}/{exam}/sign', [ReportCardController::class, 'sign'])->name('portal.report-cards.sign');
    Route::get('/report-cards/{student}/{exam}/download', [ReportCardController::class, 'download'])->name('portal.report-cards.download');
});

Route::get('/broadcasting/auth', function () { return response()->json(['ok' => true]); })->middleware('auth');
