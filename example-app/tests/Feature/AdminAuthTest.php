<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 單獨管理員認證測試.
 *
 * @internal
 *
 * @coversNothing
 */
final class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試單獨管理員認證.
     */
    public function testAdminAuthOnly(): void
    {
        // 創建管理員用戶
        $adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'username' => 'admin_user',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        // 登入管理員
        $adminLoginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin_user',
            'password' => 'AdminPassword123!',
            'device_name' => 'Admin Device'
        ]);

        $adminLoginResponse->assertStatus(200);
        $adminToken = $adminLoginResponse->json('data.token');
        $adminLoginUserData = $adminLoginResponse->json('data.user');

        // 使用管理員 token 獲取 profile
        $adminProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$adminToken}"
        ]);

        $adminProfileResponse->assertStatus(200);
        $adminProfileUserData = $adminProfileResponse->json('data.user');

        // 檢查 ID 是否一致
        $this->assertSame($adminLoginUserData['id'], $adminProfileUserData['id']);
        $this->assertSame($adminLoginUserData['email'], $adminProfileUserData['email']);
        $this->assertSame('admin@example.com', $adminProfileUserData['email']);
    }

    /**
     * 測試雙用戶場景.
     */
    public function testDualUserAuth(): void
    {
        // 創建用戶
        $adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'username' => 'admin_user',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        $regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'username' => 'regular_user',
            'password' => Hash::make('UserPassword123!'),
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        // 先登入管理員
        $adminLoginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin_user',
            'password' => 'AdminPassword123!',
            'device_name' => 'Admin Device'
        ]);

        $adminLoginResponse->assertStatus(200);
        $adminToken = $adminLoginResponse->json('data.token');
        $adminLoginUserData = $adminLoginResponse->json('data.user');

        // 然後登入普通用戶
        $userLoginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'regular_user',
            'password' => 'UserPassword123!',
            'device_name' => 'User Device'
        ]);

        $userLoginResponse->assertStatus(200);
        $userToken = $userLoginResponse->json('data.token');
        $userLoginUserData = $userLoginResponse->json('data.user');

        // 測試管理員 token 仍然有效
        $adminProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$adminToken}"
        ]);

        $adminProfileResponse->assertStatus(200);
        $adminProfileUserData = $adminProfileResponse->json('data.user');

        // 檢查管理員數據
        $this->assertSame($adminLoginUserData['id'], $adminProfileUserData['id'],
            'Admin user ID should be consistent');
        $this->assertSame('admin@example.com', $adminProfileUserData['email']);

        // 測試普通用戶 token
        $userProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$userToken}"
        ]);

        $userProfileResponse->assertStatus(200);
        $userProfileUserData = $userProfileResponse->json('data.user');

        // 檢查普通用戶數據
        $this->assertSame($userLoginUserData['id'], $userProfileUserData['id'],
            'Regular user ID should be consistent');
        $this->assertSame('user@example.com', $userProfileUserData['email']);
    }
}
