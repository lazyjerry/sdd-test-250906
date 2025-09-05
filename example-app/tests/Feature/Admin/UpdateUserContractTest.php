<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Update User Contract Test.
 *
 * 驗證 PUT /api/v1/admin/users/{id} 端點的 API 合約
 *
 * 依據 specs/001-laravel12-example-app/specify.md 的 API 規範：
 * - PUT /api/v1/admin/users/{id}: 更新特定用戶資料（管理員權限）
 *
 * @internal
 *
 * @coversNothing
 */
final class UpdateUserContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試管理員更新用戶成功回應結構.
     *
     * 驗證 200 成功回應的 JSON 結構符合 OpenAPI 規格
     */
    public function testAdminUpdateUserSuccessResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // 建立目標用戶
        $targetUser = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 準備更新資料
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'user',
            'email_verified_at' => now()->toISOString(),
        ];

        // 發送請求
        $response = $this->putJson("/api/v1/admin/users/{$targetUser->id}", $updateData);

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構符合 OpenAPI 規格
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'email_verified_at',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);

        // 期望狀態為 success
        $response->assertJson([
            'status' => 'success'
        ]);

        // 期望返回更新後的用戶資料
        $response->assertJsonPath('data.user.id', $targetUser->id);
        $response->assertJsonPath('data.user.name', 'Updated Name');
        $response->assertJsonPath('data.user.email', 'updated@example.com');
    }

    /**
     * 測試管理員更新用戶角色回應結構.
     *
     * 驗證角色變更的回應結構
     */
    public function testAdminUpdateUserRoleResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立普通用戶
        $targetUser = User::factory()->create([
            'role' => 'user'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 將用戶升級為管理員
        $updateData = [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'role' => 'admin',
        ];

        // 發送請求
        $response = $this->putJson("/api/v1/admin/users/{$targetUser->id}", $updateData);

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構符合 OpenAPI 規格
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'email_verified_at',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);

        // 期望角色已更新
        $response->assertJsonPath('data.user.role', 'admin');
    }

    /**
     * 測試管理員更新不存在用戶回應結構.
     *
     * 驗證 404 用戶不存在回應的 JSON 結構
     */
    public function testAdminUpdateNonexistentUserResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 準備更新資料
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'user',
        ];

        // 發送請求（使用不存在的用戶 ID）
        $response = $this->putJson('/api/v1/admin/users/99999', $updateData);

        // 期望狀態碼 404 Not Found
        $response->assertStatus(404);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error' => [
                'code',
                'details'
            ]
        ]);

        // 期望狀態為 error
        $response->assertJson([
            'status' => 'error'
        ]);
    }

    /**
     * 測試管理員更新用戶驗證錯誤回應結構.
     *
     * 驗證 422 驗證錯誤回應的 JSON 結構
     */
    public function testAdminUpdateUserValidationErrorResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立目標用戶
        $targetUser = User::factory()->create([
            'role' => 'user'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 準備無效的更新資料
        $updateData = [
            'name' => '', // 空名稱
            'email' => 'invalid-email', // 無效郵箱格式
            'role' => 'invalid-role', // 無效角色
        ];

        // 發送請求
        $response = $this->putJson("/api/v1/admin/users/{$targetUser->id}", $updateData);

        // 期望狀態碼 422 Unprocessable Entity
        $response->assertStatus(422);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error' => [
                'code',
                'details',
                'validation_errors' => [
                    'name',
                    'email',
                    'role'
                ]
            ]
        ]);

        // 期望狀態為 error
        $response->assertJson([
            'status' => 'error'
        ]);
    }

    /**
     * 測試管理員更新用戶郵箱重複錯誤回應結構.
     *
     * 驗證郵箱已存在的 422 錯誤回應結構
     */
    public function testAdminUpdateUserEmailDuplicateResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立兩個用戶
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com'
        ]);

        $targetUser = User::factory()->create([
            'email' => 'target@example.com'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 嘗試更新為已存在的郵箱
        $updateData = [
            'name' => 'Valid Name',
            'email' => 'existing@example.com', // 已存在的郵箱
            'role' => 'user',
        ];

        // 發送請求
        $response = $this->putJson("/api/v1/admin/users/{$targetUser->id}", $updateData);

        // 期望狀態碼 422 Unprocessable Entity
        $response->assertStatus(422);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error' => [
                'code',
                'details',
                'validation_errors' => [
                    'email'
                ]
            ]
        ]);

        // 期望狀態為 error
        $response->assertJson([
            'status' => 'error'
        ]);
    }

    /**
     * 測試非管理員用戶更新其他用戶回應結構.
     *
     * 驗證 403 權限拒絕回應的 JSON 結構
     */
    public function testNonAdminUpdateUserForbiddenResponseStructure(): void
    {
        // 建立普通用戶
        $user = User::factory()->create([
            'role' => 'user'
        ]);

        // 建立目標用戶
        $targetUser = User::factory()->create([
            'role' => 'user'
        ]);

        // 使用 Sanctum 認證普通用戶
        Sanctum::actingAs($user);

        // 準備更新資料
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'admin', // 嘗試提升權限
        ];

        // 發送請求
        $response = $this->putJson("/api/v1/admin/users/{$targetUser->id}", $updateData);

        // 期望狀態碼 403 Forbidden
        $response->assertStatus(403);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error' => [
                'code',
                'details'
            ]
        ]);

        // 期望狀態為 error
        $response->assertJson([
            'status' => 'error'
        ]);
    }

    /**
     * 測試未認證用戶更新用戶回應結構.
     *
     * 驗證 401 未認證回應的 JSON 結構
     */
    public function testUnauthenticatedUpdateUserResponseStructure(): void
    {
        // 建立目標用戶
        $targetUser = User::factory()->create();

        // 準備更新資料
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'user',
        ];

        // 發送請求（沒有認證）
        $response = $this->putJson("/api/v1/admin/users/{$targetUser->id}", $updateData);

        // 期望狀態碼 401 Unauthorized
        $response->assertStatus(401);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error' => [
                'code',
                'details'
            ]
        ]);

        // 期望狀態為 error
        $response->assertJson([
            'status' => 'error'
        ]);
    }

    /**
     * 測試管理員重設用戶郵箱驗證狀態回應結構.
     *
     * 驗證管理員重設郵箱驗證的回應結構
     */
    public function testAdminResetUserEmailVerificationResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立已驗證的目標用戶
        $targetUser = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'user'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 重設郵箱驗證狀態
        $updateData = [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'role' => $targetUser->role,
            'email_verified_at' => null, // 重設驗證狀態
        ];

        // 發送請求
        $response = $this->putJson("/api/v1/admin/users/{$targetUser->id}", $updateData);

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構符合 OpenAPI 規格
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'email_verified_at',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);

        // 期望郵箱驗證狀態已重設
        $response->assertJsonPath('data.user.email_verified_at', null);
    }

    /**
     * 測試管理員無法降級自己回應結構.
     *
     * 驗證管理員無法修改自己角色的安全限制
     */
    public function testAdminCannotDemoteSelfResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 嘗試降級自己
        $updateData = [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'user', // 嘗試降級自己
        ];

        // 發送請求
        $response = $this->putJson("/api/v1/admin/users/{$admin->id}", $updateData);

        // 期望狀態碼 400 Bad Request（或其他適當的錯誤碼）
        $response->assertStatus(400);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error' => [
                'code',
                'details'
            ]
        ]);

        // 期望狀態為 error
        $response->assertJson([
            'status' => 'error'
        ]);
    }
}
