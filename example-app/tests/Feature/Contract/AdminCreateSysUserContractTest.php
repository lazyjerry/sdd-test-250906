<?php

namespace Tests\Feature\Contract;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 合約測試：POST /api/v1/admin/sys-users.
 *
 * 測試管理員創建新管理員用戶的 API 端點結構
 *
 * @group contract
 * @group admin
 *
 * @internal
 *
 * @coversNothing
 */
final class AdminCreateSysUserContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試成功創建管理員用戶的 API 響應結構.
     *
     * @return void
     */
    public function testCreateSysUserSuccessResponseStructure()
    {
        // 準備測試數據
        $admin = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['create_admins', 'manage_users', 'manage_system']
        ]);

        // 使用 Sanctum 為 User 創建 token
        $token = $admin->createToken('test-token')->plainTextToken;

        $requestData = [
            'username' => 'newadmin',
            'password' => 'AdminPassword123!',
            'password_confirmation' => 'AdminPassword123!',
            'name' => 'New Admin User',
            'permissions' => ['manage_users', 'manage_system']
        ];

        // 執行請求 - 使用 Authorization header
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/sys-users', $requestData);

        // 驗證響應結構
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'username',
                    'name',
                    'permissions',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'message' => '管理員用戶創建成功'
            ]);

        // 驗證不返回敏感信息
        $response->assertJsonMissing(['password']);
    }

    /**
     * 測試驗證失敗時的錯誤響應結構.
     *
     * @return void
     */
    public function testCreateSysUserValidationErrorResponseStructure()
    {
        // 準備測試數據
        $admin = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['create_admins', 'manage_users']
        ]);

        $token = $admin->createToken('test-token')->plainTextToken;

        // 發送無效數據
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/sys-users', []);

        // 驗證錯誤響應結構
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'username',
                    'password',
                    'name'
                ]
            ]);
    }

    /**
     * 測試未授權訪問的響應結構.
     *
     * @return void
     */
    public function testCreateSysUserUnauthorizedResponseStructure()
    {
        $requestData = [
            'username' => 'newadmin',
            'password' => 'AdminPassword123!',
            'name' => 'New Admin User'
        ];

        $response = $this->postJson('/api/v1/admin/sys-users', $requestData);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message'
            ]);
    }

    /**
     * 測試重複用戶名的錯誤響應結構.
     *
     * @return void
     */
    public function testCreateSysUserDuplicateUsernameResponseStructure()
    {
        // 準備測試數據
        $admin = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['create_admins', 'manage_users']
        ]);
        $existingAdmin = User::factory()->create(['username' => 'existingadmin']);

        $token = $admin->createToken('test-token')->plainTextToken;

        $requestData = [
            'username' => 'existingadmin', // 重複的用戶名
            'password' => 'AdminPassword123!',
            'password_confirmation' => 'AdminPassword123!',
            'name' => 'Duplicate Admin'
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/sys-users', $requestData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'username'
                ]
            ]);
    }
}
