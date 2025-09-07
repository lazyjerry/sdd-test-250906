<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * EmailVerificationService 單元測試.
 *
 * 測試共用郵件驗證服務的功能
 *
 * @internal
 *
 * @coversNothing
 */
final class EmailVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EmailVerificationService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new EmailVerificationService();

        // 創建未驗證的測試用戶
        $this->user = User::factory()->create([
            'email' => 'service@example.com',
            'email_verified_at' => null,
            'password' => Hash::make('TestPassword123!')
        ]);
    }

    /**
     * 測試成功的郵件驗證.
     */
    public function testSuccessfulEmailVerification(): void
    {
        // 生成有效的驗證連結參數
        $expires = now()->addHour()->timestamp;
        $hash = sha1($this->user->getEmailForVerification());

        // 生成有效簽名
        $url = url()->temporarySignedRoute(
            'verification.verify',
            now()->setTimestamp($expires),
            ['id' => $this->user->id, 'hash' => $hash]
        );
        $urlParts = parse_url($url);
        parse_str($urlParts['query'], $params);

        $result = $this->service->verifyEmail([
            'id' => $this->user->id,
            'hash' => $hash,
            'expires' => $expires,
            'signature' => $params['signature']
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('電子郵件驗證成功', $result['message']);
        $this->assertNotNull($result['user']);
        $this->assertNull($result['error_code']);

        // 驗證用戶狀態已更新
        $this->user->refresh();
        $this->assertNotNull($this->user->email_verified_at);
    }

    /**
     * 測試已驗證的郵件.
     */
    public function testAlreadyVerifiedEmail(): void
    {
        // 先標記為已驗證
        $this->user->markEmailAsVerified();

        $result = $this->service->verifyEmail([
            'id' => $this->user->id,
            'hash' => 'any-hash',
            'expires' => now()->addHour()->timestamp,
            'signature' => 'any-signature'
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('電子郵件已經驗證過了', $result['message']);
        $this->assertNotNull($result['user']);
    }

    /**
     * 測試不存在的用戶.
     */
    public function testNonExistentUser(): void
    {
        $result = $this->service->verifyEmail([
            'id' => 99999,
            'hash' => 'some-hash',
            'expires' => now()->addHour()->timestamp,
            'signature' => 'some-signature'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('找不到指定的使用者', $result['message']);
        $this->assertSame('USER_NOT_FOUND', $result['error_code']);
        $this->assertNull($result['user']);
    }

    /**
     * 測試無效的簽名.
     */
    public function testInvalidSignature(): void
    {
        $result = $this->service->verifyEmail([
            'id' => $this->user->id,
            'hash' => sha1($this->user->getEmailForVerification()),
            'expires' => now()->addHour()->timestamp,
            'signature' => 'invalid-signature'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('無效或過期的驗證連結', $result['message']);
        $this->assertSame('INVALID_VERIFICATION_LINK', $result['error_code']);
    }

    /**
     * 測試過期的連結.
     */
    public function testExpiredLink(): void
    {
        $pastTime = now()->subHour()->timestamp;
        $hash = sha1($this->user->getEmailForVerification());

        // 生成過期的簽名
        $url = url()->temporarySignedRoute(
            'verification.verify',
            now()->setTimestamp($pastTime),
            ['id' => $this->user->id, 'hash' => $hash]
        );
        $urlParts = parse_url($url);
        parse_str($urlParts['query'], $params);

        $result = $this->service->verifyEmail([
            'id' => $this->user->id,
            'hash' => $hash,
            'expires' => $pastTime,
            'signature' => $params['signature']
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('無效或過期的驗證連結', $result['message']);
        $this->assertSame('INVALID_VERIFICATION_LINK', $result['error_code']);
    }

    /**
     * 測試無效的哈希值
     */
    public function testInvalidHash(): void
    {
        $expires = now()->addHour()->timestamp;

        // 生成有效簽名但使用錯誤的哈希
        $url = url()->temporarySignedRoute(
            'verification.verify',
            now()->setTimestamp($expires),
            ['id' => $this->user->id, 'hash' => 'wrong-hash']
        );
        $urlParts = parse_url($url);
        parse_str($urlParts['query'], $params);

        $result = $this->service->verifyEmail([
            'id' => $this->user->id,
            'hash' => 'wrong-hash',
            'expires' => $expires,
            'signature' => $params['signature']
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('無效或過期的驗證連結', $result['message']);
        $this->assertSame('INVALID_VERIFICATION_LINK', $result['error_code']);
    }

    /**
     * 測試 API 回應格式化.
     */
    public function testFormatApiResponse(): void
    {
        // 成功情況
        $successResult = [
            'success' => true,
            'message' => '電子郵件驗證成功',
            'user' => [
                'id' => 1,
                'username' => 'test',
                'email' => 'test@example.com',
                'email_verified_at' => now(),
            ],
            'error_code' => null
        ];

        $apiResponse = $this->service->formatApiResponse($successResult);

        $this->assertSame([
            'status' => 'success',
            'message' => '電子郵件驗證成功',
            'data' => [
                'user' => $successResult['user'],
            ],
        ], $apiResponse);

        // 失敗情況
        $failureResult = [
            'success' => false,
            'message' => '無效或過期的驗證連結',
            'user' => null,
            'error_code' => 'INVALID_VERIFICATION_LINK'
        ];

        $apiResponse = $this->service->formatApiResponse($failureResult);

        $this->assertSame([
            'status' => 'error',
            'message' => '無效或過期的驗證連結',
            'error_code' => 'INVALID_VERIFICATION_LINK',
        ], $apiResponse);
    }

    /**
     * 測試 Web 回應格式化.
     */
    public function testFormatWebResponse(): void
    {
        $result = [
            'success' => true,
            'message' => '電子郵件驗證成功',
            'user' => ['id' => 1],
            'error_code' => null
        ];

        $webResponse = $this->service->formatWebResponse($result);

        $this->assertSame($result, $webResponse);
    }
}
