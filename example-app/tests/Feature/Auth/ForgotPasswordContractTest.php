<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * 忘記密碼 API 合約測試.
 *
 * 測試 POST /api/v1/auth/forgot-password 端點的請求/回應結構
 * 確保 API 合約符合 OpenAPI 規格定義
 *
 * @internal
 *
 * @coversNothing
 */
final class ForgotPasswordContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試忘記密碼成功時的回應結構.
     */
    public function testForgotPasswordSuccessResponseStructure()
    {
        Notification::fake();

        // 建立已驗證的使用者
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('oldpassword')
        ]);

        $requestData = [
            'email' => 'test@example.com'
        ];

        $response = $this->postJson('/api/v1/auth/forgot-password', $requestData);

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構符合 OpenAPI 規格
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'email'
            ]
        ]);

        // 驗證回應值
        $response->assertJson([
            'status' => 'success',
            'message' => '密碼重設連結已發送到您的電子郵件',
            'data' => [
                'email' => 'test@example.com'
            ]
        ]);
    }

    /**
     * 測試不存在的 email 時的回應結構.
     */
    public function testForgotPasswordNonexistentEmailResponseStructure()
    {
        $requestData = [
            'email' => 'nonexistent@example.com'
        ];

        $response = $this->postJson('/api/v1/auth/forgot-password', $requestData);

        // 出於安全考量，通常回傳相同的成功訊息
        $response->assertStatus(200);

        // 期望回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'email'
            ]
        ]);

        $response->assertJson([
            'status' => 'success',
            'message' => '密碼重設連結已發送到您的電子郵件'
        ]);
    }

    /**
     * 測試驗證失敗時的回應結構.
     */
    public function testForgotPasswordValidationErrorResponseStructure()
    {
        $requestData = [
            'email' => 'invalid-email-format'
        ];

        $response = $this->postJson('/api/v1/auth/forgot-password', $requestData);

        // 期望狀態碼 422 Unprocessable Entity
        $response->assertStatus(422);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'email'
            ]
        ]);

        $response->assertJson([
            'status' => 'error',
            'message' => '資料驗證失敗'
        ]);
    }

    /**
     * 測試缺少必填欄位時的回應結構.
     */
    public function testForgotPasswordMissingEmailResponseStructure()
    {
        $requestData = [];

        $response = $this->postJson('/api/v1/auth/forgot-password', $requestData);

        // 期望狀態碼 422 Unprocessable Entity
        $response->assertStatus(422);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'email'
            ]
        ]);

        $response->assertJson([
            'status' => 'error',
            'message' => '資料驗證失敗'
        ]);
    }

    /**
     * 測試 Rate Limiting 回應結構.
     */
    public function testForgotPasswordRateLimitResponseStructure()
    {
        $requestData = [
            'email' => 'test@example.com'
        ];

        // 模擬多次請求觸發 Rate Limiting
        for ($i = 0; $i < 6; ++$i) {
            $response = $this->postJson('/api/v1/auth/forgot-password', $requestData);
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
