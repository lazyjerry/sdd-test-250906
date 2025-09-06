<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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

Route::get('/password/reset/{token}', function ($token) {
    return response()->json([
        'status' => 'info',
        'message' => '密碼重置連結',
        'data' => [
            'token' => $token,
            'api_endpoint' => '/api/v1/auth/reset-password',
            'instructions' => '請使用 POST 請求將此 token 發送到 API 端點'
        ]
    ]);
})->name('password.reset');
