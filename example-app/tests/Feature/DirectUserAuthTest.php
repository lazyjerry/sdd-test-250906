<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 直接創建用戶的認證測試.
 *
 * @internal
 *
 * @coversNothing
 */
final class DirectUserAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 使用直接創建而非 factory 的測試.
     */
    public function testDirectUserCreation(): void
    {
        // 直接創建管理員用戶
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'username' => 'admin_user',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'email_verified_at' => now(),
            'phone' => '123-456-7890'
        ]);

        // 直接創建普通用戶
        $regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'username' => 'regular_user',
            'password' => Hash::make('UserPassword123!'),
            'role' => 'user',
            'email_verified_at' => now(),
            'phone' => '098-765-4321'
        ]);

        // 驗證用戶 ID
        $this->assertSame(1, $adminUser->id);
        $this->assertSame(2, $regularUser->id);

        // 登入管理員
        $adminLoginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin_user',
            'password' => 'AdminPassword123!',
            'device_name' => 'Admin Device'
        ]);

        $adminLoginResponse->assertStatus(200);
        $adminToken = $adminLoginResponse->json('data.token');
        $adminLoginUserData = $adminLoginResponse->json('data.user');

        // 驗證登入回應
        $this->assertSame(1, $adminLoginUserData['id']);
        $this->assertSame('admin@example.com', $adminLoginUserData['email']);

        // 登入普通用戶
        $userLoginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'regular_user',
            'password' => 'UserPassword123!',
            'device_name' => 'User Device'
        ]);

        $userLoginResponse->assertStatus(200);
        $userToken = $userLoginResponse->json('data.token');
        $userLoginUserData = $userLoginResponse->json('data.user');

        // 驗證登入回應
        $this->assertSame(2, $userLoginUserData['id']);
        $this->assertSame('user@example.com', $userLoginUserData['email']);

        // 測試管理員 profile
        $adminProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$adminToken}"
        ]);

        $adminProfileResponse->assertStatus(200);
        $adminProfileUserData = $adminProfileResponse->json('data.user');

        // 檢查管理員數據
        $this->assertSame(1, $adminProfileUserData['id'],
            "Admin profile should return user ID 1, got {$adminProfileUserData['id']}");
        $this->assertSame('admin@example.com', $adminProfileUserData['email']);

        // 測試普通用戶 profile
        $userProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$userToken}"
        ]);

        $userProfileResponse->assertStatus(200);
        $userProfileUserData = $userProfileResponse->json('data.user');

        // 檢查普通用戶數據
        $this->assertSame(2, $userProfileUserData['id'],
            "User profile should return user ID 2, got {$userProfileUserData['id']}");
        $this->assertSame('user@example.com', $userProfileUserData['email']);
    }
}
