<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 使用者登出 API 合約測試.
 *
 * 測試 POST /api/v1/auth/logout 端點的請求/回應結構
 * 確保 API 合約符合 OpenAPI 規格定義
 *
 * @internal
 *
 * @coversNothing
 */
final class LogoutContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試登出成功時的回應結構.
     */
    public function testLogoutSuccessResponseStructure()
    {
        // 建立並認證使用者
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        // 期望狀態碼 200 OK
        $response->assertStatus(200);

        // 期望回應結構符合 OpenAPI 規格
        $response->assertJsonStructure([
            'status',
            'message'
        ]);

        // 驗證回應值
        $response->assertJson([
            'status' => 'success',
            'message' => '登出成功'
        ]);
    }

    /**
     * 測試未認證使用者登出的回應結構.
     */
    public function testLogoutUnauthenticatedResponseStructure()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        // 期望狀態碼 401 Unauthorized
        $response->assertStatus(401);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error_code'
        ]);

        $response->assertJson([
            'status' => 'error',
            'message' => '未認證的請求',
            'error_code' => 'UNAUTHENTICATED'
        ]);
    }

    /**
     * 測試無效 Token 登出的回應結構.
     */
    public function testLogoutInvalidTokenResponseStructure()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-here'
        ])->postJson('/api/v1/auth/logout');

        // 期望狀態碼 401 Unauthorized
        $response->assertStatus(401);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'error_code'
        ]);

        $response->assertJson([
            'status' => 'error',
            'error_code' => 'INVALID_TOKEN'
        ]);
    }
}
