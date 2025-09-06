<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 登入角色隔離測試.
 *
 * 測試用戶登入和管理員登入 API 的角色隔離機制
 * 確保用戶和管理員無法互通使用對方的登入 API
 *
 * @group auth
 * @group role-isolation
 *
 * @internal
 *
 * @coversNothing
 */
final class LoginRoleIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試普通用戶無法使用管理員登入 API.
     */
    public function testUserCannotLoginViaAdminLoginApi()
    {
        // 建立普通用戶
        $user = User::factory()->create([
            'username' => 'normaluser',
            'email' => 'user@example.com',
            'password' => Hash::make('UserPassword123!'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // 嘗試使用管理員登入 API
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'normaluser',
            'password' => 'UserPassword123!',
        ]);

        // 應該返回錯誤
        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => '用戶名或密碼錯誤',
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    /**
     * 測試普通用戶使用 email 無法透過管理員登入 API 登入.
     */
    public function testUserCannotLoginViaAdminLoginApiWithEmail()
    {
        // 建立普通用戶
        $user = User::factory()->create([
            'username' => 'normaluser',
            'email' => 'user@example.com',
            'password' => Hash::make('UserPassword123!'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // 嘗試使用 email 透過管理員登入 API
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'user@example.com', // 使用 email
            'password' => 'UserPassword123!',
        ]);

        // 應該返回錯誤
        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => '用戶名或密碼錯誤',
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    /**
     * 測試管理員無法使用普通用戶登入 API.
     */
    public function testAdminCannotLoginViaUserLoginApi()
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'username' => 'testadmin',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'permissions' => ['manage_users'],
            'email_verified_at' => now(),
        ]);

        // 嘗試使用普通用戶登入 API
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'testadmin',
            'password' => 'AdminPassword123!',
        ]);

        // 應該返回錯誤
        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => '使用者名稱或密碼錯誤',
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    /**
     * 測試超級管理員無法使用普通用戶登入 API.
     */
    public function testSuperAdminCannotLoginViaUserLoginApi()
    {
        // 建立超級管理員用戶
        $superAdmin = User::factory()->create([
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('SuperAdminPassword123!'),
            'role' => 'super_admin',
            'permissions' => ['manage_users', 'manage_system'],
            'email_verified_at' => now(),
        ]);

        // 嘗試使用普通用戶登入 API
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'superadmin',
            'password' => 'SuperAdminPassword123!',
        ]);

        // 應該返回錯誤
        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => '使用者名稱或密碼錯誤',
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    /**
     * 測試管理員使用 email 無法透過普通用戶登入 API 登入.
     */
    public function testAdminCannotLoginViaUserLoginApiWithEmail()
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'username' => 'testadmin',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'permissions' => ['manage_users'],
            'email_verified_at' => now(),
        ]);

        // 嘗試使用 email 透過普通用戶登入 API
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin@example.com', // 使用 email
            'password' => 'AdminPassword123!',
        ]);

        // 應該返回錯誤
        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => '使用者名稱或密碼錯誤',
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    /**
     * 測試普通用戶可以透過正確的用戶登入 API 成功登入.
     */
    public function testUserCanLoginViaUserLoginApi()
    {
        // 建立普通用戶
        $user = User::factory()->create([
            'username' => 'normaluser',
            'email' => 'user@example.com',
            'password' => Hash::make('UserPassword123!'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // 使用正確的用戶登入 API
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'normaluser',
            'password' => 'UserPassword123!',
        ]);

        // 應該成功登入
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '登入成功',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'username',
                        'email',
                        'name',
                    ],
                    'token',
                    'expires_at',
                ],
            ]);
    }

    /**
     * 測試管理員可以透過正確的管理員登入 API 成功登入.
     */
    public function testAdminCanLoginViaAdminLoginApi()
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'username' => 'testadmin',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'permissions' => ['manage_users'],
            'email_verified_at' => now(),
        ]);

        // 使用正確的管理員登入 API
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'testadmin',
            'password' => 'AdminPassword123!',
        ]);

        // 應該成功登入
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '管理員登入成功',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'username',
                        'email',
                        'name',
                        'role',
                        'permissions',
                    ],
                    'token',
                    'token_type',
                    'expires_in',
                    'expires_at',
                ],
            ]);
    }

    /**
     * 測試超級管理員可以透過管理員登入 API 成功登入.
     */
    public function testSuperAdminCanLoginViaAdminLoginApi()
    {
        // 建立超級管理員用戶
        $superAdmin = User::factory()->create([
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('SuperAdminPassword123!'),
            'role' => 'super_admin',
            'permissions' => ['manage_users', 'manage_system'],
            'email_verified_at' => now(),
        ]);

        // 使用管理員登入 API
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'superadmin',
            'password' => 'SuperAdminPassword123!',
        ]);

        // 應該成功登入
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '管理員登入成功',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'username',
                        'email',
                        'name',
                        'role',
                        'permissions',
                    ],
                    'token',
                    'token_type',
                    'expires_in',
                    'expires_at',
                ],
            ]);

        // 驗證返回的 role 是 super_admin
        $response->assertJsonPath('data.user.role', 'super_admin');
    }

    /**
     * 測試被軟刪除的普通用戶無法登入.
     */
    public function testSoftDeletedUserCannotLogin()
    {
        // 建立並軟刪除普通用戶
        $user = User::factory()->create([
            'username' => 'deleteduser',
            'email' => 'deleted@example.com',
            'password' => Hash::make('UserPassword123!'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $user->delete(); // 軟刪除

        // 嘗試登入
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'deleteduser',
            'password' => 'UserPassword123!',
        ]);

        // 應該返回錯誤
        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => '使用者名稱或密碼錯誤',
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    /**
     * 測試被軟刪除的管理員無法登入.
     */
    public function testSoftDeletedAdminCannotLogin()
    {
        // 建立並軟刪除管理員用戶
        $admin = User::factory()->create([
            'username' => 'deletedadmin',
            'email' => 'deletedadmin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'permissions' => ['manage_users'],
            'email_verified_at' => now(),
        ]);
        $admin->delete(); // 軟刪除

        // 嘗試管理員登入
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'deletedadmin',
            'password' => 'AdminPassword123!',
        ]);

        // 應該返回錯誤
        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => '用戶名或密碼錯誤',
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }
}
