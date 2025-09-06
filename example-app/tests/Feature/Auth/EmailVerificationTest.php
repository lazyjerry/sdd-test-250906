<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 設置測試用的前端 URL
        config(['app.frontend_url' => 'http://localhost:3000']);
    }

    public function testUserCanVerifyEmailViaPostApi()
    {
        Event::fake();

        // 創建未驗證的用戶
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // 生成驗證 URL 參數
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $parsedUrl = parse_url($verificationUrl);
        parse_str($parsedUrl['query'], $queryParams);

        // 準備 POST 請求數據
        $postData = [
            'id' => $user->id,
            'hash' => sha1($user->email),
            'expires' => $queryParams['expires'],
            'signature' => $queryParams['signature'],
        ];

        // 發送 POST 請求
        $response = $this->postJson('/api/v1/auth/verify-email', $postData);

        // 斷言回應
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '電子郵件驗證成功',
            ]);

        // 斷言用戶已驗證
        $this->assertNotNull($user->fresh()->email_verified_at);

        // 斷言事件被觸發
        Event::assertDispatched(Verified::class);
    }

    public function testUserCanVerifyEmailViaGetRoute()
    {
        Event::fake();

        // 創建未驗證的用戶
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // 生成驗證 URL
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // 發送 GET 請求
        $response = $this->get($verificationUrl);

        // 斷言回應狀態
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '電子郵件驗證成功',
            ]);

        // 斷言用戶已驗證
        $this->assertNotNull($user->fresh()->email_verified_at);

        // 斷言事件被觸發
        Event::assertDispatched(Verified::class);
    }

    public function testEmailVerificationFailsWithInvalidSignature()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // 無效的簽名數據
        $invalidData = [
            'id' => $user->id,
            'hash' => sha1($user->email),
            'expires' => Carbon::now()->addMinutes(60)->timestamp,
            'signature' => 'invalid_signature',
        ];

        // POST 請求測試
        $response = $this->postJson('/api/v1/auth/verify-email', $invalidData);
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => '無效或過期的驗證連結',
            ]);

        // GET 請求測試
        $getUrl = "/api/email/verify/{$user->id}/" . sha1($user->email) .
                  "?expires={$invalidData['expires']}&signature={$invalidData['signature']}";

        $response = $this->get($getUrl);
        $response->assertStatus(403); // 簽名驗證失敗
    }

    public function testEmailVerificationFailsWithExpiredLink()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // 生成過期的 URL
        $expiredUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->subMinutes(10), // 過期 10 分鐘
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $parsedUrl = parse_url($expiredUrl);
        parse_str($parsedUrl['query'], $queryParams);

        // POST 請求測試
        $expiredData = [
            'id' => $user->id,
            'hash' => sha1($user->email),
            'expires' => $queryParams['expires'],
            'signature' => $queryParams['signature'],
        ];

        $response = $this->postJson('/api/v1/auth/verify-email', $expiredData);
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => '無效或過期的驗證連結',
            ]);

        // GET 請求測試
        $response = $this->get($expiredUrl);
        $response->assertStatus(403); // 簽名過期
    }

    public function testEmailVerificationFailsWithWrongHash()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'wrong_hash']
        );

        $parsedUrl = parse_url($verificationUrl);
        parse_str($parsedUrl['query'], $queryParams);

        // POST 請求測試
        $wrongData = [
            'id' => $user->id,
            'hash' => 'wrong_hash',
            'expires' => $queryParams['expires'],
            'signature' => $queryParams['signature'],
        ];

        $response = $this->postJson('/api/v1/auth/verify-email', $wrongData);
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => '無效或過期的驗證連結',
            ]);
    }

    public function testAlreadyVerifiedEmailReturnsAppropriateMessage()
    {
        Event::fake();

        // 創建已驗證的用戶
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $parsedUrl = parse_url($verificationUrl);
        parse_str($parsedUrl['query'], $queryParams);

        // POST 請求測試
        $postData = [
            'id' => $user->id,
            'hash' => sha1($user->email),
            'expires' => $queryParams['expires'],
            'signature' => $queryParams['signature'],
        ];

        $response = $this->postJson('/api/v1/auth/verify-email', $postData);
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '電子郵件已經驗證過了',
            ]);

        // 斷言事件未被觸發（因為已經驗證過）
        Event::assertNotDispatched(Verified::class);
    }

    public function testVerificationRouteHasProperMiddleware()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // 測試沒有簽名的請求
        $response = $this->get("/api/email/verify/{$user->id}/" . sha1($user->email));
        $response->assertStatus(403); // 應該被 signed 中間件阻止

        // 測試 throttle 中間件 - 使用不同的用戶避免衝突
        $throttleTestUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $validUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $throttleTestUser->id, 'hash' => sha1($throttleTestUser->email)]
        );

        // 清除 throttle 狀態
        $this->artisan('cache:clear');

        // 發送 7 個請求（超過 throttle 限制 6 次/分鐘）
        $successfulRequests = 0;
        $throttledRequests = 0;

        for ($i = 0; $i < 7; ++$i) {
            $response = $this->get($validUrl);
            if (200 === $response->status()) {
                ++$successfulRequests;
            } elseif (429 === $response->status()) {
                ++$throttledRequests;
            }
        }

        // 至少應該有一些成功的請求和至少一個被 throttle 的請求
        $this->assertGreaterThan(0, $successfulRequests, 'Should have some successful requests');
        $this->assertGreaterThan(0, $throttledRequests, 'Should have some throttled requests');
    }

    public function testVerificationWorksWithDifferentUserTypes()
    {
        Event::fake();

        // 測試不同角色的用戶
        $adminUser = User::factory()->create([
            'email_verified_at' => null,
            'role' => 'admin',
        ]);

        $regularUser = User::factory()->create([
            'email_verified_at' => null,
            'role' => 'user',
        ]);

        foreach ([$adminUser, $regularUser] as $user) {
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)]
            );

            $response = $this->get($verificationUrl);
            $response->assertStatus(200);
            $this->assertNotNull($user->fresh()->email_verified_at);
        }
    }
}
