<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API v1 路由群組
Route::prefix('v1')->group(function () {
    // 認證相關路由 (無需認證)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1'); // 5 次/分鐘
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1'); // 5 次/分鐘
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    });

    // 需要認證的路由
    Route::middleware('auth:sanctum')->group(function () {
        // 認證相關 (需要登入)
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });

        // 使用者相關路由
        Route::prefix('users')->group(function () {
            Route::get('/profile', [UserController::class, 'profile']);
            Route::put('/profile', [UserController::class, 'updateProfile']);
            Route::put('/change-password', [UserController::class, 'changePassword'])
                ->middleware('throttle:5,1'); // 5 requests per minute
        });

        // 管理員相關路由
        Route::prefix('admin')->group(function () {
            Route::get('/users', [AdminController::class, 'getUsers']);
            Route::get('/users/{id}', [AdminController::class, 'getUser']);
            Route::put('/users/{id}', [AdminController::class, 'updateUser']);
            Route::post('/users/{id}/reset-password', [AdminController::class, 'resetUserPassword']);
        });
    });
});

// 郵箱驗證路由 (為測試用的 temporarySignedRoute 提供支援)
Route::get('/email/verify/{id}/{hash}', function () {
    // 這個路由主要用於生成簽名URL，實際驗證透過API完成
    return response()->json(['message' => 'Use POST /api/v1/auth/verify-email for email verification']);
})->name('verification.verify');

// 保留原有的測試路由
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
