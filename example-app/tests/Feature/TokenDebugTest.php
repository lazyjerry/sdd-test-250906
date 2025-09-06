<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Token 調試測試.
 *
 * @internal
 *
 * @coversNothing
 */
final class TokenDebugTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試 token 是否正確綁定到用戶.
     */
    public function testTokenBinding(): void
    {
        // 創建管理員用戶
        $adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'username' => 'admin_user',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        // 創建普通用戶
        $regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'username' => 'regular_user',
            'password' => Hash::make('UserPassword123!'),
            'role' => 'user',
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

        dump('Admin login user data:', $adminLoginUserData);

        // 使用管理員 token 獲取 profile
        $adminProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$adminToken}"
        ]);

        $adminProfileResponse->assertStatus(200);
        $adminProfileUserData = $adminProfileResponse->json('data.user');

        dump('Admin profile user data:', $adminProfileUserData);

        // 檢查 ID 是否一致
        $this->assertSame($adminLoginUserData['id'], $adminProfileUserData['id']);
        $this->assertSame($adminLoginUserData['email'], $adminProfileUserData['email']);
    }
}
