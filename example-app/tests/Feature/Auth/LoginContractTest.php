<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 使用者登入 API 合約測試.
 *
 * 測試 POST /api/v1/auth/login 端點的請求/回應結構
 * 確保 API 合約符合 OpenAPI 規格定義
 *
 * @internal
 *
 * @coversNothing
 */
final class LoginContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試登入成功時的回應結構.
     */
    public function testLoginSuccessResponseStructure()
    {
        // 建立測試使用者
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('Password123'),
            'email_verified_at' => now()
        ]);

        $requestData = [
            'username' => 'testuser',
            'password' => 'Password123'
        ];

        $response = $this->postJson('/api/v1/auth/login', $requestData);

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構符合 OpenAPI 規格
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'username',
                    'email',
                    'name',
                    'phone',
                    'email_verified_at',
                    'created_at',
                    'updated_at'
                ],
                'token',
                'expires_at'
            ]
        ]);

        // 驗證回應值
        $response->assertJson([
            'status' => 'success',
            'message' => '登入成功'
        ]);
    }

    /**
     * 測試登入憑證錯誤時的回應結構.
     */
    public function testLoginInvalidCredentialsResponseStructure()
    {
        $requestData = [
            'username' => 'nonexistent',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson('/api/v1/auth/login', $requestData);

        // 期望狀態碼 401 Unauthorized
        $response->assertStatus(401);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error_code'
        ]);

        $response->assertJson([
            'status' => 'error',
            'message' => '使用者名稱或密碼錯誤',
            'error_code' => 'INVALID_CREDENTIALS'
        ]);
    }

    /**
     * 測試登入驗證失敗時的回應結構.
     */
    public function testLoginValidationErrorResponseStructure()
    {
        $requestData = [
            'username' => '', // 必填欄位留空
            'password' => ''  // 必填欄位留空
        ];

        $response = $this->postJson('/api/v1/auth/login', $requestData);

        // 期望狀態碼 422 Unprocessable Entity
        $response->assertStatus(422);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'username',
                'password'
            ]
        ]);

        $response->assertJson([
            'status' => 'error',
            'message' => '資料驗證失敗'
        ]);
    }

    /**
     * 測試未驗證 email 使用者登入的回應結構.
     */
    public function testLoginUnverifiedEmailResponseStructure()
    {
        // 建立未驗證 email 的使用者
        $user = User::factory()->create([
            'username' => 'unverified',
            'email' => 'unverified@example.com',
            'password' => Hash::make('Password123'),
            'email_verified_at' => null
        ]);

        $requestData = [
            'username' => 'unverified',
            'password' => 'Password123'
        ];

        $response = $this->postJson('/api/v1/auth/login', $requestData);

        // 期望狀態碼 403 Forbidden
        $response->assertStatus(403);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error_code'
        ]);

        $response->assertJson([
            'status' => 'error',
            'message' => '請先驗證您的電子郵件地址',
            'error_code' => 'EMAIL_NOT_VERIFIED'
        ]);
    }

    /**
     * 測試 Rate Limiting 回應結構.
     */
    public function testLoginRateLimitResponseStructure()
    {
        $requestData = [
            'username' => 'testuser',
            'password' => 'wrongpassword'
        ];

        // 模擬多次失敗登入觸發 Rate Limiting
        for ($i = 0; $i < 6; ++$i) {
            $response = $this->postJson('/api/v1/auth/login', $requestData);
        }

        // 期望狀態碼 429 Too Many Requests
        $response->assertStatus(429);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error_code',
            'retry_after'
        ]);

        $response->assertJson([
            'status' => 'error',
            'error_code' => 'TOO_MANY_ATTEMPTS'
        ]);
    }
}
