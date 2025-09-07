# Laravel Sanctum 套件指南

## 概述

Laravel Sanctum 是 Laravel 官方提供的輕量級認證套件，專為 SPA (Single Page Applications)、行動應用程式和簡單的 token 基礎 API 設計。它提供了一個簡單的方式來驗證使用者並產生 API token。

## 核心概念

### Personal Access Tokens (個人存取權杖)

Sanctum 允許你為使用者帳戶建立「個人存取權杖」。這些權杖可以被授予特定的能力或範圍，用來指定權杖被允許執行的操作。

### API Token 認證

Sanctum 使用簡單的 token 字串，而不是複雜的 OAuth 權杖，使得 API 認證變得簡單易用。

## 在本專案中的應用

### 1. 配置檔案

**設定檔位置**: `config/sanctum.php`

```php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort()
    ))),

    'guard' => ['web'],

    'expiration' => null,

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
```

### 2. 模型設定

**User 模型** (`app/Models/User.php`)

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    // 其他模型配置...
}
```

## 核心方法與功能

### 1. Request 類別方法

#### `$request->user()`

**用途**: 獲取當前已認證的使用者實例

```php
public function profile(Request $request): JsonResponse
{
    $user = $request->user();

    // 檢查使用者是否存在
    if (!$user) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    return response()->json(['user' => $user]);
}
```

**本專案使用位置**:

- `UserController::profile()`
- `UserController::updateProfile()`
- `UserController::changePassword()`
- `AuthController::logout()`

#### `$request->bearerToken()`

**用途**: 獲取請求中的 Bearer token

```php
$token = $request->bearerToken();
// 返回: "1|abcdef123456..."
```

### 2. User 模型方法 (HasApiTokens Trait)

#### `createToken(string $name, array $abilities = ['*'])`

**用途**: 為使用者建立新的 API token

```php
// 基本用法
$token = $user->createToken('api-token');

// 帶有特定能力的 token
$token = $user->createToken('admin-token', ['user:read', 'user:write']);

// 取得 token 字串
$tokenString = $token->plainTextToken;
```

**本專案使用範例**:

```php
// AuthController::login()
$token = $user->createToken('api-token');

return response()->json([
    'status' => 'success',
    'message' => '登入成功',
    'data' => [
        'user' => $user,
        'token' => $token->plainTextToken,
        'token_type' => 'Bearer',
    ]
]);
```

#### `tokens()`

**用途**: 獲取使用者的所有 token

```php
$tokens = $user->tokens;

// 獲取特定 token
$token = $user->tokens()->where('name', 'api-token')->first();
```

#### `currentAccessToken()`

**用途**: 獲取當前請求使用的 token

```php
$currentToken = $user->currentAccessToken();

// 檢查 token 能力
if ($currentToken->can('user:delete')) {
    // 執行刪除操作
}
```

**本專案使用範例**:

```php
// AuthController::logout()
$user = $request->user();
$token = $user->currentAccessToken();

if ($token && method_exists($token, 'delete')) {
    $token->delete();
}
```

### 3. Token 管理方法

#### 撤銷 Token

```php
// 撤銷當前 token
$user->currentAccessToken()->delete();

// 撤銷所有 token
$user->tokens()->delete();

// 撤銷特定 token
$user->tokens()->where('name', 'api-token')->delete();
```

#### 檢查 Token 能力

```php
// 檢查當前 token 是否有特定能力
if ($request->user()->tokenCan('user:update')) {
    // 執行更新操作
}

// 在中介軟體中檢查
Route::middleware(['auth:sanctum', 'ability:user:delete'])
    ->delete('/users/{user}', [UserController::class, 'destroy']);
```

## 中介軟體使用

### 1. `auth:sanctum`

**用途**: 驗證 API token 或 session

```php
// 路由中使用
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
});
```

**本專案應用**:

```php
// routes/api.php
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // 使用者相關路由
    Route::prefix('users')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile', [UserController::class, 'updateProfile']);
        Route::put('change-password', [UserController::class, 'changePassword']);
    });

    // 認證相關路由
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
    });
});
```

### 2. `ability` 中介軟體

**用途**: 檢查 token 是否具有特定能力

```php
Route::middleware(['auth:sanctum', 'ability:user:update'])
    ->put('/users/{user}', [UserController::class, 'update']);
```

## 認證流程

### 1. 登入流程

```php
// 1. 驗證使用者憑證
$credentials = $request->only('username', 'password');

