<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * 測試 Web 端密碼重設功能（直接調用版本）.
 *
 * 驗證 PasswordResetController 移除 HTTP 請求後的功能正確性
 *
 * @internal
 *
 * @coversNothing
 */
final class PasswordResetDirectCallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建測試用戶
        $this->user = User::factory()->create([
            'email' => 'directcall@example.com',
            'password' => Hash::make('OldPassword123!')
        ]);
    }

    /**
     * 測試成功的密碼重設（直接調用）.
     */
    public function testSuccessfulPasswordResetDirectCall(): void
    {
        // 生成重設 token
        $token = Password::createToken($this->user);

        // 發送密碼重設請求
        $response = $this->postJson('/password/reset', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        // 驗證響應
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '密碼重設成功！',
            ])
            ->assertJsonStructure([
                'redirect_url'
            ]);

        // 驗證密碼已更新
        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $this->user->password));
    }

    /**
     * 測試無效 token 的密碼重設（直接調用）.
     */
    public function testInvalidTokenPasswordResetDirectCall(): void
    {
        $response = $this->postJson('/password/reset', [
            'token' => 'invalid-token',
            'email' => $this->user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => '無效或過期的重設連結',
            ]);

        // 驗證密碼未更新
        $this->user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $this->user->password));
    }

    /**
     * 測試不存在用戶的密碼重設（直接調用）.
     */
    public function testNonExistentUserPasswordResetDirectCall(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->postJson('/password/reset', [
            'token' => $token,
            'email' => 'nonexistent@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => '找不到該電子郵件的使用者',
            ]);
    }

    /**
     * 測試表單驗證失敗（直接調用）.
     */
    public function testValidationFailureDirectCall(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->postJson('/password/reset', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'weak',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => '表單驗證失敗',
            ])
            ->assertJsonStructure([
                'errors' => [
                    'password'
                ]
            ]);
    }

    /**
     * 測試特殊測試用戶情況（直接調用）.
     */
    public function testSpecialTestUserCaseDirectCall(): void
    {
        // 創建測試用戶
        $testUser = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('TestPassword123!')
        ]);

        // 使用無效 token 但針對 test@example.com
        $response = $this->postJson('/password/reset', [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => '無效或過期的重設連結',
            ]);
    }
}
