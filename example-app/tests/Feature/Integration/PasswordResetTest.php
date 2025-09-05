<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Password Reset Integration Test.
 *
 * 測試完整的密碼重設流程整合
 *
 * 涵蓋的流程：
 * 1. 忘記密碼請求
 * 2. 重設郵件發送和接收
 * 3. 重設連結驗證
 * 4. 新密碼設定
 * 5. 安全性檢查和 token 撤銷
 *
 * @internal
 *
 * @coversNothing
 */
final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試完整的密碼重設流程.
     *
     * 從忘記密碼到重設完成的完整流程
     */
    public function testCompletePasswordResetFlow(): void
    {
        Event::fake();
        Mail::fake();

        $originalPassword = 'OriginalPassword123!';
        $newPassword = 'NewResetPassword456!';

        // 第一步：建立測試用戶
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make($originalPassword),
            'email_verified_at' => now()
        ]);

        // 創建一些現有的 token 用來測試撤銷
        Sanctum::actingAs($user);
        $existingTokenResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'reset@example.com',
            'password' => $originalPassword,
            'device_name' => 'Existing Device'
        ]);
        $existingToken = $existingTokenResponse->json('data.token');

        // 第二步：請求忘記密碼
        $forgotPasswordResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset@example.com'
        ]);

        $forgotPasswordResponse->assertStatus(200);
        $forgotPasswordResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'email_sent',
                'sent_at'
            ]
        ]);

        // 第三步：驗證重設郵件已發送
        Mail::assertSent(\Illuminate\Auth\Notifications\ResetPassword::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });

        // 第四步：生成重設 token（模擬從郵件中獲取）
        $resetToken = Password::createToken($user);

        // 第五步：使用 token 重設密碼
        $resetPasswordResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'reset@example.com',
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);

        $resetPasswordResponse->assertStatus(200);
        $resetPasswordResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email'
                ],
                'password_reset_at',
                'tokens_revoked'
            ]
        ]);

        // 第六步：驗證密碼已在資料庫中更新
        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check($originalPassword, $user->password));

        // 第七步：驗證密碼重設事件已觸發
        Event::assertDispatched(PasswordReset::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });

        // 第八步：驗證現有 token 已被撤銷
        $testExistingTokenResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$existingToken}"
        ]);
        $testExistingTokenResponse->assertStatus(401);

        // 第九步：使用新密碼登入
        $newLoginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'reset@example.com',
            'password' => $newPassword,
            'device_name' => 'After Reset'
        ]);

        $newLoginResponse->assertStatus(200);
        $newToken = $newLoginResponse->json('data.token');

        // 第十步：使用新 token 存取受保護資源
        $profileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$newToken}"
        ]);
        $profileResponse->assertStatus(200);

        // 第十一步：驗證舊密碼無法登入
        $oldPasswordLoginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'reset@example.com',
            'password' => $originalPassword,
            'device_name' => 'Old Password Test'
        ]);
        $oldPasswordLoginResponse->assertStatus(401);
    }

    /**
     * 測試重複的忘記密碼請求
     *
     * 驗證速率限制和重複請求處理
     */
    public function testMultipleForgotPasswordRequests(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'multiple@example.com',
            'email_verified_at' => now()
        ]);

        // 第一步：第一次忘記密碼請求
        $firstRequest = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'multiple@example.com'
        ]);
        $firstRequest->assertStatus(200);

        // 第二步：立即再次請求（應該被速率限制）
        $immediateSecondRequest = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'multiple@example.com'
        ]);
        $immediateSecondRequest->assertStatus(429); // Too Many Requests

        // 第三步：等待一段時間後再次請求（模擬時間經過）
        $this->travel(61)->seconds();

        $delayedSecondRequest = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'multiple@example.com'
        ]);
        $delayedSecondRequest->assertStatus(200);

        // 第四步：驗證郵件發送次數
        Mail::assertSent(\Illuminate\Auth\Notifications\ResetPassword::class, 2);
    }

    /**
     * 測試無效的重設 token 處理.
     *
     * 驗證各種無效 token 的處理邏輯
     */
    public function testInvalidResetTokenHandling(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid@example.com',
            'email_verified_at' => now()
        ]);

        $newPassword = 'NewPassword123!';

        // 測試案例 1：完全無效的 token
        $invalidTokenResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token-12345',
            'email' => 'invalid@example.com',
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);
        $invalidTokenResponse->assertStatus(400);

        // 測試案例 2：有效但已使用的 token
        $validToken = Password::createToken($user);

        // 第一次使用 token（應該成功）
        $firstUseResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $validToken,
            'email' => 'invalid@example.com',
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);
        $firstUseResponse->assertStatus(200);

        // 第二次使用相同 token（應該失敗）
        $secondUseResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $validToken,
            'email' => 'invalid@example.com',
            'password' => 'AnotherPassword789!',
            'password_confirmation' => 'AnotherPassword789!'
        ]);
        $secondUseResponse->assertStatus(400);

        // 測試案例 3：錯誤的郵箱地址
        $anotherValidToken = Password::createToken($user);
        $wrongEmailResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $anotherValidToken,
            'email' => 'wrong@example.com',
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);
        $wrongEmailResponse->assertStatus(400);
    }

    /**
     * 測試過期 token 的處理.
     *
     * 驗證過期 token 的處理邏輯
     */
    public function testExpiredTokenHandling(): void
    {
        $user = User::factory()->create([
            'email' => 'expired@example.com',
            'email_verified_at' => now()
        ]);

        // 生成 token
        $token = Password::createToken($user);

        // 模擬 token 過期（通常是 60 分鐘）
        $this->travel(61)->minutes();

        $expiredTokenResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'expired@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!'
        ]);

        $expiredTokenResponse->assertStatus(400);
        $expiredTokenResponse->assertJsonStructure([
            'status',
            'message',
            'error' => [
                'code',
                'details'
            ]
        ]);
    }

    /**
     * 測試不存在用戶的忘記密碼請求
     *
     * 驗證對不存在郵箱的處理（安全考量）
     */
    public function testForgotPasswordForNonexistentUser(): void
    {
        Mail::fake();

        // 請求重設不存在的郵箱
        $nonExistentResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com'
        ]);

        // 應該回傳成功訊息（安全考量，不透露用戶是否存在）
        $nonExistentResponse->assertStatus(200);
        $nonExistentResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'email_sent',
                'sent_at'
            ]
        ]);

        // 但實際上不應該發送郵件
        Mail::assertNothingSent();
    }

    /**
     * 測試密碼重設的驗證規則.
     *
     * 驗證密碼重設時的各種驗證要求
     */
    public function testPasswordResetValidationRules(): void
    {
        $user = User::factory()->create([
            'email' => 'validation@example.com',
            'email_verified_at' => now()
        ]);

        $validToken = Password::createToken($user);

        // 測試案例 1：密碼太短
        $shortPasswordResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $validToken,
            'email' => 'validation@example.com',
            'password' => '123',
            'password_confirmation' => '123'
        ]);
        $shortPasswordResponse->assertStatus(422);
        $shortPasswordResponse->assertJsonValidationErrors(['password']);

        // 測試案例 2：密碼確認不符
        $mismatchPasswordResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $validToken,
            'email' => 'validation@example.com',
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'DifferentPassword456!'
        ]);
        $mismatchPasswordResponse->assertStatus(422);
        $mismatchPasswordResponse->assertJsonValidationErrors(['password']);

        // 測試案例 3：缺少必填欄位
        $missingFieldsResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $validToken,
            // 缺少 email, password, password_confirmation
        ]);
        $missingFieldsResponse->assertStatus(422);
        $missingFieldsResponse->assertJsonValidationErrors(['email', 'password']);

        // 測試案例 4：無效的郵箱格式
        $invalidEmailResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $validToken,
            'email' => 'invalid-email-format',
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!'
        ]);
        $invalidEmailResponse->assertStatus(422);
        $invalidEmailResponse->assertJsonValidationErrors(['email']);
    }

    /**
     * 測試軟刪除用戶的密碼重設.
     *
     * 驗證軟刪除用戶無法重設密碼
     */
    public function testPasswordResetForSoftDeletedUser(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'deleted@example.com',
            'email_verified_at' => now(),
            'deleted_at' => now()
        ]);

        // 請求忘記密碼
        $forgotPasswordResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'deleted@example.com'
        ]);

        // 應該回傳成功訊息（安全考量）
        $forgotPasswordResponse->assertStatus(200);

        // 但實際上不應該發送郵件
        Mail::assertNothingSent();

        // 即使手動生成 token 也無法重設
        $token = Password::createToken($user);
        $resetResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'deleted@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!'
        ]);

        $resetResponse->assertStatus(400);
    }

    /**
     * 測試並發密碼重設請求
     *
     * 驗證同一用戶的並發重設請求處理
     */
    public function testConcurrentPasswordResetRequests(): void
    {
        $user = User::factory()->create([
            'email' => 'concurrent@example.com',
            'email_verified_at' => now()
        ]);

        // 生成兩個不同的 token
        $token1 = Password::createToken($user);
        $token2 = Password::createToken($user);

        $newPassword1 = 'NewPassword123!';
        $newPassword2 = 'DifferentPassword456!';

        // 第一個重設請求
        $reset1Response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token1,
            'email' => 'concurrent@example.com',
            'password' => $newPassword1,
            'password_confirmation' => $newPassword1
        ]);

        // 第二個重設請求（應該失敗，因為第一個已經成功）
        $reset2Response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token2,
            'email' => 'concurrent@example.com',
            'password' => $newPassword2,
            'password_confirmation' => $newPassword2
        ]);

        // 第一個應該成功
        $reset1Response->assertStatus(200);

        // 第二個應該失敗（token 已失效）
        $reset2Response->assertStatus(400);

        // 驗證密碼是第一個重設的結果
        $user->refresh();
        $this->assertTrue(Hash::check($newPassword1, $user->password));
        $this->assertFalse(Hash::check($newPassword2, $user->password));
    }

    /**
     * 測試密碼重設後的安全檢查.
     *
     * 驗證重設後的安全措施
     */
    public function testSecurityMeasuresAfterPasswordReset(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'email' => 'security@example.com',
            'password' => Hash::make('OldPassword123!'),
            'email_verified_at' => now()
        ]);

        // 建立一些現有 token
        Sanctum::actingAs($user);
        $token1 = $this->postJson('/api/v1/auth/login', [
            'email' => 'security@example.com',
            'password' => 'OldPassword123!',
            'device_name' => 'Device 1'
        ])->json('data.token');

        $token2 = $this->postJson('/api/v1/auth/login', [
            'email' => 'security@example.com',
            'password' => 'OldPassword123!',
            'device_name' => 'Device 2'
        ])->json('data.token');

        // 執行密碼重設
        $resetToken = Password::createToken($user);
        $resetResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'security@example.com',
            'password' => 'NewSecurePassword456!',
            'password_confirmation' => 'NewSecurePassword456!'
        ]);

        $resetResponse->assertStatus(200);

        // 驗證所有現有 token 都已失效
        $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token1}"
        ])->assertStatus(401);

        $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token2}"
        ])->assertStatus(401);

        // 驗證安全事件已記錄
        Event::assertDispatched(PasswordReset::class);

        // 驗證新登入需要新密碼
        $newLoginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'security@example.com',
            'password' => 'NewSecurePassword456!',
            'device_name' => 'After Reset'
        ]);

        $newLoginResponse->assertStatus(200);
        $newToken = $newLoginResponse->json('data.token');

        // 新 token 應該可以正常工作
        $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$newToken}"
        ])->assertStatus(200);
    }
}
