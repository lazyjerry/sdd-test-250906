<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Role-based Registration Integration Test.
 *
 * 測試不同角色的註冊流程差異
 *
 * 涵蓋的測試：
 * 1. 一般用戶註冊 (預設角色)
 * 2. 管理員註冊新用戶 (含角色指定)
 * 3. 角色權限驗證
 * 4. 非法角色註冊防護
 *
 * @internal
 *
 * @coversNothing
 */
final class RoleBasedRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試一般用戶註冊的預設角色.
     *
     * 驗證普通註冊端點只能創建 user 角色
     */
    public function testUserRegistrationDefaultRole(): void
    {
        $registrationData = [
            'name' => 'Regular User',
            'username' => 'regularuser',
            'email' => 'regular@example.com',
            'phone' => '0900123456',
            'password' => 'UserPassword123!',
            'password_confirmation' => 'UserPassword123!',
        ];

        $response = $this->postJson('/api/v1/auth/register', $registrationData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'username',
                    'email',
                    'name',
                    'phone',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ],
        ]);

        // 驗證用戶資料
        $user = User::where('email', 'regular@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('user', $user->role); // 預設角色為 user
        $this->assertSame('Regular User', $user->name);
        $this->assertSame('regularuser', $user->username);
        $this->assertNull($user->email_verified_at); // 一般註冊需要驗證信箱

        // 驗證用戶權限
        $token = $response->json('data.token');
        $profileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);
        $profileResponse->assertStatus(200);

        // 驗證無法存取管理員端點
        $adminResponse = $this->getJson('/api/v1/admin/users', [
            'Authorization' => "Bearer {$token}"
        ]);
        $adminResponse->assertStatus(403);
    }

    /**
     * 測試一般用戶無法指定角色.
     *
     * 驗證普通註冊端點忽略角色參數
     */
    public function testUserRegistrationIgnoresRoleParameter(): void
    {
        $registrationData = [
            'name' => 'Wannabe Admin',
            'username' => 'wannabeadmin',
            'email' => 'wannabe@example.com',
            'password' => 'AdminPassword123!',
            'password_confirmation' => 'AdminPassword123!',
            'role' => 'admin', // 嘗試指定角色
        ];

        $response = $this->postJson('/api/v1/auth/register', $registrationData);

        $response->assertStatus(201);

        // 驗證角色參數被忽略，仍然是 user
        $user = User::where('email', 'wannabe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('user', $user->role); // 應該是預設的 user，不是 admin
    }

    /**
     * 測試管理員註冊新用戶功能.
     *
     * 驗證管理員可以創建任何角色的用戶
     */
    public function testAdminCanRegisterUsersWithRoles(): void
    {
        // 創建管理員用戶
        $admin = User::factory()->create([
            'username' => 'admin_user',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        // 測試案例 1：管理員創建普通用戶
        $userRegistrationData = [
            'name' => 'New User',
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'NewUserPassword123!',
            'password_confirmation' => 'NewUserPassword123!',
            'role' => 'user',
        ];

        $userResponse = $this->postJson('/api/v1/admin/register', $userRegistrationData);

        $userResponse->assertStatus(201);
        $userResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'username',
                    'email',
                    'name',
                    'role',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
                'created_by' => [
                    'id',
                    'username',
                ],
            ],
        ]);

        // 驗證新用戶
        $newUser = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertSame('user', $newUser->role);
        $this->assertNotNull($newUser->email_verified_at); // 管理員創建的用戶預設已驗證

        // 測試案例 2：管理員創建另一個管理員
        $adminRegistrationData = [
            'name' => 'New Admin',
            'username' => 'newadmin',
            'email' => 'newadmin@example.com',
            'password' => 'NewAdminPassword123!',
            'password_confirmation' => 'NewAdminPassword123!',
            'role' => 'admin',
        ];

        $adminResponse = $this->postJson('/api/v1/admin/register', $adminRegistrationData);

        $adminResponse->assertStatus(201);

        // 驗證新管理員
        $newAdmin = User::where('email', 'newadmin@example.com')->first();
        $this->assertNotNull($newAdmin);
        $this->assertSame('admin', $newAdmin->role);
        $this->assertNotNull($newAdmin->email_verified_at);

        // 驗證創建者記錄
        $this->assertSame($admin->id, $adminResponse->json('data.created_by.id'));
        $this->assertSame($admin->username, $adminResponse->json('data.created_by.username'));
    }

    /**
     * 測試普通用戶無法存取管理員註冊端點.
     *
     * 驗證權限控制機制
     */
    public function testUserCannotAccessAdminRegistration(): void
    {
        // 創建普通用戶
        $user = User::factory()->create([
            'username' => 'regular_user',
            'email' => 'regular@example.com',
            'password' => Hash::make('UserPassword123!'),
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($user);

        $registrationData = [
            'name' => 'Unauthorized Admin',
            'username' => 'unauthorizedadmin',
            'email' => 'unauthorized@example.com',
            'password' => 'UnauthorizedPassword123!',
            'password_confirmation' => 'UnauthorizedPassword123!',
            'role' => 'admin',
        ];

        $response = $this->postJson('/api/v1/admin/register', $registrationData);

        $response->assertStatus(403);
        $response->assertJsonStructure([
            'status',
            'message',
            'error_code',
        ]);
        $response->assertJson([
            'status' => 'error',
            'message' => '沒有權限執行此操作',
            'error_code' => 'INSUFFICIENT_PRIVILEGES',
        ]);

        // 驗證沒有創建用戶
        $unauthorizedUser = User::where('email', 'unauthorized@example.com')->first();
        $this->assertNull($unauthorizedUser);
    }

    /**
     * 測試未認證用戶無法存取管理員註冊端點.
     *
     * 驗證認證中間件保護
     */
    public function testUnauthenticatedUserCannotAccessAdminRegistration(): void
    {
        $registrationData = [
            'name' => 'Unauthenticated User',
            'username' => 'unauthuser',
            'email' => 'unauth@example.com',
            'password' => 'UnauthPassword123!',
            'password_confirmation' => 'UnauthPassword123!',
            'role' => 'user',
        ];

        $response = $this->postJson('/api/v1/admin/register', $registrationData);

        $response->assertStatus(401);

        // 驗證沒有創建用戶
        $unauthUser = User::where('email', 'unauth@example.com')->first();
        $this->assertNull($unauthUser);
    }

    /**
     * 測試管理員註冊時的資料驗證.
     *
     * 驗證角色參數的驗證規則
     */
    public function testAdminRegistrationValidation(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        // 測試案例 1：無效的角色
        $invalidRoleData = [
            'name' => 'Invalid Role User',
            'username' => 'invalidrole',
            'email' => 'invalid@example.com',
            'password' => 'InvalidPassword123!',
            'password_confirmation' => 'InvalidPassword123!',
            'role' => 'superadmin', // 無效角色
        ];

        $invalidRoleResponse = $this->postJson('/api/v1/admin/register', $invalidRoleData);
        $invalidRoleResponse->assertStatus(422);
        $invalidRoleResponse->assertJsonValidationErrors(['role']);

        // 測試案例 2：缺少角色參數
        $missingRoleData = [
            'name' => 'Missing Role User',
            'username' => 'missingrole',
            'email' => 'missing@example.com',
            'password' => 'MissingPassword123!',
            'password_confirmation' => 'MissingPassword123!',
            // 'role' => '缺少角色參數',
        ];

        $missingRoleResponse = $this->postJson('/api/v1/admin/register', $missingRoleData);
        $missingRoleResponse->assertStatus(422);
        $missingRoleResponse->assertJsonValidationErrors(['role']);

        // 驗證沒有創建任何用戶
        $this->assertNull(User::where('email', 'invalid@example.com')->first());
        $this->assertNull(User::where('email', 'missing@example.com')->first());
    }

    /**
     * 測試角色權限在註冊後的驗證.
     *
     * 驗證不同角色的用戶有正確的權限
     */
    public function testRolePermissionsAfterRegistration(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        // 創建新的管理員
        $newAdminData = [
            'name' => 'New Admin',
            'username' => 'testadmin',
            'email' => 'testadmin@example.com',
            'password' => 'TestAdminPassword123!',
            'password_confirmation' => 'TestAdminPassword123!',
            'role' => 'admin',
        ];

        $this->postJson('/api/v1/admin/register', $newAdminData);

        // 使用新管理員登入
        $newAdminLoginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'testadmin',
            'password' => 'TestAdminPassword123!',
            'device_name' => 'Test Device'
        ]);

        $newAdminLoginResponse->assertStatus(200);
        $newAdminToken = $newAdminLoginResponse->json('data.token');

        // 驗證新管理員可以存取管理員功能
        $adminUsersResponse = $this->getJson('/api/v1/admin/users', [
            'Authorization' => "Bearer {$newAdminToken}"
        ]);
        $adminUsersResponse->assertStatus(200);

        // 驗證新管理員可以創建其他用戶
        $this->postJson('/api/v1/admin/register', [
            'name' => 'Another User',
            'username' => 'anotheruser',
            'email' => 'another@example.com',
            'password' => 'AnotherPassword123!',
            'password_confirmation' => 'AnotherPassword123!',
            'role' => 'user',
        ], [
            'Authorization' => "Bearer {$newAdminToken}"
        ])->assertStatus(201);
    }
}
