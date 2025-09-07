<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Web 端郵件驗證測試.
 *
 * 測試 EmailVerificationController 的功能
 *
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

        // 創建未驗證的測試用戶
        $this->user = User::factory()->create([
            'email' => 'webtest@example.com',
            'email_verified_at' => null,
            'password' => Hash::make('TestPassword123!')
        ]);
    }

    /**
     * 測試成功的郵件驗證（Web 端點）.
     */
    public function testSuccessfulEmailVerificationWeb(): void
    {
        // 生成有效的驗證連結
        $expires = now()->addHour()->timestamp;
        $hash = sha1($this->user->getEmailForVerification());

        $url = url()->temporarySignedRoute(
            'verification.verify',
            now()->setTimestamp($expires),
            ['id' => $this->user->id, 'hash' => $hash]
        );

        // 發送 GET 請求到驗證連結
        $response = $this->get($url);

        // 驗證回應是成功的 HTML 頁面
        $response->assertStatus(200)
            ->assertViewIs('auth.email-verification-result')
            ->assertViewHas('success', true)
            ->assertViewHas('message', '電子郵件驗證成功')
            ->assertSee('驗證成功！')
            ->assertSee($this->user->username)
            ->assertSee($this->user->email);

        // 驗證用戶狀態已更新
        $this->user->refresh();
        $this->assertNotNull($this->user->email_verified_at);
    }

    /**
     * 測試無效連結的郵件驗證（Web 端點）.
     */
    public function testInvalidLinkEmailVerificationWeb(): void
    {
        // 使用無效簽名的連結 - 這會被 signed 中介軟體攔截返回 403
        $url = route('verification.verify', [
            'id' => $this->user->id,
            'hash' => sha1($this->user->getEmailForVerification()),
            'expires' => now()->addHour()->timestamp,
            'signature' => 'invalid-signature'
        ]);

        $response = $this->get($url);

        // signed 中介軟體會直接返回 403，而不是到達控制器
        $response->assertStatus(403);

        // 驗證用戶狀態未更新
        $this->user->refresh();
        $this->assertNull($this->user->email_verified_at);
    }

    /**
     * 測試已驗證用戶的郵件驗證（Web 端點）.
     */
    public function testAlreadyVerifiedEmailWeb(): void
    {
        // 先標記為已驗證
        $this->user->markEmailAsVerified();

        // 生成有效的驗證連結
        $expires = now()->addHour()->timestamp;
        $hash = sha1($this->user->getEmailForVerification());

        $url = url()->temporarySignedRoute(
            'verification.verify',
            now()->setTimestamp($expires),
            ['id' => $this->user->id, 'hash' => $hash]
        );

        $response = $this->get($url);

        $response->assertStatus(200)
            ->assertViewIs('auth.email-verification-result')
            ->assertViewHas('success', true)
            ->assertViewHas('message', '電子郵件已經驗證過了')
            ->assertSee('驗證成功！')
            ->assertSee('已經驗證過了');
    }

    /**
     * 測試 AJAX 請求的郵件驗證（JSON 回應）.
     */
    public function testEmailVerificationAjaxRequest(): void
    {
        // 生成有效的驗證連結
        $expires = now()->addHour()->timestamp;
        $hash = sha1($this->user->getEmailForVerification());

        $url = url()->temporarySignedRoute(
            'verification.verify',
            now()->setTimestamp($expires),
            ['id' => $this->user->id, 'hash' => $hash]
        );

        // 發送期望 JSON 回應的請求
        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '電子郵件驗證成功',
            ])
            ->assertJsonStructure([
                'user' => [
                    'id', 'username', 'email', 'email_verified_at'
                ]
            ]);

        // 驗證用戶狀態已更新
        $this->user->refresh();
        $this->assertNotNull($this->user->email_verified_at);
    }

    /**
     * 測試不存在用戶的驗證.
     */
    public function testNonExistentUserVerification(): void
    {
        // 使用不存在的用戶 ID，但簽名無效 - 會被 signed 中介軟體攔截
        $url = route('verification.verify', [
            'id' => 99999,
            'hash' => 'some-hash',
            'expires' => now()->addHour()->timestamp,
            'signature' => 'some-signature'
        ]);

        $response = $this->get($url);

        // signed 中介軟體會直接返回 403
        $response->assertStatus(403);
    }

    /**
     * 測試格式錯誤的驗證連結.
     */
    public function testMalformedVerificationLink(): void
    {
        // 缺少必需參數的路由，會被 signed 中介軟體攔截
        $url = route('verification.verify', [
            'id' => 'invalid',
            'hash' => 'some-hash'
            // 缺少 expires 和 signature
        ]);

        $response = $this->get($url);

        // signed 中介軟體會直接返回 403
        $response->assertStatus(403);
    }
}
