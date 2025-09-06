<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * 整合測試：EMAIL_VERIFICATION 環境變數控制.
 *
 * 測試 REQUIRE_EMAIL_VERIFICATION 環境變數控制郵件驗證功能的完整流程
 * 包括註冊、登入、郵件發送等場景
 *
 * @group integration
 * @group email
 * @group config
 *
 * @internal
 *
 * @coversNothing
 */
final class EmailVerificationToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
    }

    /**
     * 測試當 REQUIRE_EMAIL_VERIFICATION=true 時的註冊流程.
     *
     * @return void
     */
    public function testRegistrationWithEmailVerificationEnabled()
    {
        // 設置需要郵件驗證
        Config::set('auth.require_email_verification', true);

        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'UserPassword123!',
            'password_confirmation' => 'UserPassword123!',
            'name' => '測試用戶'
        ];

        // 註冊用戶
        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => '註冊成功，請檢查您的電子郵件以完成驗證'
            ]);

        // 驗證用戶已創建但未驗證
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        // 驗證發送了驗證郵件
        Mail::assertSent(\App\Mail\EmailVerification::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // 嘗試登入未驗證的用戶
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'UserPassword123!'
        ]);

        $loginResponse->assertStatus(403)
            ->assertJson([
                'message' => '請先驗證您的電子郵件地址'
            ]);
    }

    /**
     * 測試當 REQUIRE_EMAIL_VERIFICATION=false 時的註冊流程.
     *
     * @return void
     */
    public function testRegistrationWithEmailVerificationDisabled()
    {
        // 設置不需要郵件驗證
        Config::set('auth.require_email_verification', false);

        $userData = [
            'username' => 'testuser2',
            'email' => 'test2@example.com',
            'password' => 'UserPassword123!',
            'password_confirmation' => 'UserPassword123!',
            'name' => '測試用戶2'
        ];

        // 註冊用戶
        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => '註冊成功'
            ]);

        // 驗證用戶已創建且自動驗證
        $user = User::where('email', 'test2@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);

        // 驗證沒有發送驗證郵件
        Mail::assertNotSent(\App\Mail\EmailVerification::class);

        // 嘗試登入用戶，應該成功
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test2@example.com',
            'password' => 'UserPassword123!'
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'username', 'email', 'name'],
                    'token'
                ]
            ]);
    }

    /**
     * 測試環境變數變更後的行為一致性.
     *
     * @return void
     */
    public function testBehaviorConsistencyAfterConfigChange()
    {
        // 初始設置：需要驗證
        Config::set('auth.require_email_verification', true);

        // 註冊第一個用戶
        $this->postJson('/api/v1/auth/register', [
            'username' => 'user1',
            'email' => 'user1@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'name' => '用戶1'
        ]);

        // 變更設置：不需要驗證
        Config::set('auth.require_email_verification', false);

        // 註冊第二個用戶
        $this->postJson('/api/v1/auth/register', [
            'username' => 'user2',
            'email' => 'user2@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'name' => '用戶2'
        ]);

        $user1 = User::where('email', 'user1@example.com')->first();
        $user2 = User::where('email', 'user2@example.com')->first();

        // 用戶1 應該未驗證
        $this->assertNull($user1->email_verified_at);

        // 用戶2 應該已驗證
        $this->assertNotNull($user2->email_verified_at);

        // 用戶1 無法登入
        $this->postJson('/api/v1/auth/login', [
            'email' => 'user1@example.com',
            'password' => 'Password123!'
        ])->assertStatus(403);

        // 用戶2 可以登入
        $this->postJson('/api/v1/auth/login', [
            'email' => 'user2@example.com',
            'password' => 'Password123!'
        ])->assertStatus(200);
    }

    /**
     * 測試郵件驗證 API 在不同設定下的行為.
     *
     * @return void
     */
    public function testEmailVerificationApiBehaviorWithConfig()
    {
        // 設置需要郵件驗證
        Config::set('auth.require_email_verification', true);

        // 創建未驗證的用戶
        $user = User::factory()->create([
            'email' => 'verify@example.com',
            'email_verified_at' => null
        ]);

        // 測試重新發送驗證郵件
        $response = $this->postJson('/api/v1/auth/resend-verification', [
            'email' => 'verify@example.com'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '驗證郵件已重新發送'
            ]);

        Mail::assertSent(\App\Mail\EmailVerification::class);

        // 變更設置為不需要驗證
        Config::set('auth.require_email_verification', false);

        // 重置郵件假象
        Mail::fake();

        // 再次嘗試重新發送驗證郵件
        $response = $this->postJson('/api/v1/auth/resend-verification', [
            'email' => 'verify@example.com'
        ]);

        // 應該返回不需要驗證的消息
        $response->assertStatus(200)
            ->assertJson([
                'message' => '當前系統設定不需要郵件驗證'
            ]);

        // 不應該發送郵件
        Mail::assertNotSent(\App\Mail\EmailVerification::class);
    }

    /**
     * 測試手動驗證郵件功能在不同設定下的行為.
     *
     * @return void
     */
    public function testManualEmailVerificationWithConfig()
    {
        // 設置需要郵件驗證
        Config::set('auth.require_email_verification', true);

        // 創建未驗證的用戶
        $user = User::factory()->create([
            'email' => 'manual@example.com',
            'email_verified_at' => null
        ]);

        // 生成驗證令牌
        $verificationToken = 'test-verification-token';

        // 模擬點擊驗證鏈接
        $response = $this->postJson('/api/v1/auth/verify-email', [
            'token' => $verificationToken,
            'email' => 'manual@example.com'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '郵件驗證成功'
            ]);

        // 驗證用戶已驗證
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // 變更設置為不需要驗證
        Config::set('auth.require_email_verification', false);

        // 創建另一個用戶
        $user2 = User::factory()->create([
            'email' => 'manual2@example.com',
            'email_verified_at' => null
        ]);

        // 嘗試驗證（雖然系統不需要）
        $response = $this->postJson('/api/v1/auth/verify-email', [
            'token' => 'another-token',
            'email' => 'manual2@example.com'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '郵件驗證成功（系統當前不要求驗證）'
            ]);
    }

    /**
     * 測試批量用戶在配置變更前後的狀態.
     *
     * @return void
     */
    public function testBulkUsersStatusAcrossConfigChanges()
    {
        // 第一階段：需要驗證
        Config::set('auth.require_email_verification', true);

        $users = [];
        for ($i = 1; $i <= 3; ++$i) {
            $response = $this->postJson('/api/v1/auth/register', [
                'username' => "user{$i}",
                'email' => "user{$i}@example.com",
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'name' => "用戶{$i}"
            ]);
            $response->assertStatus(201);
            $users[] = User::where('email', "user{$i}@example.com")->first();
        }

        // 所有用戶應該未驗證
        foreach ($users as $user) {
            $this->assertNull($user->email_verified_at);
        }

        // 手動驗證第一個用戶
        $users[0]->email_verified_at = now();
        $users[0]->save();

        // 第二階段：不需要驗證
        Config::set('auth.require_email_verification', false);

        // 註冊新用戶
        $this->postJson('/api/v1/auth/register', [
            'username' => 'user4',
            'email' => 'user4@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'name' => '用戶4'
        ]);

        $user4 = User::where('email', 'user4@example.com')->first();

        // 測試登入狀態
        // 用戶1（已驗證）可以登入
        $this->postJson('/api/v1/auth/login', [
            'email' => 'user1@example.com',
            'password' => 'Password123!'
        ])->assertStatus(200);

        // 用戶2（未驗證，但系統現在不要求驗證）應該能登入
        $this->postJson('/api/v1/auth/login', [
            'email' => 'user2@example.com',
            'password' => 'Password123!'
        ])->assertStatus(200);

        // 用戶4（新註冊，自動驗證）可以登入
        $this->postJson('/api/v1/auth/login', [
            'email' => 'user4@example.com',
            'password' => 'Password123!'
        ])->assertStatus(200);
    }

    /**
     * 測試管理員功能不受郵件驗證設定影響
     *
     * @return void
     */
    public function testAdminFunctionsUnaffectedByEmailVerificationSetting()
    {
        // 無論設定如何，管理員功能都應該正常
        foreach ([true, false] as $requireVerification) {
            Config::set('auth.require_email_verification', $requireVerification);

            // 創建管理員（通過 seeder）
            $this->artisan('db:seed', ['--class' => 'DefaultAdminSeeder']);

            // 管理員登入
            $response = $this->postJson('/api/v1/auth/admin-login', [
                'username' => 'admin',
                'password' => 'admin123'
            ]);

            $response->assertStatus(200);

            // 清理
            \App\Models\SysUser::truncate();
        }
    }

    /**
     * 測試配置讀取的正確性.
     *
     * @return void
     */
    public function testConfigurationReadingAccuracy()
    {
        // 測試默認值
        $this->assertTrue(config('auth.require_email_verification'));

        // 測試環境變數覆蓋
        Config::set('auth.require_email_verification', false);
        $this->assertFalse(config('auth.require_email_verification'));

        Config::set('auth.require_email_verification', true);
        $this->assertTrue(config('auth.require_email_verification'));

        // 測試字符串值的轉換
        Config::set('auth.require_email_verification', 'true');
        $this->assertTrue(config('auth.require_email_verification'));

        Config::set('auth.require_email_verification', 'false');
        $this->assertFalse(config('auth.require_email_verification'));
    }
}
