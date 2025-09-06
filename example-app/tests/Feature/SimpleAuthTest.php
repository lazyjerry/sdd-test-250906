<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 簡單認證測試.
 *
 * @internal
 *
 * @coversNothing
 */
final class SimpleAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試無認證情況
     */
    public function testUnauthenticatedAccess(): void
    {
        // 測試沒有 token 的情況
        $noTokenResponse = $this->getJson('/api/v1/users/profile');
        $noTokenResponse->assertStatus(401);

        // 測試無效 token 的情況
        $invalidTokenResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => 'Bearer invalid-token-12345'
        ]);
        $invalidTokenResponse->assertStatus(401);
    }

    /**
     * 測試基本認證工作.
     */
    public function testBasicAuth(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => Hash::make('password123'),
            'email_verified_at' => now()
        ]);

        // 登入
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');

        // 使用 token 獲取 profile
        $profileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);

        $profileResponse->assertStatus(200);
        $profileResponse->assertJsonPath('data.user.email', 'test@example.com');
    }
}
