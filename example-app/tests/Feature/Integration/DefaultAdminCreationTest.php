<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 整合測試：預設 admin 用戶創建功能.
 *
 * 測試系統初始化時自動創建預設管理員用戶的完整流程
 *
 * @group integration
 * @group seeder
 * @group admin
 *
 * @internal
 *
 * @coversNothing
 */
final class DefaultAdminCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試運行 DefaultAdminSeeder 後創建預設管理員用戶.
     *
     * @return void
     */
    public function testDefaultAdminSeederCreatesAdminUser()
    {
        // 確保開始時沒有管理員用戶
        $this->assertDatabaseEmpty('users');

        // 運行預設管理員 seeder
        Artisan::call('db:seed', [
            '--class' => 'DefaultAdminSeeder'
        ]);

        // 驗證預設管理員用戶已創建
        $this->assertDatabaseHas('users', [
            'username' => 'admin',
            'name' => '系統管理員',
            'role' => 'super_admin'
        ]);

        // 驗證管理員用戶詳細信息
        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->username);
        $this->assertSame('系統管理員', $admin->name);
        $this->assertNotNull($admin->password);
        $this->assertTrue(Hash::check('admin123', $admin->password));
    }

    /**
     * 測試預設管理員用戶具有正確的權限.
     *
     * @return void
     */
    public function testDefaultAdminHasCorrectPermissions()
    {
        // 運行預設管理員 seeder
        Artisan::call('db:seed', [
            '--class' => 'DefaultAdminSeeder'
        ]);

        $admin = User::where('username', 'admin')->first();

        // 驗證管理員權限
        $expectedPermissions = [
            'manage_users',
            'manage_system',
            'create_admins',
            'view_all_data'
        ];

        foreach ($expectedPermissions as $permission) {
            $this->assertContains($permission, $admin->permissions ?? []);
        }
    }

    /**
     * 測試重複運行 seeder 不會創建重複的管理員用戶.
     *
     * @return void
     */
    public function testSeederDoesNotCreateDuplicateAdmin()
    {
        // 第一次運行 seeder
        Artisan::call('db:seed', [
            '--class' => 'DefaultAdminSeeder'
        ]);

        $this->assertDatabaseCount('users', 1);

        // 第二次運行 seeder
        Artisan::call('db:seed', [
            '--class' => 'DefaultAdminSeeder'
        ]);

        // 確保只有一個管理員用戶
        $this->assertDatabaseCount('users', 1);

        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);
    }

    /**
     * 測試使用預設管理員用戶可以成功登入.
     *
     * @return void
     */
    public function testDefaultAdminCanLoginSuccessfully()
    {
        // 運行預設管理員 seeder
        Artisan::call('db:seed', [
            '--class' => 'DefaultAdminSeeder'
        ]);

        // 嘗試使用預設管理員登入
        $response = $this->postJson('/api/v1/auth/admin-login', [
            'username' => 'admin',
            'password' => 'admin123'
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
                    'token'
                ]
            ]);

        // 驗證返回的用戶信息
        $responseData = $response->json();
        $this->assertSame('admin', $responseData['data']['user']['username']);
        $this->assertSame('系統管理員', $responseData['data']['user']['name']);
    }

    /**
     * 測試預設管理員創建後可以創建其他管理員用戶.
     *
     * @return void
     */
    public function testDefaultAdminCanCreateOtherAdmins()
    {
        // 運行預設管理員 seeder
        Artisan::call('db:seed', [
            '--class' => 'DefaultAdminSeeder'
        ]);

        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin, 'sanctum');

        // 使用預設管理員創建新的管理員
        $response = $this->postJson('/api/v1/admin/sys-users', [
            'username' => 'newadmin',
            'password' => 'NewAdminPass123!',
            'password_confirmation' => 'NewAdminPass123!',
            'name' => '新管理員',
            'permissions' => ['manage_users']
        ]);

        $response->assertStatus(201);

        // 驗證新管理員已創建
        $this->assertDatabaseHas('users', [
            'username' => 'newadmin',
            'name' => '新管理員'
        ]);

        // 確保現在有兩個管理員用戶
        $this->assertDatabaseCount('users', 2);
    }

    /**
     * 測試完整的資料庫初始化流程包含預設管理員
     *
     * @return void
     */
    public function testFullDatabaseSeedingIncludesDefaultAdmin()
    {
        // 運行完整的資料庫 seeding
        Artisan::call('db:seed');

        // 驗證預設管理員存在於完整的 seeding 中
        $this->assertDatabaseHas('users', [
            'username' => 'admin',
            'name' => '系統管理員'
        ]);

        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('admin123', $admin->password));
    }
}
