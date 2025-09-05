<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * User List Contract Test.
 *
 * 驗證 GET /api/v1/admin/users 端點的 API 合約
 *
 * 依據 specs/001-laravel12-example-app/specify.md 的 API 規範：
 * - GET /api/v1/admin/users: 取得用戶列表（管理員權限）
 *
 * @internal
 *
 * @coversNothing
 */
final class UserListContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試管理員取得用戶列表成功回應結構.
     *
     * 驗證 200 成功回應的 JSON 結構符合 OpenAPI 規格
     */
    public function testAdminGetUsersSuccessResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // 建立一些普通用戶
        User::factory()->count(3)->create([
            'role' => 'user'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 發送請求
        $response = $this->getJson('/api/v1/admin/users');

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構符合 OpenAPI 規格
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'users' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'email_verified_at',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'from',
                    'to'
                ]
            ]
        ]);

        // 期望狀態為 success
        $response->assertJson([
            'status' => 'success'
        ]);

        // 期望包含至少 4 個用戶（3 個普通用戶 + 1 個管理員）
        $this->assertGreaterThanOrEqual(4, \count($response->json('data.users')));
    }

    /**
     * 測試管理員取得用戶列表分頁回應結構.
     *
     * 驗證分頁參數的回應結構
     */
    public function testAdminGetUsersPaginationResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立大量用戶測試分頁
        User::factory()->count(25)->create([
            'role' => 'user'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 發送分頁請求
        $response = $this->getJson('/api/v1/admin/users?page=2&per_page=10');

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望分頁結構
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'users' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'email_verified_at',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'from',
                    'to'
                ]
            ]
        ]);

        // 期望分頁資訊正確
        $response->assertJsonPath('data.pagination.current_page', 2);
        $response->assertJsonPath('data.pagination.per_page', 10);
    }

    /**
     * 測試管理員用戶搜尋回應結構.
     *
     * 驗證搜尋功能的回應結構
     */
    public function testAdminSearchUsersResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立測試用戶
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'role' => 'user'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 發送搜尋請求
        $response = $this->getJson('/api/v1/admin/users?search=john');

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構包含搜尋結果
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'users' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'email_verified_at',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'search_query',
                'pagination'
            ]
        ]);

        // 期望包含搜尋關鍵字
        $response->assertJsonPath('data.search_query', 'john');
    }

    /**
     * 測試非管理員用戶存取回應結構.
     *
     * 驗證 403 權限拒絕回應的 JSON 結構
     */
    public function testNonAdminGetUsersForbiddenResponseStructure(): void
    {
        // 建立普通用戶
        $user = User::factory()->create([
            'role' => 'user'
        ]);

        // 使用 Sanctum 認證普通用戶
        Sanctum::actingAs($user);

        // 發送請求
        $response = $this->getJson('/api/v1/admin/users');

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
    public function testUnauthenticatedGetUsersResponseStructure(): void
    {
        // 發送請求（沒有認證）
        $response = $this->getJson('/api/v1/admin/users');

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
     * 測試用戶角色篩選回應結構.
     *
     * 驗證角色篩選功能的回應結構
     */
    public function testAdminFilterUsersByRoleResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立不同角色的用戶
        User::factory()->count(2)->create(['role' => 'admin']);
        User::factory()->count(3)->create(['role' => 'user']);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 發送角色篩選請求
        $response = $this->getJson('/api/v1/admin/users?role=admin');

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構包含篩選結果
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'users' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'email_verified_at',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'filters' => [
                    'role'
                ],
                'pagination'
            ]
        ]);

        // 期望篩選條件正確
        $response->assertJsonPath('data.filters.role', 'admin');

        // 確認所有返回的用戶都是管理員
        $users = $response->json('data.users');
        foreach ($users as $user) {
            $this->assertSame('admin', $user['role']);
        }
    }

    /**
     * 測試用戶狀態篩選回應結構.
     *
     * 驗證驗證狀態篩選功能的回應結構
     */
    public function testAdminFilterUsersByVerificationStatusResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立不同驗證狀態的用戶
        User::factory()->count(2)->create([
            'role' => 'user',
            'email_verified_at' => now()
        ]);
        User::factory()->count(3)->create([
            'role' => 'user',
            'email_verified_at' => null
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 發送驗證狀態篩選請求
        $response = $this->getJson('/api/v1/admin/users?verified=false');

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構包含篩選結果
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'users' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'email_verified_at',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'filters' => [
                    'verified'
                ],
                'pagination'
            ]
        ]);

        // 期望篩選條件正確
        $response->assertJsonPath('data.filters.verified', false);

        // 確認所有返回的用戶都未驗證
        $users = $response->json('data.users');
        foreach ($users as $user) {
            $this->assertNull($user['email_verified_at']);
        }
    }
}
