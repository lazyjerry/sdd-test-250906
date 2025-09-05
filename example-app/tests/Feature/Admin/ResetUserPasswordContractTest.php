<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Reset User Password Contract Test.
 *
 * 驗證 POST /api/v1/admin/users/{id}/reset-password 端點的 API 合約
 *
 * 依據 specs/001-laravel12-example-app/specify.md 的 API 規範：
 * - POST /api/v1/admin/users/{id}/reset-password: 管理員重設用戶密碼
 *
 * @internal
 *
 * @coversNothing
 */
final class ResetUserPasswordContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試管理員重設用戶密碼成功回應結構.
     *
     * 驗證 200 成功回應的 JSON 結構符合 OpenAPI 規格
     */
    public function testAdminResetUserPasswordSuccessResponseStructure(): void
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
            'password' => Hash::make('old-password'),
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 準備密碼重設資料
        $resetData = [
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
            'notify_user' => true, // 是否通知用戶
        ];

        // 發送請求
        $response = $this->postJson("/api/v1/admin/users/{$targetUser->id}/reset-password", $resetData);

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構符合 OpenAPI 規格
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user_id',
                'password_reset_at',
                'notification_sent'
            ]
        ]);

        // 期望狀態為 success
        $response->assertJson([
            'status' => 'success'
        ]);

        // 期望包含重設資訊
        $response->assertJsonPath('data.user_id', $targetUser->id);
        $response->assertJsonPath('data.notification_sent', true);
    }

    /**
     * 測試管理員重設用戶密碼但不通知回應結構.
     *
     * 驗證不發送通知的回應結構
     */
    public function testAdminResetUserPasswordNoNotificationResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立目標用戶
        $targetUser = User::factory()->create([
            'role' => 'user',
            'password' => Hash::make('old-password'),
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 準備密碼重設資料（不通知用戶）
        $resetData = [
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
            'notify_user' => false,
        ];

        // 發送請求
        $response = $this->postJson("/api/v1/admin/users/{$targetUser->id}/reset-password", $resetData);

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構符合 OpenAPI 規格
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user_id',
                'password_reset_at',
                'notification_sent'
            ]
        ]);

        // 期望沒有發送通知
        $response->assertJsonPath('data.notification_sent', false);
    }

    /**
     * 測試管理員重設不存在用戶密碼回應結構.
     *
     * 驗證 404 用戶不存在回應的 JSON 結構
     */
    public function testAdminResetNonexistentUserPasswordResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 準備密碼重設資料
        $resetData = [
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ];

        // 發送請求（使用不存在的用戶 ID）
        $response = $this->postJson('/api/v1/admin/users/99999/reset-password', $resetData);

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
     * 測試管理員重設密碼驗證錯誤回應結構.
     *
     * 驗證 422 驗證錯誤回應的 JSON 結構
     */
    public function testAdminResetUserPasswordValidationErrorResponseStructure(): void
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

        // 準備無效的密碼重設資料
        $resetData = [
            'new_password' => '123', // 太短的密碼
            'new_password_confirmation' => '456', // 確認密碼不一致
        ];

        // 發送請求
        $response = $this->postJson("/api/v1/admin/users/{$targetUser->id}/reset-password", $resetData);

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
                    'new_password'
                ]
            ]
        ]);

        // 期望狀態為 error
        $response->assertJson([
            'status' => 'error'
        ]);
    }

    /**
     * 測試非管理員用戶重設密碼回應結構.
     *
     * 驗證 403 權限拒絕回應的 JSON 結構
     */
    public function testNonAdminResetUserPasswordForbiddenResponseStructure(): void
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

        // 準備密碼重設資料
        $resetData = [
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ];

        // 發送請求
        $response = $this->postJson("/api/v1/admin/users/{$targetUser->id}/reset-password", $resetData);

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
     * 測試未認證用戶重設密碼回應結構.
     *
     * 驗證 401 未認證回應的 JSON 結構
     */
    public function testUnauthenticatedResetUserPasswordResponseStructure(): void
    {
        // 建立目標用戶
        $targetUser = User::factory()->create();

        // 準備密碼重設資料
        $resetData = [
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ];

        // 發送請求（沒有認證）
        $response = $this->postJson("/api/v1/admin/users/{$targetUser->id}/reset-password", $resetData);

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
     * 測試管理員重設已刪除用戶密碼回應結構.
     *
     * 驗證軟刪除用戶的回應結構
     */
    public function testAdminResetDeletedUserPasswordResponseStructure(): void
    {
        // 建立管理員用戶
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 建立並軟刪除目標用戶
        $deletedUser = User::factory()->create([
            'role' => 'user'
        ]);
        $deletedUser->delete();

        // 使用 Sanctum 認證管理員
        Sanctum::actingAs($admin);

        // 準備密碼重設資料
        $resetData = [
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ];

        // 發送請求
        $response = $this->postJson("/api/v1/admin/users/{$deletedUser->id}/reset-password", $resetData);

        // 期望狀態碼 404 Not Found（軟刪除的用戶不應該能重設密碼）
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
     * 測試管理員缺少必填欄位回應結構.
     *
     * 驗證缺少必填欄位的 422 錯誤回應結構
     */
    public function testAdminResetUserPasswordMissingFieldsResponseStructure(): void
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

        // 發送空的請求資料
        $resetData = [];

        // 發送請求
        $response = $this->postJson("/api/v1/admin/users/{$targetUser->id}/reset-password", $resetData);

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
                    'new_password'
                ]
            ]
        ]);

        // 期望狀態為 error
        $response->assertJson([
            'status' => 'error'
        ]);
    }

    /**
     * 測試管理員使用弱密碼重設回應結構.
     *
     * 驗證密碼強度不符合要求的 422 錯誤回應結構
     */
    public function testAdminResetUserWeakPasswordResponseStructure(): void
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

        // 準備弱密碼
        $resetData = [
            'new_password' => 'weak', // 弱密碼
            'new_password_confirmation' => 'weak',
        ];

        // 發送請求
        $response = $this->postJson("/api/v1/admin/users/{$targetUser->id}/reset-password", $resetData);

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
                    'new_password'
                ]
            ]
        ]);

        // 期望狀態為 error
        $response->assertJson([
            'status' => 'error'
        ]);
    }

    /**
     * 測試管理員生成隨機密碼回應結構.
     *
     * 驗證生成隨機密碼的回應結構
     */
    public function testAdminGenerateRandomPasswordResponseStructure(): void
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

        // 請求生成隨機密碼
        $resetData = [
            'generate_random' => true,
            'notify_user' => true,
        ];

        // 發送請求
        $response = $this->postJson("/api/v1/admin/users/{$targetUser->id}/reset-password", $resetData);

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構包含生成的密碼資訊
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user_id',
                'password_reset_at',
                'notification_sent',
                'temporary_password' // 生成的臨時密碼（僅在此次回應中顯示）
            ]
        ]);

        // 期望狀態為 success
        $response->assertJson([
            'status' => 'success'
        ]);

        // 期望包含臨時密碼
        $this->assertNotNull($response->json('data.temporary_password'));
    }
}
