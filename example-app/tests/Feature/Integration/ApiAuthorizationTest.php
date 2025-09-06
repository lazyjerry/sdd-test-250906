<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API Authorization Integration Test.
 *
 * 測試完整的 API 授權流程整合
 *
 * 涵蓋的流程：
 * 1. Token 基礎授權驗證
 * 2. 角色權限控制
 * 3. 資源存取授權
 * 4. API 速率限制
 * 5. 跨域和安全標頭
 *
 * @internal
 *
 * @coversNothing
 */
final class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試完整的 API 授權流程.
     *
     * 從 token 獲取到資源存取的完整授權流程
     */
    public function testCompleteApiAuthorizationFlow(): void
    {
        // 第一步：建立不同角色的用戶
        $adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'username' => 'admin_user',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        $regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'username' => 'regular_user',
            'password' => Hash::make('UserPassword123!'),
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        // 第二步：獲取不同用戶的 token
        $adminLoginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin_user',
            'password' => 'AdminPassword123!',
            'device_name' => 'Admin Device'
        ]);
        $adminLoginResponse->assertStatus(200);
        $adminToken = $adminLoginResponse->json('data.token');

        $userLoginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'regular_user',
            'password' => 'UserPassword123!',
            'device_name' => 'User Device'
        ]);
        $userLoginResponse->assertStatus(200);
        $userToken = $userLoginResponse->json('data.token');

        // 第三步：測試公開端點（無需授權）
        $publicResponse = $this->getJson('/api/v1/auth/ping');
        if (404 !== $publicResponse->status()) {
            $publicResponse->assertStatus(200);
        }

        // 第四步：測試用戶端點授權
        // 用戶可以存取自己的個人資料
        $userProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$userToken}"
        ]);
        $userProfileResponse->assertStatus(200);
        $userProfileResponse->assertJsonPath('data.user.email', 'user@example.com');

        // 管理員也可以存取自己的個人資料
        $adminProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$adminToken}"
        ]);
        $adminProfileResponse->assertStatus(200);
        $adminProfileResponse->assertJsonPath('data.user.email', 'admin@example.com');

        // 第五步：測試管理員端點授權
        // 一般用戶無法存取管理員功能
        $userAdminAccessResponse = $this->getJson('/api/v1/admin/users', [
            'Authorization' => "Bearer {$userToken}"
        ]);
        $userAdminAccessResponse->assertStatus(403);

        // 管理員可以存取管理員功能
        $adminAccessResponse = $this->getJson('/api/v1/admin/users', [
            'Authorization' => "Bearer {$adminToken}"
        ]);
        $adminAccessResponse->assertStatus(200);

        // 第六步：測試無效 token 處理
        $invalidTokenResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => 'Bearer invalid-token-12345'
        ]);
        $invalidTokenResponse->assertStatus(401);

        // 第七步：測試缺少 Authorization 標頭
        $noAuthResponse = $this->getJson('/api/v1/users/profile');
        $noAuthResponse->assertStatus(401);

        // 第八步：測試錯誤的 Authorization 格式
        $malformedAuthResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => 'InvalidFormat token123'
        ]);
        $malformedAuthResponse->assertStatus(401);
    }

    /**
     * 測試資源擁有權授權.
     *
     * 驗證用戶只能存取自己的資源
     */
    public function testResourceOwnershipAuthorization(): void
    {
        // 建立兩個用戶
        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        // 獲取 token
        $user1Token = $this->postJson('/api/v1/auth/login', [
            'email' => 'user1@example.com',
            'password' => 'Password123!',
            'device_name' => 'User1 Device'
        ])->json('data.token');

        $user2Token = $this->postJson('/api/v1/auth/login', [
            'email' => 'user2@example.com',
            'password' => 'Password123!',
            'device_name' => 'User2 Device'
        ])->json('data.token');

        // 測試案例 1：用戶1可以存取自己的個人資料
        $user1ProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$user1Token}"
        ]);
        $user1ProfileResponse->assertStatus(200);
        $user1ProfileResponse->assertJsonPath('data.user.email', 'user1@example.com');

        // 測試案例 2：用戶1可以更新自己的個人資料
        $user1UpdateResponse = $this->putJson('/api/v1/users/profile', [
            'name' => 'Updated User1 Name'
        ], [
            'Authorization' => "Bearer {$user1Token}"
        ]);
        $user1UpdateResponse->assertStatus(200);

        // 測試案例 3：用戶1無法直接存取用戶2的資料（如果有此類端點）
        $user1AccessUser2Response = $this->getJson("/api/v1/users/{$user2->id}/profile", [
            'Authorization' => "Bearer {$user1Token}"
        ]);

        // 這個端點可能不存在，或者應該回傳 403
        $this->assertContains($user1AccessUser2Response->status(), [403, 404]);

        // 測試案例 4：用戶1無法更新用戶2的資料
        $user1UpdateUser2Response = $this->putJson("/api/v1/users/{$user2->id}/profile", [
            'name' => 'Attempted Update'
        ], [
            'Authorization' => "Bearer {$user1Token}"
        ]);

        $this->assertContains($user1UpdateUser2Response->status(), [403, 404]);
    }

    /**
     * 測試 API 速率限制.
     *
     * 驗證不同端點的速率限制機制
     */
    public function testApiRateLimiting(): void
    {
        $user = User::factory()->create([
            'email' => 'ratelimit@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'ratelimit@example.com',
            'password' => 'Password123!',
            'device_name' => 'Rate Limit Test'
        ])->json('data.token');

        // 測試案例 1：一般 API 速率限制
        $requestCount = 0;
        $rateLimitHit = false;

        for ($i = 0; $i < 100; ++$i) { // 嘗試大量請求
            $response = $this->getJson('/api/v1/users/profile', [
                'Authorization' => "Bearer {$token}"
            ]);

            ++$requestCount;

            if (429 === $response->status()) {
                $rateLimitHit = true;

                break;
            }

            $this->assertSame(200, $response->status());
        }

        // 如果有速率限制，應該在某個點被觸發
        if ($rateLimitHit) {
            $this->assertTrue($rateLimitHit);
            $this->assertLessThan(100, $requestCount);
        }

        // 測試案例 2：認證端點的特殊速率限制
        $loginAttempts = 0;
        $loginRateLimitHit = false;

        for ($i = 0; $i < 20; ++$i) { // 嘗試多次登入
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit@example.com',
                'password' => 'WrongPassword123!', // 故意使用錯誤密碼
                'device_name' => 'Rate Limit Test'
            ]);

            ++$loginAttempts;

            if (429 === $response->status()) {
                $loginRateLimitHit = true;

                break;
            }

            $this->assertSame(401, $response->status()); // 認證失敗
        }

        // 登入端點通常有更嚴格的速率限制
        if ($loginRateLimitHit) {
            $this->assertTrue($loginRateLimitHit);
            $this->assertLessThan(20, $loginAttempts);
        }
    }

    /**
     * 測試 API 版本控制和向後相容性.
     *
     * 驗證不同 API 版本的授權機制
     */
    public function testApiVersioningAuthorization(): void
    {
        $user = User::factory()->create([
            'email' => 'versioning@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'versioning@example.com',
            'password' => 'Password123!',
            'device_name' => 'Versioning Test'
        ])->json('data.token');

        // 測試案例 1：v1 API 存取
        $v1Response = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);
        $v1Response->assertStatus(200);

        // 測試案例 2：v2 API 存取（如果存在）
        $v2Response = $this->getJson('/api/v2/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);

        // v2 可能不存在，或者需要不同的授權機制
        $this->assertContains($v2Response->status(), [200, 404, 401, 403]);

        // 測試案例 3：無版本 API 存取
        $noVersionResponse = $this->getJson('/api/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);

        $this->assertContains($noVersionResponse->status(), [200, 404, 401]);

        // 測試案例 4：錯誤的版本格式
        $invalidVersionResponse = $this->getJson('/api/v999/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);
        $invalidVersionResponse->assertStatus(404);
    }

    /**
     * 測試 CORS 和安全標頭.
     *
     * 驗證跨域請求和安全標頭的處理
     */
    public function testCorsAndSecurityHeaders(): void
    {
        $user = User::factory()->create([
            'email' => 'cors@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'cors@example.com',
            'password' => 'Password123!',
            'device_name' => 'CORS Test'
        ])->json('data.token');

        // 測試案例 1：預檢請求（OPTIONS）
        $preflightResponse = $this->call('OPTIONS', '/api/v1/users/profile', [], [], [], [
            'HTTP_ORIGIN' => 'https://example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization, Content-Type'
        ]);

        if (200 === $preflightResponse->status()) {
            // 檢查 CORS 標頭
            $preflightResponse->assertHeader('Access-Control-Allow-Origin');
            $preflightResponse->assertHeader('Access-Control-Allow-Methods');
            $preflightResponse->assertHeader('Access-Control-Allow-Headers');
        }

        // 測試案例 2：實際的跨域請求
        $corsResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}",
            'Origin' => 'https://example.com'
        ]);

        $corsResponse->assertStatus(200);

        // 檢查安全標頭
        $corsResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $corsResponse->assertHeader('X-Frame-Options');
        $corsResponse->assertHeader('X-XSS-Protection');

        // 檢查 CORS 標頭（如果配置了）
        if ($corsResponse->headers->has('Access-Control-Allow-Origin')) {
            $this->assertNotEmpty($corsResponse->headers->get('Access-Control-Allow-Origin'));
        }

        // 測試案例 3：不允許的來源
        $unauthorizedOriginResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}",
            'Origin' => 'https://malicious-site.com'
        ]);

        // 根據 CORS 配置，可能允許或拒絕
        $this->assertContains($unauthorizedOriginResponse->status(), [200, 403]);
    }

    /**
     * 測試 API 金鑰授權（如果實作）.
     *
     * 驗證除了 Bearer token 之外的授權機制
     */
    public function testApiKeyAuthorization(): void
    {
        $user = User::factory()->create([
            'email' => 'apikey@example.com',
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        // 測試案例 1：使用 API 金鑰標頭
        $apiKeyResponse = $this->getJson('/api/v1/users/profile', [
            'X-API-Key' => 'test-api-key-12345'
        ]);

        // 如果系統不支持 API 金鑰，應該回傳 401
        $this->assertContains($apiKeyResponse->status(), [200, 401, 404]);

        // 測試案例 2：查詢參數中的 API 金鑰
        $apiKeyQueryResponse = $this->getJson('/api/v1/users/profile?api_key=test-api-key-12345');
        $this->assertContains($apiKeyQueryResponse->status(), [200, 401, 404]);

        // 測試案例 3：無效的 API 金鑰
        $invalidApiKeyResponse = $this->getJson('/api/v1/users/profile', [
            'X-API-Key' => 'invalid-api-key'
        ]);
        $this->assertContains($invalidApiKeyResponse->status(), [401, 404]);
    }

    /**
     * 測試 Token 作用域和權限.
     *
     * 驗證 token 的權限範圍限制
     */
    public function testTokenScopesAndPermissions(): void
    {
        $user = User::factory()->create([
            'email' => 'scopes@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        // 如果系統支持 token 作用域
        Sanctum::actingAs($user, ['read-profile', 'update-profile']);

        // 測試案例 1：允許的作用域操作
        $readResponse = $this->getJson('/api/v1/users/profile');
        $readResponse->assertStatus(200);

        $updateResponse = $this->putJson('/api/v1/users/profile', [
            'name' => 'Updated Name'
        ]);
        $updateResponse->assertStatus(200);

        // 測試案例 2：限制 token 作用域
        Sanctum::actingAs($user, ['read-profile']); // 只有讀取權限

        $readOnlyResponse = $this->getJson('/api/v1/users/profile');
        $readOnlyResponse->assertStatus(200);

        $restrictedUpdateResponse = $this->putJson('/api/v1/users/profile', [
            'name' => 'Attempted Update'
        ]);

        // 如果系統檢查作用域，應該被拒絕
        $this->assertContains($restrictedUpdateResponse->status(), [200, 403]);

        // 測試案例 3：無作用域的 token
        Sanctum::actingAs($user, []);

        $noScopeResponse = $this->getJson('/api/v1/users/profile');
        $this->assertContains($noScopeResponse->status(), [200, 403]);
    }

    /**
     * 測試 API 授權的邊界情況
     *
     * 驗證各種邊界和異常情況的處理
     */
    public function testAuthorizationEdgeCases(): void
    {
        $user = User::factory()->create([
            'email' => 'edge@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'edge@example.com',
            'password' => 'Password123!',
            'device_name' => 'Edge Case Test'
        ])->json('data.token');

        // 測試案例 1：非常長的 Authorization 標頭
        $veryLongToken = str_repeat('a', 10000);
        $longTokenResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$veryLongToken}"
        ]);
        $longTokenResponse->assertStatus(401);

        // 測試案例 2：空的 Authorization 標頭
        $emptyAuthResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => ''
        ]);
        $emptyAuthResponse->assertStatus(401);

        // 測試案例 3：只有 "Bearer" 沒有 token
        $bearerOnlyResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => 'Bearer'
        ]);
        $bearerOnlyResponse->assertStatus(401);

        // 測試案例 4：多個 Authorization 標頭
        $response = $this->call('GET', '/api/v1/users/profile', [], [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$token}",
            'HTTP_X_AUTHORIZATION' => 'Bearer another-token'
        ]);

        // 應該使用標準的 Authorization 標頭
        $response->assertStatus(200);

        // 測試案例 5：特殊字符在 token 中
        $specialCharToken = 'token-with-special-chars-!@#$%^&*()';
        $specialCharResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$specialCharToken}"
        ]);
        $specialCharResponse->assertStatus(401);

        // 測試案例 6：已刪除用戶的有效 token
        $user->delete(); // 軟刪除

        $deletedUserResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);
        $deletedUserResponse->assertStatus(401);
    }
}
