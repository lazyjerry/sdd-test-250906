<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * 重設密碼 API 合約測試.
 *
 * 測試 POST /api/v1/auth/reset-password 端點的請求/回應結構
 * 確保 API 合約符合 OpenAPI 規格定義
 *
 * @internal
 *
 * @coversNothing
 */
final class ResetPasswordContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試重設密碼成功時的回應結構.
     */
    public function testResetPasswordSuccessResponseStructure()
    {
        // 建立已驗證的使用者
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('oldpassword')
        ]);

        // 生成有效的重設 token
        $token = Password::createToken($user);

        $requestData = [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123'
        ];

        $response = $this->postJson('/api/v1/auth/reset-password', $requestData);

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
            'message' => '密碼重設成功',
            'data' => [
                'email' => 'test@example.com'
            ]
        ]);
    }

    /**
     * 測試無效 token 時的回應結構.
     */
    public function testResetPasswordInvalidTokenResponseStructure()
    {
        $requestData = [
            'token' => 'invalid-token-here',
            'email' => 'test@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123'
        ];

        $response = $this->postJson('/api/v1/auth/reset-password', $requestData);

        // 期望狀態碼 400 Bad Request
        $response->assertStatus(400);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error_code'
        ]);

        $response->assertJson([
            'status' => 'error',
            'message' => '無效或過期的重設連結',
            'error_code' => 'INVALID_RESET_TOKEN'
        ]);
    }

    /**
     * 測試驗證失敗時的回應結構.
     */
    public function testResetPasswordValidationErrorResponseStructure()
    {
        $requestData = [
            'token' => '',
            'email' => 'invalid-email',
            'password' => '123', // 密碼太短
            'password_confirmation' => '456' // 不匹配
        ];

        $response = $this->postJson('/api/v1/auth/reset-password', $requestData);

        // 期望狀態碼 422 Unprocessable Entity
        $response->assertStatus(422);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'token',
                'email',
                'password'
            ]
        ]);

        $response->assertJson([
            'status' => 'error',
            'message' => '資料驗證失敗'
        ]);
    }

    /**
     * 測試不存在使用者的回應結構.
     */
    public function testResetPasswordNonexistentUserResponseStructure()
    {
        $requestData = [
            'token' => 'some-token',
            'email' => 'nonexistent@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123'
        ];

        $response = $this->postJson('/api/v1/auth/reset-password', $requestData);

        // 期望狀態碼 400 Bad Request
        $response->assertStatus(400);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error_code'
        ]);

        $response->assertJson([
            'status' => 'error',
            'error_code' => 'USER_NOT_FOUND'
        ]);
    }

    /**
     * 測試過期 token 的回應結構.
     */
    public function testResetPasswordExpiredTokenResponseStructure()
    {
        // 建立使用者
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now()
        ]);

        // 這裡我們模擬一個過期的 token（實際實作中會檢查時間戳）
        $expiredToken = 'expired-token-simulation';

        $requestData = [
            'token' => $expiredToken,
            'email' => 'test@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123'
        ];

        $response = $this->postJson('/api/v1/auth/reset-password', $requestData);

        // 期望狀態碼 400 Bad Request
        $response->assertStatus(400);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error_code'
        ]);

        $response->assertJson([
            'status' => 'error',
            'error_code' => 'INVALID_RESET_TOKEN'
        ]);
    }
}
