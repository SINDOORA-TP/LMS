<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\FirebaseAuthenticate;
use App\Http\Middleware\OptionalFirebaseAuth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| LMS API Routes
|
| Public routes: No auth required
| Auth routes: Firebase token required
| Admin routes: Firebase token + admin role required
|
*/

// ============================================================
// PUBLIC ROUTES (no auth required)
// ============================================================

Route::get('/courses', [CourseController::class, 'index'])
    ->middleware(OptionalFirebaseAuth::class);

Route::get('/courses/{id}', [CourseController::class, 'show'])
    ->middleware(OptionalFirebaseAuth::class);

Route::get('/courses/{courseId}/modules', [ModuleController::class, 'index']);

// Payment callback (PhonePe sends here after payment)
Route::post('/payments/callback', [PaymentController::class, 'callback']);


// ============================================================
// AUTHENTICATED ROUTES (Firebase token required)
// ============================================================

Route::middleware(FirebaseAuthenticate::class)->group(function () {

    // Auth
    Route::post('/auth/sync', [AuthController::class, 'sync']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    // Enrollment
    Route::post('/courses/{id}/enroll', [EnrollmentController::class, 'enroll']);
    Route::get('/my-courses', [EnrollmentController::class, 'myCourses']);

    // Video Access (secure — checks auth + enrollment + unlock)
    Route::get('/videos/{id}/access', [VideoController::class, 'access']);

    // Progress
    Route::post('/videos/{id}/progress', [ProgressController::class, 'recordProgress']);
    Route::get('/courses/{courseId}/progress', [ProgressController::class, 'courseProgress']);
    Route::get('/my-progress', [ProgressController::class, 'myProgress']);

    // Payment Status
    Route::get('/payments/{merchantTransactionId}/status', [PaymentController::class, 'status']);

    // Security
    Route::post('/security-violation', [AuthController::class, 'reportSecurityViolation']);
});


// ============================================================
// ADMIN ROUTES (Firebase token + admin role required)
// ============================================================

Route::middleware([FirebaseAuthenticate::class, AdminMiddleware::class])
    ->prefix('admin')
    ->group(function () {

        // Course Management
        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{id}', [CourseController::class, 'update']);
        Route::delete('/courses/{id}', [CourseController::class, 'destroy']);

        // Module Management
        Route::post('/modules', [ModuleController::class, 'store']);
        Route::put('/modules/{id}', [ModuleController::class, 'update']);
        Route::delete('/modules/{id}', [ModuleController::class, 'destroy']);
        Route::post('/modules/reorder', [ModuleController::class, 'reorder']);

        // Video Management
        Route::post('/videos', [VideoController::class, 'store']);
        Route::put('/videos/{id}', [VideoController::class, 'update']);
        Route::delete('/videos/{id}', [VideoController::class, 'destroy']);
    });
