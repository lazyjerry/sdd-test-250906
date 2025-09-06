<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 管理員註冊 API 合約測試.
 *
 * 測試 POST /api/v1/admin/register 端點的請求/回應結構
 * 確保 API 合約符合 OpenAPI 規格定義
 *
 * @internal
 *
 * @coversNothing
 */
final class AdminRegisterContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試管理員註冊成功時的回應結構.
     */
    public function testAdminRegisterSuccessResponseStructure()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        $requestData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'user'
        ];

        $response = $this->postJson('/api/v1/admin/register', $requestData);

        // 期望狀態碼 201 Created
        $response->assertStatus(201);

        // 期望回應結構符合 OpenAPI 規格
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

        // 驗證回應內容格式
        $response->assertJson([
            'status' => 'success',
            'message' => '用戶註冊成功',
        ]);

        // 驗證回應資料型別
        $userData = $response->json('data.user');
        $this->assertIsInt($userData['id']);
        $this->assertIsString($userData['username']);
        $this->assertIsString($userData['email']);
        $this->assertIsString($userData['role']);
        $this->assertIsString($userData['created_at']);
        $this->assertIsString($userData['updated_at']);
    }

    /**
     * 測試管理員註冊驗證錯誤時的回應結構.
     */
    public function testAdminRegisterValidationErrorResponseStructure()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        $requestData = [
            'username' => '', // 無效的用戶名
            'email' => 'invalid-email', // 無效的信箱格式
            'password' => '123', // 密碼太簡單
            'role' => 'invalid-role' // 無效的角色
        ];

        $response = $this->postJson('/api/v1/admin/register', $requestData);

        // 期望狀態碼 422 Unprocessable Entity
        $response->assertStatus(422);

        // 期望驗證錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'username',
                'email',
                'password',
                'role',
            ],
        ]);

        // 驗證錯誤訊息格式
        $response->assertJson([
            'status' => 'error',
            'message' => '資料驗證失敗',
        ]);
    }

    /**
     * 測試重複 email 註冊時的回應結構.
     */
    public function testAdminRegisterDuplicateEmailResponseStructure()
    {
        // 創建現有用戶
        User::factory()->create([
            'email' => 'existing@example.com'
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        $requestData = [
            'username' => 'newuser',
            'email' => 'existing@example.com', // 重複的信箱
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'user'
        ];

        $response = $this->postJson('/api/v1/admin/register', $requestData);

        // 期望狀態碼 422
        $response->assertStatus(422);

        // 期望包含 email 驗證錯誤
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * 測試權限不足時的回應結構.
     */
    public function testAdminRegisterInsufficientPrivilegesResponseStructure()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($user);

        $requestData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'user'
        ];

        $response = $this->postJson('/api/v1/admin/register', $requestData);

        // 期望狀態碼 403 Forbidden
        $response->assertStatus(403);

        // 期望權限錯誤回應結構
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
    }

    /**
     * 測試未認證時的回應結構.
     */
    public function testAdminRegisterUnauthenticatedResponseStructure()
    {
        $requestData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'user'
        ];

        $response = $this->postJson('/api/v1/admin/register', $requestData);

        // 期望狀態碼 401 Unauthorized
        $response->assertStatus(401);
    }

    /**
     * 測試必填欄位缺失時的回應結構.
     */
    public function testAdminRegisterMissingRequiredFieldsResponseStructure()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        $requestData = [
            // 缺少所有必填欄位
        ];

        $response = $this->postJson('/api/v1/admin/register', $requestData);

        // 期望狀態碼 422
        $response->assertStatus(422);

        // 期望包含所有必填欄位的驗證錯誤
        $response->assertJsonValidationErrors([
            'username',
            'email',
            'password',
            'role'
        ]);
    }

    /**
     * 測試不同角色創建時的回應一致性.
     */
    public function testAdminRegisterRoleConsistencyResponseStructure()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        // 測試創建 user 角色
        $userRequestData = [
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'user'
        ];

        $userResponse = $this->postJson('/api/v1/admin/register', $userRequestData);
        $userResponse->assertStatus(201);

        // 測試創建 admin 角色
        $adminRequestData = [
            'username' => 'testadmin',
            'email' => 'testadmin@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin'
        ];

        $adminResponse = $this->postJson('/api/v1/admin/register', $adminRequestData);
        $adminResponse->assertStatus(201);

        // 驗證兩個回應的結構一致
        $userStructure = array_keys($userResponse->json('data.user'));
        $adminStructure = array_keys($adminResponse->json('data.user'));

        $this->assertSame($userStructure, $adminStructure);

        // 驗證角色欄位正確
        $this->assertSame('user', $userResponse->json('data.user.role'));
        $this->assertSame('admin', $adminResponse->json('data.user.role'));
    }
}
