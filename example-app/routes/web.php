<?php

use App\Http\Controllers\Web\EmailVerificationController;
use App\Http\Controllers\Web\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 密碼重設測試頁面
Route::get('/password-reset-test', function () {
    return view('password-reset-test');
})->name('password.reset.test');

// 命名路由，供 Laravel 內部功能使用
Route::get('/login', function () {
    return response()->json([
        'status' => 'error',
        'message' => '此為 API 專用應用程式，請使用 /api/v1/auth/login',
        'error_code' => 'WEB_LOGIN_NOT_SUPPORTED'
    ], 302, [
        'Location' => '/api/v1/auth/login'
    ]);
})->name('login');

// 密碼重設相關路由
Route::get('/password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.reset.submit');
Route::get('/password/reset-success', [PasswordResetController::class, 'success'])->name('password.reset.success');

/*
 * ⚠️ RESTful API 設計原則衝突警告：
 *
 * 以下 GET 路由（郵箱驗證路由）違反了 RESTful API 的以下原則：
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
 *
 * 重構變更：
 * - 將 verifyEmailByLink 從 AuthController 移動到 EmailVerificationController
 * - 使用共用的 EmailVerificationService 處理邏輯
 * - 提供友好的 Web 界面回應
 */

// 郵箱驗證路由（Web 端點）
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verifyByLink'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
