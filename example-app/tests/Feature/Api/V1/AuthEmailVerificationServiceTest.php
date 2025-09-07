<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * AuthController 郵件驗證功能測試（重構後）.
 *
 * 驗證使用共用服務後的 API 端點功能
 *
 * @internal
 *
 * @coversNothing
 */
final class AuthEmailVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建未驗證的測試用戶
        $this->user = User::factory()->create([
            'email' => 'apitest@example.com',
            'email_verified_at' => null,
            'password' => Hash::make('TestPassword123!')
        ]);
    }

    /**
     * 測試成功的郵件驗證 API.
     */
    public function testSuccessfulEmailVerificationApi(): void
    {
        // 生成有效的驗證參數
        $expires = now()->addHour()->timestamp;
        $hash = sha1($this->user->getEmailForVerification());

        $url = url()->temporarySignedRoute(
            'verification.verify',
            now()->setTimestamp($expires),
            ['id' => $this->user->id, 'hash' => $hash]
        );
        $urlParts = parse_url($url);
        parse_str($urlParts['query'], $params);

        // 發送 API 請求
        $response = $this->postJson('/api/v1/auth/verify-email', [
            'id' => $this->user->id,
            'hash' => $hash,
            'expires' => $expires,
            'signature' => $params['signature']
        ]);

        // 驗證響應
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '電子郵件驗證成功',
                'data' => [
                    'user' => [
                        'id' => $this->user->id,
                        'username' => $this->user->username,
                        'email' => $this->user->email,
                    ],
                ],
            ]);

        // 驗證用戶狀態已更新
        $this->user->refresh();
        $this->assertNotNull($this->user->email_verified_at);
    }

    /**
     * 測試無效簽名的郵件驗證 API.
     */
    public function testInvalidSignatureEmailVerificationApi(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-email', [
            'id' => $this->user->id,
            'hash' => sha1($this->user->getEmailForVerification()),
            'expires' => now()->addHour()->timestamp,
            'signature' => 'invalid-signature'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => '無效或過期的驗證連結',
                'error_code' => 'INVALID_VERIFICATION_LINK',
            ]);

        // 驗證用戶狀態未更新
        $this->user->refresh();
        $this->assertNull($this->user->email_verified_at);
    }

    /**
     * 測試已驗證用戶的郵件驗證 API.
     */
    public function testAlreadyVerifiedEmailApi(): void
    {
        // 先標記為已驗證
        $this->user->markEmailAsVerified();

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'id' => $this->user->id,
            'hash' => 'any-hash',
            'expires' => now()->addHour()->timestamp,
            'signature' => 'any-signature'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '電子郵件已經驗證過了',
                'data' => [
                    'user' => [
                        'id' => $this->user->id,
                        'email' => $this->user->email,
                    ],
                ],
            ]);
    }

    /**
     * 測試不存在用戶的郵件驗證 API.
     */
    public function testNonExistentUserEmailVerificationApi(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-email', [
            'id' => 99999,
            'hash' => 'some-hash',
            'expires' => now()->addHour()->timestamp,
            'signature' => 'some-signature'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => '找不到指定的使用者',
                'error_code' => 'USER_NOT_FOUND',
            ]);
    }

    /**
     * 測試驗證參數缺失的情況
     */
    public function testMissingParametersEmailVerificationApi(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-email', [
            'id' => $this->user->id,
            // 缺少其他必需參數
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => '資料驗證失敗',
            ])
            ->assertJsonStructure([
                'errors' => [
                    'hash', 'expires', 'signature'
                ]
            ]);
    }
}
