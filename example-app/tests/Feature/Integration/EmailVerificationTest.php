<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Email Verification Integration Test.
 *
 * 測試完整的郵箱驗證流程整合
 *
 * 涵蓋的流程：
 * 1. 用戶註冊後發送驗證郵件
 * 2. 驗證連結生成和簽名
 * 3. 點擊驗證連結完成驗證
 * 4. 重新發送驗證郵件
 * 5. 驗證狀態變更和事件觸發
 *
 * @internal
 *
 * @coversNothing
 */
final class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試完整的郵箱驗證流程.
     *
     * 從註冊到驗證完成的完整流程
     */
    public function testCompleteEmailVerificationFlow(): void
    {
        Event::fake();
        Mail::fake();

        // 第一步：註冊新用戶
        $registrationData = [
            'name' => 'Verification Test User',
            'email' => 'verify@example.com',
            'password' => 'VerifyPassword123!',
            'password_confirmation' => 'VerifyPassword123!',
        ];

        $registerResponse = $this->postJson('/api/v1/auth/register', $registrationData);
        $registerResponse->assertStatus(201);

        // 獲取建立的用戶
        $user = User::where('email', 'verify@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at); // 初始未驗證

        // 第二步：驗證註冊時已發送驗證郵件
        Mail::assertSent(\Illuminate\Auth\Notifications\VerifyEmail::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });

        // 第三步：生成驗證 URL
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        // 第四步：使用 token 點擊驗證連結
        $token = $registerResponse->json('data.token');
        Sanctum::actingAs($user);

        $verifyResponse = $this->postJson('/api/v1/auth/verify-email', [
            'verification_url' => $verificationUrl
        ]);

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);

        // 第五步：驗證用戶狀態已更新
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // 第六步：驗證事件已觸發
        Event::assertDispatched(Verified::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });

        // 第七步：驗證已驗證用戶可以存取需要驗證的功能
        $profileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);
        $profileResponse->assertStatus(200);
        $profileResponse->assertJsonPath('data.user.email_verified_at', $user->email_verified_at->toISOString());
    }

    /**
     * 測試重新發送驗證郵件功能.
     *
     * 驗證用戶可以要求重新發送驗證郵件
     */
    public function testResendVerificationEmail(): void
    {
        Mail::fake();

        // 建立未驗證用戶
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        Sanctum::actingAs($user);

        // 第一步：請求重新發送驗證郵件
        $resendResponse = $this->postJson('/api/v1/auth/verify-email/resend');
        $resendResponse->assertStatus(200);
        $resendResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'email_sent',
                'sent_at'
            ]
        ]);

        // 第二步：驗證郵件已發送
        Mail::assertSent(\Illuminate\Auth\Notifications\VerifyEmail::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });

        // 第三步：驗證速率限制（短時間內不能重複發送）
        $immediateResendResponse = $this->postJson('/api/v1/auth/verify-email/resend');
        $immediateResendResponse->assertStatus(429); // Too Many Requests
    }

    /**
     * 測試已驗證用戶的驗證請求
     *
     * 驗證已驗證用戶的處理邏輯
     */
    public function testAlreadyVerifiedUserVerificationAttempt(): void
    {
        // 建立已驗證用戶
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($user);

        // 嘗試重新驗證
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $verifyResponse = $this->postJson('/api/v1/auth/verify-email', [
            'verification_url' => $verificationUrl
        ]);

        // 應該回傳成功但註明已驗證
        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'email_verified_at'
                ],
                'already_verified'
            ]
        ]);

        $verifyResponse->assertJsonPath('data.already_verified', true);
    }

    /**
     * 測試無效驗證連結的處理.
     *
     * 驗證各種無效驗證連結的處理邏輯
     */
    public function testInvalidVerificationLinkHandling(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        Sanctum::actingAs($user);

        // 測試案例 1：無效簽名
        $invalidSignatureUrl = URL::route('verification.verify', [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]) . '&signature=invalid';

        $response1 = $this->postJson('/api/v1/auth/verify-email', [
            'verification_url' => $invalidSignatureUrl
        ]);
        $response1->assertStatus(400);

        // 測試案例 2：錯誤的用戶 ID
        $wrongUserUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => 99999,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response2 = $this->postJson('/api/v1/auth/verify-email', [
            'verification_url' => $wrongUserUrl
        ]);
        $response2->assertStatus(404);

        // 測試案例 3：錯誤的 hash
        $wrongHashUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => 'wrong-hash',
            ]
        );

        $response3 = $this->postJson('/api/v1/auth/verify-email', [
            'verification_url' => $wrongHashUrl
        ]);
        $response3->assertStatus(400);
    }

    /**
     * 測試過期驗證連結的處理.
     *
     * 驗證過期連結的處理邏輯
     */
    public function testExpiredVerificationLinkHandling(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        Sanctum::actingAs($user);

        // 生成已過期的驗證 URL（1秒前過期）
        $expiredUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subSeconds(1),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'verification_url' => $expiredUrl
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'status',
            'message',
            'error' => [
                'code',
                'details'
            ]
        ]);

        // 驗證用戶仍未驗證
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /**
     * 測試跨用戶驗證攻擊防護.
     *
     * 驗證用戶A無法使用用戶B的驗證連結
     */
    public function testCrossUserVerificationAttackPrevention(): void
    {
        // 建立兩個未驗證用戶
        $userA = User::factory()->create([
            'email' => 'usera@example.com',
            'email_verified_at' => null
        ]);

        $userB = User::factory()->create([
            'email' => 'userb@example.com',
            'email_verified_at' => null
        ]);

        // 生成用戶B的驗證連結
        $userBVerificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $userB->getKey(),
                'hash' => sha1($userB->getEmailForVerification()),
            ]
        );

        // 用戶A嘗試使用用戶B的驗證連結
        Sanctum::actingAs($userA);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'verification_url' => $userBVerificationUrl
        ]);

        // 應該被拒絕
        $response->assertStatus(403);

        // 驗證兩個用戶都仍未驗證
        $userA->refresh();
        $userB->refresh();
        $this->assertNull($userA->email_verified_at);
        $this->assertNull($userB->email_verified_at);
    }

    /**
     * 測試未認證用戶的驗證嘗試.
     *
     * 驗證未登入用戶無法完成驗證
     */
    public function testUnauthenticatedVerificationAttempt(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        // 未認證的請求
        $response = $this->postJson('/api/v1/auth/verify-email', [
            'verification_url' => $verificationUrl
        ]);

        $response->assertStatus(401);

        // 驗證用戶仍未驗證
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /**
     * 測試驗證完成後的功能存取.
     *
     * 驗證郵箱驗證對功能存取的影響
     */
    public function testFunctionalityAccessAfterVerification(): void
    {
        // 建立未驗證用戶
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        Sanctum::actingAs($user);

        // 某些功能可能需要郵箱驗證
        $sensitiveResponse = $this->putJson('/api/v1/users/profile', [
            'name' => 'Updated Name',
            'email' => 'newemail@example.com'
        ]);

        // 根據系統設計，可能需要驗證才能修改郵箱
        $expectedStatus = $sensitiveResponse->status();

        // 完成郵箱驗證
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $verifyResponse = $this->postJson('/api/v1/auth/verify-email', [
            'verification_url' => $verificationUrl
        ]);
        $verifyResponse->assertStatus(200);

        // 驗證後再次嘗試敏感操作
        $postVerificationResponse = $this->putJson('/api/v1/users/profile', [
            'name' => 'Updated Name After Verification',
            'email' => 'verified@example.com'
        ]);

        // 驗證後應該可以進行敏感操作
        $this->assertLessThanOrEqual(400, $postVerificationResponse->status());
    }
}
