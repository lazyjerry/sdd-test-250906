<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 整合測試：管理員無需 email 登入流程.
 *
 * 測試管理員可以僅使用用戶名和密碼登入，無需 email 驗證的完整流程
 * 對比一般用戶需要 email 驗證的流程
 *
 * @group integration
 * @group auth
 * @group admin
 *
 * @internal
 *
 * @coversNothing
 */
final class AdminNoEmailLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試管理員可以使用用戶名登入（無需 email）.
     *
     * @return void
     */
    public function testAdminCanLoginWithUsernameOnly()
    {
        // 創建管理員用戶（有 email 但不需要驗證）
        $admin = User::factory()->create([
            'username' => 'adminuser',
            'password' => Hash::make('AdminPassword123!'),
            'email' => 'admin@example.com',
            'role' => 'admin',
            'email_verified_at' => null, // 未驗證但可以登入
            'name' => '管理員用戶',
            ]);

        // 使用用戶名和密碼登入
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'adminuser',
            'password' => 'AdminPassword123!'
        ]);

        // 驗證登入成功
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'username',
                        'name'
                    ],
                    'token' => [
                        'access_token',
                        'token_type'
                    ]
                ],
                'message'
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'username' => 'adminuser',
                        'name' => '管理員用戶'
                    ]
                ],
                'message' => '管理員登入成功'
            ]);

        // 驗證 token 有效性
        $token = $response->json()['data']['token']['access_token'];
        $this->assertNotEmpty($token);

        // 使用 token 訪問受保護的資源
        $protectedResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');

        $protectedResponse->assertStatus(200);
    }

    /**
     * 測試管理員即使有 email 也可以僅用用戶名登入.
     *
     * @return void
     */
    public function testAdminWithEmailCanStillLoginWithUsername()
    {
        // 創建有 email 的管理員用戶
        $admin = User::factory()->create([
            'username' => 'adminwithemail',
            'password' => Hash::make('AdminPassword123!'),
            'email' => 'admin@example.com',
            'role' => 'admin',
            'name' => '有郵箱的管理員',
            ]);

        // 使用用戶名登入（不使用 email）
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'adminwithemail',
            'password' => 'AdminPassword123!'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user' => [
                        'username' => 'adminwithemail',
                        'name' => '有郵箱的管理員'
                    ]
                ]
            ]);
    }

    /**
     * 測試對比：一般用戶需要 email 登入.
     *
     * @return void
     */
    public function testRegularUserRequiresEmailForLogin()
    {
        // 創建一般用戶（需要 email）
        $user = User::factory()->create([
            'username' => 'regularuser',
            'email' => 'user@example.com',
            'password' => Hash::make('UserPassword123!'),
            'email_verified_at' => now()
        ]);

        // 嘗試僅使用用戶名登入一般用戶端點
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'regularuser',
            'password' => 'UserPassword123!'
        ]);

        // 應該失敗或要求 email
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // 使用 email 登入應該成功
        $emailResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'UserPassword123!'
        ]);

        $emailResponse->assertStatus(200);
    }

    /**
     * 測試管理員登入不受 REQUIRE_EMAIL_VERIFICATION 設定影響
     *
     * @return void
     */
    public function testAdminLoginIgnoresEmailVerificationSetting()
    {
        // 設置需要 email 驗證
        Config::set('auth.require_email_verification', true);

        // 創建未驗證 email 的管理員
        $admin = User::factory()->create([
            'username' => 'unverifiedadmin',
            'password' => Hash::make('AdminPassword123!'),
            'email' => 'unverified@example.com',
            'email_verified_at' => null, // 未驗證
            'role' => 'admin',
            'name' => '未驗證郵箱的管理員',
        ]);

        // 管理員應該仍可登入
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'unverifiedadmin',
            'password' => 'AdminPassword123!'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user' => [
                        'username' => 'unverifiedadmin'
                    ]
                ]
            ]);

        // 對比：一般用戶在相同設定下應該被拒絕
        $user = User::factory()->create([
            'email' => 'unverifieduser@example.com',
            'password' => Hash::make('UserPassword123!'),
            'email_verified_at' => null
        ]);

        $userResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'unverifieduser@example.com',
            'password' => 'UserPassword123!'
        ]);

        $userResponse->assertStatus(403)
            ->assertJson([
                'message' => '請先驗證您的電子郵件地址'
            ]);
    }

    /**
     * 測試管理員登入的錯誤處理.
     *
     * @return void
     */
    public function testAdminLoginErrorHandling()
    {
        $admin = User::factory()->create([
            'username' => 'testadmin',
            'password' => Hash::make('CorrectPassword123!'),
            'role' => 'admin',
            ]);

        // 測試錯誤的密碼
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'testadmin',
            'password' => 'WrongPassword'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => '用戶名或密碼錯誤'
            ]);

        // 測試不存在的用戶名
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'nonexistent',
            'password' => 'SomePassword123!'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => '用戶名或密碼錯誤'
            ]);

        // 測試空的用戶名或密碼
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => '',
            'password' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'password']);
    }

    /**
     * 測試管理員登入後的會話管理.
     *
     * @return void
     */
    public function testAdminSessionManagementAfterLogin()
    {
        $admin = User::factory()->create([
            'username' => 'sessionadmin',
            'password' => Hash::make('SessionPassword123!')
            'role' => 'admin',
            ]);

        // 登入
        $loginResponse = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'sessionadmin',
            'password' => 'SessionPassword123!'
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json()['data']['token']['access_token'];

        // 驗證 token 可以用於 API 調用
        $apiResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/profile');

        $apiResponse->assertStatus(200);

        // 測試登出
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200);

        // 驗證 token 在登出後無效
        $invalidTokenResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/profile');

        $invalidTokenResponse->assertStatus(401);
    }

    /**
     * 測試管理員多重登入場景.
     *
     * @return void
     */
    public function testAdminMultipleLoginSessions()
    {
        $admin = User::factory()->create([
            'username' => 'multiadmin',
            'password' => Hash::make('MultiPassword123!')
            'role' => 'admin',
            ]);

        $tokens = [];

        // 創建多個登入會話
        for ($i = 1; $i <= 3; ++$i) {
            $response = $this->postJson('/api/v1/auth/admin-login', [
                'username' => 'multiadmin',
                'password' => 'MultiPassword123!'
            ]);

            $response->assertStatus(200);
            $tokens[] = $response->json()['data']['token']['access_token'];
        }

        // 驗證所有 token 都有效
        foreach ($tokens as $token) {
            $apiResponse = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/v1/admin/profile');

            $apiResponse->assertStatus(200);
        }

        // 使用其中一個 token 登出
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$tokens[0]}")
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200);

        // 驗證登出的 token 無效，其他仍有效
        $this->withHeader('Authorization', "Bearer {$tokens[0]}")
            ->getJson('/api/v1/admin/profile')
            ->assertStatus(401);

        $this->withHeader('Authorization', "Bearer {$tokens[1]}")
            ->getJson('/api/v1/admin/profile')
            ->assertStatus(200);
    }
}
