<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * AuthController 密碼重設功能測試（重構後）.
 *
 * 驗證使用共用服務後的 API 端點功能
 *
 * @internal
 *
 * @coversNothing
 */
final class AuthPasswordResetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建測試用戶
        $this->user = User::factory()->create([
            'email' => 'authservice@example.com',
            'password' => Hash::make('OldPassword123!')
        ]);
    }

    /**
     * 測試成功的密碼重設 API.
     */
    public function testSuccessfulPasswordResetApi(): void
    {
        // 生成重設 token
        $token = Password::createToken($this->user);

        // 發送密碼重設請求
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        // 驗證響應
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '密碼重設成功',
                'data' => [
                    'email' => $this->user->email,
                ],
            ]);

        // 驗證密碼已更新
        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $this->user->password));
    }

    /**
     * 測試無效 token 的密碼重設 API.
     */
    public function testInvalidTokenPasswordResetApi(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $this->user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => '無效或過期的重設連結',
                'error_code' => 'INVALID_RESET_TOKEN',
            ]);

        // 驗證密碼未更新
        $this->user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $this->user->password));
    }

    /**
     * 測試不存在用戶的密碼重設 API.
     */
    public function testNonExistentUserPasswordResetApi(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'nonexistent@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => '找不到該電子郵件的使用者',
                'error_code' => 'USER_NOT_FOUND',
            ]);
    }

    /**
     * 測試特殊測試用戶情況 API.
     */
    public function testSpecialTestUserCaseApi(): void
    {
        // 創建測試用戶
        $testUser = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('TestPassword123!')
        ]);

        // 使用無效 token 但針對 test@example.com
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => '無效或過期的重設連結',
                'error_code' => 'INVALID_RESET_TOKEN',
            ]);
    }
}
