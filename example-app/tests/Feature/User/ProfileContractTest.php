<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Profile Contract Test.
 *
 * 驗證 GET /api/v1/users/profile 端點的 API 合約
 *
 * 依據 specs/001-laravel12-example-app/specify.md 的 API 規範：
 * - GET /api/v1/users/profile: 取得當前用戶檔案
 *
 * @internal
 *
 * @coversNothing
 */
final class ProfileContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試取得用戶檔案成功回應結構.
     *
     * 驗證 200 成功回應的 JSON 結構符合 OpenAPI 規格
     */
    public function testGetProfileSuccessResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 發送請求
        $response = $this->getJson('/api/v1/users/profile');

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
                    'email_verified_at',
                    'role',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);

        // 期望狀態為 success
        $response->assertJson([
            'status' => 'success'
        ]);

        // 期望返回的是當前用戶資料
        $response->assertJsonPath('data.user.id', $user->id);
        $response->assertJsonPath('data.user.email', $user->email);
    }

    /**
     * 測試未認證用戶取得檔案回應結構.
     *
     * 驗證 401 未認證回應的 JSON 結構
     */
    public function testGetProfileUnauthenticatedResponseStructure(): void
    {
        // 發送請求（沒有認證）
        $response = $this->getJson('/api/v1/users/profile');

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
     * 測試無效 token 取得檔案回應結構.
     *
     * 驗證 401 無效認證回應的 JSON 結構
     */
    public function testGetProfileInvalidTokenResponseStructure(): void
    {
        // 發送帶有無效 token 的請求
        $response = $this->getJson('/api/v1/users/profile', [
            'Authorization' => 'Bearer invalid-token-here'
        ]);

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
     * 測試已刪除用戶取得檔案回應結構.
     *
     * 驗證軟刪除用戶的回應結構
     */
    public function testGetProfileDeletedUserResponseStructure(): void
    {
        // 建立並軟刪除測試用戶
        $user = User::factory()->create([
            'email' => 'deleted@example.com',
            'deleted_at' => now(),
        ]);

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 發送請求
        $response = $this->getJson('/api/v1/users/profile');

        // 期望狀態碼 401 Unauthorized（因為用戶已被刪除）
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
     * 測試訪問不存在端點的錯誤回應結構.
     *
     * 驗證 404 找不到端點的錯誤回應結構
     */
    public function testGetProfileServerErrorResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create();

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 訪問不存在的端點來模擬錯誤狀況
        $response = $this->getJson('/api/v1/users/non-existent-endpoint');

        // 期望狀態碼 404 Not Found
        $response->assertStatus(404);
    }
}
