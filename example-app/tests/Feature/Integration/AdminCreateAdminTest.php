<?php

namespace Tests\Feature\Integration;

use App\Models\SysUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 整合測試：管理員創建新管理員流程.
 *
 * 測試現有管理員通過 API 創建新管理員用戶的完整流程
 *
 * @group integration
 * @group admin
 * @group api
 *
 * @internal
 *
 * @coversNothing
 */
final class AdminCreateAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試管理員完整創建新管理員的流程.
     *
     * @return void
     */
    public function testCompleteAdminCreationWorkflow()
    {
        // 準備現有管理員
        $existingAdmin = SysUser::factory()->create([
            'username' => 'masteradmin',
            'permissions' => ['manage_users', 'create_admins', 'manage_system', 'view_reports']
        ]);

        // 使用 Sanctum 認證
        Sanctum::actingAs($existingAdmin);

        // 準備新管理員數據
        $newAdminData = [
            'username' => 'newadmin2024',
            'password' => 'SecureAdminPass123!',
            'password_confirmation' => 'SecureAdminPass123!',
            'name' => '2024新管理員',
            'permissions' => ['manage_users', 'view_reports']
        ];

        // 步驟 1: 創建新管理員
        $response = $this->postJson('/api/v1/admin/sys-users', $newAdminData);

        // 驗證創建響應
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'username',
                    'name',
                    'permissions',
                    'created_at'
                ],
                'message'
            ])
            ->assertJson([
                'data' => [
                    'username' => 'newadmin2024',
                    'name' => '2024新管理員'
                ]
            ]);

        // 步驟 2: 驗證資料庫中的新管理員
        $this->assertDatabaseHas('sys_users', [
            'username' => 'newadmin2024',
            'name' => '2024新管理員'
        ]);

        $newAdmin = SysUser::where('username', 'newadmin2024')->first();
        $this->assertNotNull($newAdmin);
        $this->assertTrue(Hash::check('SecureAdminPass123!', $newAdmin->password));
        $this->assertSame(['manage_users', 'view_reports'], $newAdmin->permissions);

        // 步驟 3: 驗證新管理員可以登入
        $loginResponse = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'newadmin2024',
            'password' => 'SecureAdminPass123!'
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'username', 'name'],
                    'token'
                ]
            ]);

        // 步驟 4: 驗證新管理員可以執行授權操作
        $newAdminToken = $loginResponse->json()['data']['token']['access_token'];

        $this->withHeader('Authorization', "Bearer {$newAdminToken}")
            ->getJson('/api/v1/admin/users')
            ->assertStatus(200); // 假設新管理員有 manage_users 權限
    }

    /**
     * 測試管理員創建時的權限驗證.
     *
     * @return void
     */
    public function testAdminCreationPermissionValidation()
    {
        // 準備沒有創建管理員權限的管理員
        $limitedAdmin = SysUser::factory()->create([
            'username' => 'limitedadmin',
            'permissions' => ['manage_users'] // 沒有 create_admins 權限
        ]);

        Sanctum::actingAs($limitedAdmin);

        $newAdminData = [
            'username' => 'unauthorizedadmin',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'name' => '未授權管理員'
        ];

        // 嘗試創建管理員
        $response = $this->postJson('/api/v1/admin/sys-users', $newAdminData);

        // 應該被拒絕
        $response->assertStatus(403)
            ->assertJson([
                'message' => '權限不足：無法創建管理員用戶'
            ]);

        // 驗證未創建新管理員
        $this->assertDatabaseMissing('sys_users', [
            'username' => 'unauthorizedadmin'
        ]);
    }

    /**
     * 測試創建管理員時的輸入驗證.
     *
     * @return void
     */
    public function testAdminCreationInputValidation()
    {
        $admin = SysUser::factory()->create([
            'permissions' => ['create_admins']
        ]);
        Sanctum::actingAs($admin);

        // 測試各種無效輸入情況
        $testCases = [
            // 缺少必要字段
            [
                'data' => [],
                'expectedErrors' => ['username', 'password', 'name']
            ],
            // 用戶名太短
            [
                'data' => [
                    'username' => 'ab',
                    'password' => 'ValidPass123!',
                    'password_confirmation' => 'ValidPass123!',
                    'name' => 'Valid Name'
                ],
                'expectedErrors' => ['username']
            ],
            // 密碼不匹配
            [
                'data' => [
                    'username' => 'validuser',
                    'password' => 'Password123!',
                    'password_confirmation' => 'DifferentPass123!',
                    'name' => 'Valid Name'
                ],
                'expectedErrors' => ['password']
            ],
            // 密碼太弱
            [
                'data' => [
                    'username' => 'validuser',
                    'password' => 'weak',
                    'password_confirmation' => 'weak',
                    'name' => 'Valid Name'
                ],
                'expectedErrors' => ['password']
            ]
        ];

        foreach ($testCases as $case) {
            $response = $this->postJson('/api/v1/admin/sys-users', $case['data']);

            $response->assertStatus(422);

            foreach ($case['expectedErrors'] as $field) {
                $response->assertJsonValidationErrors($field);
            }
        }
    }

    /**
     * 測試重複用戶名的處理.
     *
     * @return void
     */
    public function testDuplicateUsernameHandling()
    {
        $admin = SysUser::factory()->create([
            'permissions' => ['create_admins']
        ]);
        Sanctum::actingAs($admin);

        // 先創建一個管理員
        $existingAdmin = SysUser::factory()->create([
            'username' => 'existingadmin'
        ]);

        // 嘗試創建相同用戶名的管理員
        $response = $this->postJson('/api/v1/admin/sys-users', [
            'username' => 'existingadmin',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
            'name' => '重複用戶名測試'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username'])
            ->assertJson([
                'errors' => [
                    'username' => ['用戶名已存在']
                ]
            ]);
    }

    /**
     * 測試管理員創建後的權限繼承和限制.
     *
     * @return void
     */
    public function testAdminPermissionInheritanceAndLimits()
    {
        // 準備具有部分權限的管理員
        $creatorAdmin = SysUser::factory()->create([
            'permissions' => ['create_admins', 'manage_users', 'manage_system']
        ]);
        Sanctum::actingAs($creatorAdmin);

        // 嘗試創建具有相同和額外權限的管理員
        $response = $this->postJson('/api/v1/admin/sys-users', [
            'username' => 'superadmin',
            'password' => 'SuperPass123!',
            'password_confirmation' => 'SuperPass123!',
            'name' => '超級管理員',
            'permissions' => ['create_admins', 'manage_users', 'manage_system', 'view_reports'] // view_reports 不在創建者權限中
        ]);

        // 應該成功創建，但權限被限制為創建者的權限子集
        $response->assertStatus(201);

        $newAdmin = SysUser::where('username', 'superadmin')->first();

        // 驗證新管理員的權限只包含創建者有的權限
        $expectedPermissions = ['create_admins', 'manage_users', 'manage_system']; // view_reports 被過濾掉

        $this->assertSame($expectedPermissions, $newAdmin->permissions);
    }

    /**
     * 測試批量創建管理員的場景.
     *
     * @return void
     */
    public function testBatchAdminCreationScenario()
    {
        $admin = SysUser::factory()->create([
            'permissions' => ['create_admins', 'manage_users']
        ]);
        Sanctum::actingAs($admin);

        $adminUsers = [
            ['username' => 'admin1', 'name' => '管理員1'],
            ['username' => 'admin2', 'name' => '管理員2'],
            ['username' => 'admin3', 'name' => '管理員3']
        ];

        // 創建多個管理員
        foreach ($adminUsers as $userData) {
            $response = $this->postJson('/api/v1/admin/sys-users', [
                'username' => $userData['username'],
                'password' => 'AdminPass123!',
                'password_confirmation' => 'AdminPass123!',
                'name' => $userData['name'],
                'permissions' => ['manage_users']
            ]);

            $response->assertStatus(201);
        }

        // 驗證所有管理員都已創建
        foreach ($adminUsers as $userData) {
            $this->assertDatabaseHas('sys_users', [
                'username' => $userData['username'],
                'name' => $userData['name']
            ]);
        }

        // 驗證總計有4個管理員（包括創建者）
        $this->assertDatabaseCount('sys_users', 4);
    }
}
