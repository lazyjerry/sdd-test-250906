<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Update Profile Contract Test.
 *
 * 驗證 PUT /api/v1/users/profile 端點的 API 合約
 *
 * 依據 specs/001-laravel12-example-app/specify.md 的 API 規範：
 * - PUT /api/v1/users/profile: 更新當前用戶檔案
 *
 * @internal
 *
 * @coversNothing
 */
final class UpdateProfileContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試更新用戶檔案成功回應結構.
     *
     * 驗證 200 成功回應的 JSON 結構符合 OpenAPI 規格
     */
    public function testUpdateProfileSuccessResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Original Name',
            'email_verified_at' => now(),
        ]);

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 準備更新資料
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        // 發送請求
        $response = $this->putJson('/api/v1/users/profile', $updateData);

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

        // 期望返回更新後的用戶資料
        $response->assertJsonPath('data.user.id', $user->id);
        $response->assertJsonPath('data.user.name', 'Updated Name');
        $response->assertJsonPath('data.user.email', 'updated@example.com');
    }

    /**
     * 測試更新檔案驗證錯誤回應結構.
     *
     * 驗證 422 驗證錯誤回應的 JSON 結構
     */
    public function testUpdateProfileValidationErrorResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create();

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 準備無效的更新資料
        $updateData = [
            'name' => '', // 空名稱
            'email' => 'invalid-email', // 無效郵箱格式
        ];

        // 發送請求
        $response = $this->putJson('/api/v1/users/profile', $updateData);

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
     * 測試更新檔案郵箱重複錯誤回應結構.
     *
     * 驗證郵箱已存在的 422 錯誤回應結構
     */
    public function testUpdateProfileEmailDuplicateResponseStructure(): void
    {
        // 建立兩個測試用戶
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com'
        ]);

        $currentUser = User::factory()->create([
            'email' => 'current@example.com'
        ]);

        // 使用 Sanctum 認證當前用戶
        Sanctum::actingAs($currentUser);

        // 嘗試更新為已存在的郵箱
        $updateData = [
            'name' => 'Valid Name',
            'email' => 'existing@example.com', // 已存在的郵箱
        ];

        // 發送請求
        $response = $this->putJson('/api/v1/users/profile', $updateData);

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
     * 測試未認證用戶更新檔案回應結構.
     *
     * 驗證 401 未認證回應的 JSON 結構
     */
    public function testUpdateProfileUnauthenticatedResponseStructure(): void
    {
        // 準備更新資料
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        // 發送請求（沒有認證）
        $response = $this->putJson('/api/v1/users/profile', $updateData);

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
     * 測試缺少必填欄位回應結構.
     *
     * 驗證缺少必填欄位的 422 錯誤回應結構
     */
    public function testUpdateProfileMissingRequiredFieldsResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create();

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 發送空的請求資料
        $updateData = [];

        // 發送請求
        $response = $this->putJson('/api/v1/users/profile', $updateData);

        // 期望狀態碼 422 Unprocessable Entity
        $response->assertStatus(422);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error' => [
                'code',
                'details',
                'validation_errors'
            ]
        ]);

        // 期望狀態為 error
        $response->assertJson([
            'status' => 'error'
        ]);
    }

    /**
     * 測試郵箱修改需要重新驗證回應結構.
     *
     * 驗證郵箱修改後需要重新驗證的回應結構
     */
    public function testUpdateProfileEmailChangeVerificationResponseStructure(): void
    {
        // 建立已驗證的測試用戶
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ]);

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 修改郵箱
        $updateData = [
            'name' => $user->name,
            'email' => 'newemail@example.com',
        ];

        // 發送請求
        $response = $this->putJson('/api/v1/users/profile', $updateData);

        // 期望狀態碼 200 OK（但會包含需要驗證的訊息）
        $response->assertStatus(200);

        // 期望回應結構包含驗證提醒
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at', // 應該為 null
                    'role',
                    'created_at',
                    'updated_at'
                ],
                'email_verification_sent' // 驗證郵件已發送標識
            ]
        ]);

        // 期望狀態為 success
        $response->assertJson([
            'status' => 'success'
        ]);

        // 期望新郵箱尚未驗證
        $response->assertJsonPath('data.user.email_verified_at', null);
        $response->assertJsonPath('data.email_verification_sent', true);
    }
}
