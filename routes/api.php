<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PushNotificationController;
use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Web\ChartController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/request-otp', 'requestOtp');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/reset-password-otp', 'resetPasswordViaOtp');
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify');
    Route::post('/email/verify/resend', [AuthController::class, 'resendVerificationEmail']);

    Route::get('/auth/session/validate', [AuthController::class, 'validateSession']);

    // Push Notifications
    Route::post('/push/notification', [PushNotificationController::class, 'sendToUser']);
    Route::post('/push/alert', [AlertController::class, 'sendToUser']);
});
