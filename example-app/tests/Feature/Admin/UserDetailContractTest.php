<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * User Detail Contract Test.
 *
 * 驗證 GET /api/v1/admin/users/{id} 端點的 API 合約
 *
 * 依據 specs/001-laravel12-example-app/specify.md 的 API 規範：
 * - GET /api/v1/admin/users/{id}: 取得特定用戶詳細資料（管理員權限）
 *
 * @internal
 *
 * @coversNothing
 */
final class UserDetailContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試管理員取得用戶詳細資料成功回應結構.
     *
     * 驗證 200 成功回應的 JSON 結構符合 OpenAPI 規格
     */
    public function testAdminGetUserDetailSuccessResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // 建立目標用戶
        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'email' => 'target@example.com',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 發送請求
        $response = $this->getJson("/api/v1/admin/users/{$targetUser->id}");

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
                    'updated_at',
                    'last_login_at',
                    'api_tokens_count'
                ]
            ]
        ]);

        // 期望狀態為 success
        $response->assertJson([
            'status' => 'success'
        ]);

        // 期望返回的是目標用戶資料
        $response->assertJsonPath('data.user.id', $targetUser->id);
        $response->assertJsonPath('data.user.email', $targetUser->email);
        $response->assertJsonPath('data.user.name', 'Target User');
    }

    /**
     * 測試管理員取得不存在用戶回應結構.
     *
     * 驗證 404 用戶不存在回應的 JSON 結構
     */
    public function testAdminGetNonexistentUserResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 發送請求（使用不存在的用戶 ID）
        $response = $this->getJson('/api/v1/admin/users/99999');

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
     * 測試管理員取得已刪除用戶回應結構.
     *
     * 驗證軟刪除用戶的回應結構
     */
    public function testAdminGetDeletedUserResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立並軟刪除目標用戶
        $deletedUser = User::factory()->create([
            'email' => 'deleted@example.com',
            'role' => 'user',
        ]);
        $deletedUser->delete(); // 軟刪除

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 發送請求
        $response = $this->getJson("/api/v1/admin/users/{$deletedUser->id}");

        // 期望狀態碼 404 Not Found（軟刪除的用戶應該被視為不存在）
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
     * 測試管理員取得已刪除用戶詳細資料（包含已刪除）回應結構.
     *
     * 驗證使用 include_deleted 參數的回應結構
     */
    public function testAdminGetDeletedUserWithTrashedResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立並軟刪除目標用戶
        $deletedUser = User::factory()->create([
            'email' => 'deleted@example.com',
            'role' => 'user',
        ]);
        $deletedUser->delete(); // 軟刪除

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 發送請求（包含已刪除的參數）
        $response = $this->getJson("/api/v1/admin/users/{$deletedUser->id}?include_deleted=true");

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構包含刪除資訊
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
                    'updated_at',
                    'deleted_at', // 軟刪除時間
                    'last_login_at',
                    'api_tokens_count'
                ]
            ]
        ]);

        // 期望狀態為 success
        $response->assertJson([
            'status' => 'success'
        ]);

        // 期望包含刪除時間
        $response->assertJsonPath('data.user.deleted_at', $deletedUser->deleted_at->toISOString());
    }

    /**
     * 測試非管理員用戶存取回應結構.
     *
     * 驗證 403 權限拒絕回應的 JSON 結構
     */
    public function testNonAdminGetUserDetailForbiddenResponseStructure(): void
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

        // 發送請求
        $response = $this->getJson("/api/v1/admin/users/{$targetUser->id}");

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
     * 測試未認證用戶存取回應結構.
     *
     * 驗證 401 未認證回應的 JSON 結構
     */
    public function testUnauthenticatedGetUserDetailResponseStructure(): void
    {
        // 建立目標用戶
        $targetUser = User::factory()->create();

        // 發送請求（沒有認證）
        $response = $this->getJson("/api/v1/admin/users/{$targetUser->id}");

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
     * 測試無效用戶 ID 格式回應結構.
     *
     * 驗證無效 ID 格式的 400 錯誤回應結構
     */
    public function testAdminGetUserInvalidIdFormatResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 發送請求（使用無效的用戶 ID 格式）
        $response = $this->getJson('/api/v1/admin/users/invalid-id');

        // 期望狀態碼 400 Bad Request 或 404 Not Found
        $this->assertContains($response->status(), [400, 404]);

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
     * 測試管理員取得自己的詳細資料回應結構.
     *
     * 驗證管理員查看自己資料的回應結構
     */
    public function testAdminGetOwnDetailResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 發送請求（查看自己的資料）
        $response = $this->getJson("/api/v1/admin/users/{$admin->id}");

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
                    'updated_at',
                    'last_login_at',
                    'api_tokens_count'
                ]
            ]
        ]);

        // 期望狀態為 success
        $response->assertJson([
            'status' => 'success'
        ]);

        // 期望返回的是管理員自己的資料
        $response->assertJsonPath('data.user.id', $admin->id);
        $response->assertJsonPath('data.user.email', $admin->email);
        $response->assertJsonPath('data.user.role', 'admin');
    }
}
