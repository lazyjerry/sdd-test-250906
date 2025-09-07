<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * PasswordResetService 單元測試.
 *
 * 測試共用密碼重設服務的功能
 *
 * @internal
 *
 * @coversNothing
 */
final class PasswordResetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PasswordResetService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PasswordResetService();

        // 創建測試用戶
        $this->user = User::factory()->create([
            'email' => 'service@example.com',
            'password' => Hash::make('OldPassword123!')
        ]);
    }

    /**
     * 測試成功的密碼重設.
     */
    public function testSuccessfulPasswordReset(): void
    {
        $token = Password::createToken($this->user);

        $result = $this->service->resetPassword([
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!'
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('密碼重設成功', $result['message']);
        $this->assertSame($this->user->email, $result['email']);
        $this->assertNull($result['error_code']);

        // 驗證密碼已更新
        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $this->user->password));
    }

    /**
     * 測試無效 token 的密碼重設.
     */
    public function testInvalidTokenPasswordReset(): void
    {
        $result = $this->service->resetPassword([
            'token' => 'invalid-token',
            'email' => $this->user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('無效或過期的重設連結', $result['message']);
        $this->assertSame('INVALID_RESET_TOKEN', $result['error_code']);
    }

    /**
     * 測試不存在用戶的密碼重設.
     */
    public function testNonExistentUserPasswordReset(): void
    {
        $token = Password::createToken($this->user);

        $result = $this->service->resetPassword([
            'token' => $token,
            'email' => 'nonexistent@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('找不到該電子郵件的使用者', $result['message']);
        $this->assertSame('USER_NOT_FOUND', $result['error_code']);
    }

    /**
     * 測試特殊測試用戶情況
     */
    public function testSpecialTestUserCase(): void
    {
        $testUser = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('TestPassword123!')
        ]);

        $result = $this->service->resetPassword([
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('無效或過期的重設連結', $result['message']);
        $this->assertSame('INVALID_RESET_TOKEN', $result['error_code']);
    }

    /**
     * 測試 API 回應格式化.
     */
    public function testFormatApiResponse(): void
    {
        // 成功情況
        $successResult = [
            'success' => true,
            'message' => '密碼重設成功',
            'email' => 'test@example.com',
            'error_code' => null
        ];

        $apiResponse = $this->service->formatApiResponse($successResult);

        $this->assertSame([
            'status' => 'success',
            'message' => '密碼重設成功',
            'data' => [
                'email' => 'test@example.com',
            ],
        ], $apiResponse);

        // 失敗情況
        $failureResult = [
            'success' => false,
            'message' => '無效或過期的重設連結',
            'email' => 'test@example.com',
            'error_code' => 'INVALID_RESET_TOKEN'
        ];

        $apiResponse = $this->service->formatApiResponse($failureResult);

        $this->assertSame([
            'status' => 'error',
            'message' => '無效或過期的重設連結',
            'error_code' => 'INVALID_RESET_TOKEN',
        ], $apiResponse);
    }

    /**
     * 測試 Web 回應格式化.
     */
    public function testFormatWebResponse(): void
    {
        // 成功情況
        $successResult = [
            'success' => true,
            'message' => '密碼重設成功',
            'email' => 'test@example.com'
        ];

        $webResponse = $this->service->formatWebResponse($successResult, '/success');

        $this->assertSame([
            'success' => true,
            'message' => '密碼重設成功！',
            'redirect_url' => '/success',
        ], $webResponse);

        // 失敗情況
        $failureResult = [
            'success' => false,
            'message' => '無效或過期的重設連結',
            'email' => 'test@example.com'
        ];

        $webResponse = $this->service->formatWebResponse($failureResult);

        $this->assertSame([
            'success' => false,
            'message' => '無效或過期的重設連結',
            'errors' => [],
        ], $webResponse);
    }
}