// 2. 嘗試認證
if (Auth::guard('web')->attempt($credentials)) {
    $user = Auth::guard('web')->user();

    // 3. 建立 token
    $token = $user->createToken('api-token');

    // 4. 返回 token
    return response()->json([
        'token' => $token->plainTextToken,
        'user' => $user
    ]);
}
```

### 2. API 請求流程

```bash
# 客戶端請求
curl -H "Authorization: Bearer 1|abcdef123456..." \
     -H "Accept: application/json" \
     http://localhost/api/v1/users/profile
```

```php
// 伺服器端處理
public function profile(Request $request): JsonResponse
{
    // 1. Sanctum 中介軟體驗證 token
    // 2. 設定已認證的使用者到 request
    $user = $request->user(); // 3. 獲取已認證的使用者

    // 4. 處理業務邏輯
    return response()->json(['user' => $user]);
}
```

### 3. 登出流程

```php
public function logout(Request $request): JsonResponse
{
    $user = $request->user();
    $token = $user->currentAccessToken();

    // 刪除當前 token
    if ($token && method_exists($token, 'delete')) {
        $token->delete();
    }

    return response()->json(['message' => '登出成功']);
}
```

## 安全考量

### 1. Token 過期

```php
// 在 config/sanctum.php 中設定
'expiration' => 24 * 60, // 24 小時後過期
```

### 2. Token 能力限制

```php
// 建立有限能力的 token
$token = $user->createToken('read-only', ['user:read']);

// 在控制器中檢查
if (!$request->user()->tokenCan('user:write')) {
    return response()->json(['error' => 'Insufficient permissions'], 403);
}
```

### 3. HTTPS 使用

生產環境中務必使用 HTTPS 來保護 token 傳輸：

```php
// 在 .env 中設定
APP_URL=https://yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
```

## 測試中的使用

### 1. `Sanctum::actingAs()`

**用途**: 在測試中模擬已認證的使用者

```php
use Laravel\Sanctum\Sanctum;

public function test_user_can_get_profile()
{
    $user = User::factory()->create();

    // 模擬已認證的使用者
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/users/profile');

    $response->assertStatus(200);
}
```

**本專案測試範例**:

```php
// tests/Feature/User/ProfileContractTest.php
public function test_user_can_get_profile(): void
{
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/users/profile');

    $response->assertStatus(200)
             ->assertJson([
                 'status' => 'success',
                 'data' => [
                     'user' => [
                         'id' => $user->id,
                         'email' => $user->email,
                     ]
                 ]
             ]);
}
```

### 2. 帶有能力的測試

```php
public function test_user_with_specific_ability()
{
    $user = User::factory()->create();

    // 模擬具有特定能力的 token
    Sanctum::actingAs($user, ['user:update']);

    $response = $this->putJson('/api/v1/users/profile', [
        'name' => 'New Name'
    ]);

    $response->assertStatus(200);
}
```

## 常見問題與解決方案

### 1. Token 認證失敗

**問題**: API 請求返回 401 Unauthenticated

**解決方案**:

```php
// 檢查請求標頭
Authorization: Bearer {token}
Accept: application/json

// 檢查 token 是否有效
$token = PersonalAccessToken::findToken($tokenString);
if (!$token) {
    // Token 無效或已過期
}
```

### 2. CORS 問題

**解決方案**:

```php
// config/cors.php
'supports_credentials' => true,
'allowed_origins' => ['http://localhost:3000'],
'allowed_headers' => ['*'],
```

### 3. 軟刪除使用者的處理

**本專案解決方案**:

```php
public function profile(Request $request): JsonResponse
{
    $user = $request->user();

    // 檢查使用者是否被軟刪除
    if ($user->trashed()) {
        return response()->json([
            'status' => 'error',
            'message' => '使用者帳戶已被停用',
            'error' => ['code' => 'USER_DEACTIVATED']
        ], 401);
    }

    // 正常處理邏輯...
}
```

## 效能最佳化

### 1. Token 清理

定期清理過期的 token：

```php
// 建立 Artisan 指令
php artisan make:command PruneTokens

// 在指令中實作
public function handle()
{
    PersonalAccessToken::where('created_at', '<', now()->subDays(30))->delete();
}
```

### 2. 資料庫索引

確保 personal_access_tokens 表有適當的索引：

```php
// 在遷移檔案中
$table->index('tokenable_id');
$table->index('token');
```

## 總結

Laravel Sanctum 為本專案提供了：

1. **簡單的 Token 認證** - 無需複雜的 OAuth 設定
2. **靈活的權限控制** - 通過 token 能力系統
3. **易於測試** - `Sanctum::actingAs()` 方法
4. **安全性** - 內建的 token 管理和過期機制
5. **與 Laravel 生態系統的完美整合**

這使得我們能夠快速構建安全、可擴展的 API 認證系統，同時保持程式碼的簡潔和可維護性。
