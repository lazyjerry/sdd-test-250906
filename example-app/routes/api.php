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
            Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
            Route::post('/users/{id}/reset-password', [AdminController::class, 'resetUserPassword']);
            Route::post('/users/{id}/deactivate', [AdminController::class, 'deactivateUser']);
            Route::post('/users/{id}/activate', [AdminController::class, 'activateUser']);

            // 批量操作路由
            Route::post('users/bulk-deactivate', [AdminController::class, 'bulkDeactivateUsers']);
            Route::post('users/bulk-activate', [AdminController::class, 'bulkActivateUsers']);
            Route::post('users/bulk-update', [AdminController::class, 'bulkUpdateUsers']);
            Route::post('users/bulk-role-change', [AdminController::class, 'bulkRoleChangeUsers']);
            Route::post('users/bulk-delete', [AdminController::class, 'bulkDeleteUsers']);

            // 統計相關路由
            Route::get('statistics/users', [AdminController::class, 'getUserStatistics']);
            Route::get('statistics/system', [AdminController::class, 'getSystemStatistics']);
            Route::get('statistics/activity', [AdminController::class, 'getActivityStatistics']);

            // 系統健康檢查
            Route::get('system/health', [AdminController::class, 'getSystemHealth']);

            // 審計日誌
            Route::get('audit-log', [AdminController::class, 'getAuditLog']);
            Route::get('activity-log', [AdminController::class, 'getActivityLog']);
        });
    });
});

// 郵箱驗證路由 (為測試用的 temporarySignedRoute 提供支援)
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmailByLink'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

/*
 * ⚠️ RESTful API 設計原則衝突警告：
 *
 * 上述 GET 路由（郵箱驗證路由 ）違反了 RESTful API 的以下原則：
 *
 * 1. 【冪等性違反】- GET 請求應該是冪等的，不應改變伺服器狀態
 *    → 但此路由會修改用戶的 email_verified_at 狀態
 *
 * 2. 【安全性違反】- GET 請求應該是"安全"的，只用於資料檢索
 *    → 但此路由執行了狀態變更操作
 *
 * 3. 【語意不明確】- GET 通常表示"獲取資源"
 *    → 但此路由實際執行"驗證動作"
 *
 * 4. 【快取問題】- GET 請求可能被瀏覽器/代理伺服器快取
 *    → 可能導致重複驗證或驗證失效
 *
 * 5. 【記錄風險】- GET 參數會出現在存取記錄中
 *    → 敏感的驗證參數可能被記錄
 *
 * 更符合 RESTful 的做法：
 * - 使用 POST /api/v1/auth/verify-email 進行實際驗證
 * - GET 路由僅用於重定向到前端頁面，由前端調用 POST API
 *
 * 保留此 GET 路由的原因：
 * - Laravel 原生郵箱驗證機制的相容性
 * - 使用者點擊郵件連結的便利性
 * - temporarySignedRoute() 方法需要具名路由
 */

// 保留原有的測試路由
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
