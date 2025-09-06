<?php

namespace Tests\Feature\Contract;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 合約測試：POST /api/v1/auth/admin-login.
 *
 * 測試管理員專用登入 API 端點結構
 *
 * @group contract
 * @group auth
 * @group admin
 *
 * @internal
 *
 * @coversNothing
 */
final class AdminLoginContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試管理員成功登入的 API 響應結構.
     *
     * @return void
     */
    public function testAdminLoginSuccessResponseStructure()
    {
        // 準備測試數據
        $admin = User::factory()->create([
            'username' => 'testadmin',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'permissions' => ['manage_users', 'create_admins']
        ]);

        $requestData = [
            'username' => 'testadmin',
            'password' => 'AdminPassword123!'
        ];

        // 執行請求
        $response = $this->postJson('/api/v1/auth/admin-login', $requestData);

        // 驗證響應結構
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'username',
                        'name',
                        'permissions',
                        'created_at',
                        'updated_at'
                    ],
                    'token' => [
                        'access_token',
                        'token_type',
                        'expires_in'
                    ]
                ],
                'message'
            ])
            ->assertJson([
                'message' => '管理員登入成功'
            ]);

        // 驗證不返回敏感信息
        $response->assertJsonMissing(['password']);
    }

    /**
     * 測試管理員登入驗證失敗的響應結構.
     *
     * @return void
     */
    public function testAdminLoginValidationErrorResponseStructure()
    {
        // 發送無效數據
        $response = $this->postJson('/api/v1/auth/admin-login', []);

        // 驗證錯誤響應結構
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'username',
                    'password'
                ]
            ]);
    }

    /**
     * 測試管理員登入憑證錯誤的響應結構.
     *
     * @return void
     */
    public function testAdminLoginInvalidCredentialsResponseStructure()
    {
        // 準備測試數據
        User::factory()->create([
            'username' => 'testadmin',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        $requestData = [
            'username' => 'testadmin',
            'password' => 'WrongPassword'
        ];

        $response = $this->postJson('/api/v1/auth/admin-login', $requestData);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message'
            ])
            ->assertJson([
                'message' => '用戶名或密碼錯誤'
            ]);
    }

    /**
     * 測試不存在的管理員用戶登入響應結構.
     *
     * @return void
     */
    public function testAdminLoginUserNotFoundResponseStructure()
    {
        $requestData = [
            'username' => 'nonexistentadmin',
            'password' => 'AdminPassword123!'
        ];

        $response = $this->postJson('/api/v1/auth/admin-login', $requestData);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message'
            ])
            ->assertJson([
                'message' => '用戶名或密碼錯誤'
            ]);
    }

    /**
     * 測試管理員無需 email 即可登入（相對於一般用戶）.
     *
     * @return void
     */
    public function testAdminLoginWithoutEmailRequirement()
    {
        // 準備測試數據 - 管理員有 email 但不需要驗證
        $admin = User::factory()->create([
            'username' => 'adminnomail',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'email' => 'admin@example.com',
            'email_verified_at' => null // 沒有驗證但可以登入
        ]);

        $requestData = [
            'username' => 'adminnomail',
            'password' => 'AdminPassword123!'
        ];

        // 執行請求 - 應該成功，不需要 email
        $response = $this->postJson('/api/v1/auth/admin-login', $requestData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'username',
                        'name'
                    ],
                    'token'
                ],
                'message'
            ]);
    }
}
