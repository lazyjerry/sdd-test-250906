<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'auth:sanctum' => Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => '請求次數過多，請稍後再試',
                    'error' => [
                        'code' => 'TOO_MANY_ATTEMPTS',
                        'details' => '您的請求過於頻繁，請稍後再試',
                        'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
                    ]
                ], 429);
            }
        });

        $exceptions->render(function (Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                // 如果有 Authorization 頭但認證失敗，說明是無效 token
                if ($request->hasHeader('Authorization')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => '無效的認證令牌',
                        'error' => [
                            'code' => 'INVALID_TOKEN',
                            'details' => '提供的認證令牌無效或已過期'
                        ]
                    ], 401);
                }

                // 沒有 Authorization 頭，說明是未認證請求
                return response()->json([
                    'status' => 'error',
                    'message' => '未認證的請求',
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'details' => '此端點需要有效的認證令牌'
                    ]
                ], 401);
            }
        });
    })->create();
