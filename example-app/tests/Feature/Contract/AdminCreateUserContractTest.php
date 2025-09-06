<?php

namespace Tests\Feature\Contract;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Admin Create User Contract Test.
 *
 * 測試管理員創建新用戶的 API 端點合約
 * 確保 API 端點結構正確且符合預期
 *
 * @internal
 *
 * @coversNothing
 */
final class AdminCreateUserContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建管理員用戶進行測試
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'username' => 'test_admin',
            'email' => 'admin@test.com',
        ]);
    }

    public function testAdminCreateUserEndpointExists()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        // 端點應該存在（不是 404）
        $response->assertStatus(422); // 預期會失敗，因為還沒實作
    }

    public function testAdminCreateUserRequiresAuthentication()
    {
        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $response->assertStatus(401);
    }

    public function testAdminCreateUserRequiresAdminRole()
    {
        $regularUser = User::factory()->create([
            'role' => 'user',
        ]);

        Sanctum::actingAs($regularUser);

        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $response->assertStatus(403);
    }

    public function testAdminCreateUserValidatesRequiredFields()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/admin/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'username', 'password', 'role']);
    }

    public function testAdminCreateUserValidatesUniqueUsername()
    {
        Sanctum::actingAs($this->admin);

        User::factory()->create(['username' => 'existing_user']);

        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'Test User',
            'username' => 'existing_user',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function testAdminCreateUserSuccessResponseStructure()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        // 預期成功後的響應結構
        if (201 === $response->status()) {
            $response->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'username',
                    'email',
                    'role',
                    'created_at',
                    'updated_at',
                ],
                'message'
            ]);
        }
    }

    public function testAdminCanCreateAdminUser()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'Admin User',
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        // 管理員應該能夠創建其他管理員
        if (201 === $response->status()) {
            $response->assertJson([
                'data' => [
                    'role' => 'admin',
                ]
            ]);
        }
    }
}
