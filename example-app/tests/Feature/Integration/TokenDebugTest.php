<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Token Debug Test.
 *
 * 測試 Token 認證問題
 *
 * @internal
 *
 * @coversNothing
 */
final class TokenDebugTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試 Admin Token 認證.
     */
    public function testAdminTokenAuthentication(): void
    {
        // 建立 admin 用戶
        $adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'username' => 'admin_user',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        // Admin 登入
        $adminLoginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin_user',
            'password' => 'AdminPassword123!',
            'device_name' => 'Admin Device'
        ]);

        $adminLoginResponse->assertStatus(200);
        $adminToken = $adminLoginResponse->json('data.token');

        // 確認登入回應正確
        $adminLoginResponse->assertJsonPath('data.user.email', 'admin@example.com');

        // 使用 token 獲取 profile
        $adminProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$adminToken}"
        ]);

        $adminProfileResponse->assertStatus(200);

        // 檢查是否返回正確的用戶
        $profileData = $adminProfileResponse->json();
        dump('Admin profile response:', $profileData);

        $adminProfileResponse->assertJsonPath('data.user.email', 'admin@example.com');
    }

    /**
     * 測試 User Token 認證.
     */
    public function testUserTokenAuthentication(): void
    {
        // 建立 user 用戶
        $regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'username' => 'regular_user',
            'password' => Hash::make('UserPassword123!'),
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        // User 登入
        $userLoginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'regular_user',
            'password' => 'UserPassword123!',
            'device_name' => 'User Device'
        ]);

        $userLoginResponse->assertStatus(200);
        $userToken = $userLoginResponse->json('data.token');

        // 確認登入回應正確
        $userLoginResponse->assertJsonPath('data.user.email', 'user@example.com');

        // 使用 token 獲取 profile
        $userProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$userToken}"
        ]);

        $userProfileResponse->assertStatus(200);

        // 檢查是否返回正確的用戶
        $profileData = $userProfileResponse->json();
        dump('User profile response:', $profileData);

        $userProfileResponse->assertJsonPath('data.user.email', 'user@example.com');
    }
}
