<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Change Password Contract Test.
 *
 * 驗證 PUT /api/v1/users/change-password 端點的 API 合約
 *
 * 依據 specs/001-laravel12-example-app/specify.md 的 API 規範：
 * - PUT /api/v1/users/change-password: 修改當前用戶密碼
 *
 * @internal
 *
 * @coversNothing
 */
final class ChangePasswordContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試修改密碼成功回應結構.
     *
     * 驗證 200 成功回應的 JSON 結構符合 OpenAPI 規格
     */
    public function testChangePasswordSuccessResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('old-password'),
            'email_verified_at' => now(),
        ]);

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 準備密碼修改資料
        $changeData = [
            'current_password' => 'old-password',
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ];

        // 發送請求
        $response = $this->putJson('/api/v1/users/change-password', $changeData);

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構符合 OpenAPI 規格
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'password_changed_at'
            ]
        ]);

        // 期望狀態為 success
        $response->assertJson([
            'status' => 'success'
        ]);

        // 期望包含密碼修改時間戳
        $response->assertJsonStructure([
            'data' => [
                'password_changed_at'
            ]
        ]);
    }

    /**
     * 測試修改密碼驗證錯誤回應結構.
     *
     * 驗證 422 驗證錯誤回應的 JSON 結構
     */
    public function testChangePasswordValidationErrorResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create([
            'password' => Hash::make('current-password')
        ]);

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 準備無效的密碼修改資料
        $changeData = [
            'current_password' => 'current-password',
            'new_password' => '123', // 太短的密碼
            'new_password_confirmation' => '456', // 確認密碼不一致
        ];

        // 發送請求
        $response = $this->putJson('/api/v1/users/change-password', $changeData);

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
     * 測試當前密碼錯誤回應結構.
     *
     * 驗證當前密碼不正確的 400 錯誤回應結構
     */
    public function testChangePasswordCurrentPasswordIncorrectResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create([
            'password' => Hash::make('correct-password')
        ]);

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 準備錯誤的當前密碼
        $changeData = [
            'current_password' => 'wrong-password', // 錯誤的當前密碼
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ];

        // 發送請求
        $response = $this->putJson('/api/v1/users/change-password', $changeData);

        // 期望狀態碼 400 Bad Request
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

    /**
     * 測試未認證用戶修改密碼回應結構.
     *
     * 驗證 401 未認證回應的 JSON 結構
     */
    public function testChangePasswordUnauthenticatedResponseStructure(): void
    {
        // 準備密碼修改資料
        $changeData = [
            'current_password' => 'current-password',
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ];

        // 發送請求（沒有認證）
        $response = $this->putJson('/api/v1/users/change-password', $changeData);

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
    public function testChangePasswordMissingRequiredFieldsResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create();

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 發送空的請求資料
        $changeData = [];

        // 發送請求
        $response = $this->putJson('/api/v1/users/change-password', $changeData);

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
                    'current_password',
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
     * 測試新密碼與當前密碼相同回應結構.
     *
     * 驗證新密碼與當前密碼相同的 400 錯誤回應結構
     */
    public function testChangePasswordSameAsCurrentResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create([
            'password' => Hash::make('current-password')
        ]);

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 新密碼與當前密碼相同
        $changeData = [
            'current_password' => 'current-password',
            'new_password' => 'current-password', // 與當前密碼相同
            'new_password_confirmation' => 'current-password',
        ];

        // 發送請求
        $response = $this->putJson('/api/v1/users/change-password', $changeData);

        // 期望狀態碼 400 Bad Request
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

    /**
     * 測試密碼強度不足回應結構.
     *
     * 驗證密碼強度不符合要求的 422 錯誤回應結構
     */
    public function testChangePasswordWeakPasswordResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create([
            'password' => Hash::make('current-password')
        ]);

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 準備弱密碼
        $changeData = [
            'current_password' => 'current-password',
            'new_password' => 'weak', // 弱密碼
            'new_password_confirmation' => 'weak',
        ];

        // 發送請求
        $response = $this->putJson('/api/v1/users/change-password', $changeData);

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
     * 測試頻率限制回應結構.
     *
     * 驗證密碼修改頻率限制的 429 錯誤回應結構
     */
    public function testChangePasswordRateLimitResponseStructure(): void
    {
        // 建立測試用戶
        $user = User::factory()->create([
            'password' => Hash::make('current-password')
        ]);

        // 使用 Sanctum 認證
        Sanctum::actingAs($user);

        // 準備密碼修改資料
        $changeData = [
            'current_password' => 'current-password',
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ];

        // 連續多次請求觸發頻率限制
        $response = null;
        for ($i = 0; $i < 10; ++$i) {
            $response = $this->putJson('/api/v1/users/change-password', $changeData);
        }

        // 期望狀態碼 429 Too Many Requests
        $response->assertStatus(429);

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
