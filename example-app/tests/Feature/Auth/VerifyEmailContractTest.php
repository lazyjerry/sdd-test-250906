<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * 信箱驗證 API 合約測試.
 *
 * 測試 POST /api/v1/auth/verify-email 端點的請求/回應結構
 * 確保 API 合約符合 OpenAPI 規格定義
 *
 * @internal
 *
 * @coversNothing
 */
final class VerifyEmailContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試信箱驗證成功時的回應結構.
     */
    public function testVerifyEmailSuccessResponseStructure()
    {
        // 建立未驗證的使用者
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
            'password' => Hash::make('password123')
        ]);

        // 生成有效的驗證 URL 簽名
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // 提取簽名參數
        $urlParts = parse_url($verificationUrl);
        parse_str($urlParts['query'], $params);

        $requestData = [
            'id' => $user->id,
            'hash' => sha1($user->email),
            'expires' => $params['expires'],
            'signature' => $params['signature']
        ];

        $response = $this->postJson('/api/v1/auth/verify-email', $requestData);

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
                    'email_verified_at'
                ]
            ]
        ]);

        // 驗證回應值
        $response->assertJson([
            'status' => 'success',
            'message' => '電子郵件驗證成功'
        ]);
    }

    /**
     * 測試無效簽名時的回應結構.
     */
    public function testVerifyEmailInvalidSignatureResponseStructure()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null
        ]);

        $requestData = [
            'id' => $user->id,
            'hash' => sha1($user->email),
            'expires' => now()->addMinutes(60)->timestamp,
            'signature' => 'invalid-signature'
        ];

        $response = $this->postJson('/api/v1/auth/verify-email', $requestData);

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
            'message' => '無效或過期的驗證連結',
            'error_code' => 'INVALID_VERIFICATION_LINK'
        ]);
    }

    /**
     * 測試已驗證使用者的回應結構.
     */
    public function testVerifyEmailAlreadyVerifiedResponseStructure()
    {
        // 建立已驗證的使用者
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123')
        ]);

        $requestData = [
            'id' => $user->id,
            'hash' => sha1($user->email),
            'expires' => now()->addMinutes(60)->timestamp,
            'signature' => 'some-signature'
        ];

        $response = $this->postJson('/api/v1/auth/verify-email', $requestData);

        // 期望狀態碼 200 OK（已驗證也算成功）
        $response->assertStatus(200);

        // 期望回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'email',
                    'email_verified_at'
                ]
            ]
        ]);

        $response->assertJson([
            'status' => 'success',
            'message' => '電子郵件已經驗證過了'
        ]);
    }

    /**
     * 測試不存在使用者的回應結構.
     */
    public function testVerifyEmailNonexistentUserResponseStructure()
    {
        $requestData = [
            'id' => 99999, // 不存在的 ID
            'hash' => sha1('nonexistent@example.com'),
            'expires' => now()->addMinutes(60)->timestamp,
            'signature' => 'some-signature'
        ];

        $response = $this->postJson('/api/v1/auth/verify-email', $requestData);

        // 期望狀態碼 404 Not Found
        $response->assertStatus(404);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error_code'
        ]);

        $response->assertJson([
            'status' => 'error',
            'message' => '找不到指定的使用者',
            'error_code' => 'USER_NOT_FOUND'
        ]);
    }

    /**
     * 測試過期連結的回應結構.
     */
    public function testVerifyEmailExpiredLinkResponseStructure()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null
        ]);

        // 模擬過期的時間戳
        $expiredTimestamp = now()->subHours(2)->timestamp;

        $requestData = [
            'id' => $user->id,
            'hash' => sha1($user->email),
            'expires' => $expiredTimestamp,
            'signature' => 'some-signature'
        ];

        $response = $this->postJson('/api/v1/auth/verify-email', $requestData);

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
            'message' => '無效或過期的驗證連結',
            'error_code' => 'INVALID_VERIFICATION_LINK'
        ]);
    }

    /**
     * 測試驗證失敗時的回應結構.
     */
    public function testVerifyEmailValidationErrorResponseStructure()
    {
        $requestData = [
            'id' => 'not-a-number',
            'hash' => '',
            'expires' => 'invalid-timestamp',
            'signature' => ''
        ];

        $response = $this->postJson('/api/v1/auth/verify-email', $requestData);

        // 期望狀態碼 422 Unprocessable Entity
        $response->assertStatus(422);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'id',
                'hash',
                'expires',
                'signature'
            ]
        ]);

        $response->assertJson([
            'status' => 'error',
            'message' => '資料驗證失敗'
        ]);
    }
}
