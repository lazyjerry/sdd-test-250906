<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * User Authentication Integration Test.
 *
 * 測試完整的用戶認證流程整合
 *
 * 涵蓋的流程：
 * 1. 完整的登入流程
 * 2. Token 管理和撤銷
 * 3. 多設備登入處理
 * 4. 認證狀態檢查
 * 5. 自動登出機制
 *
 * @internal
 *
 * @coversNothing
 */
final class UserAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試完整的用戶認證流程.
     *
     * 從登入到登出的完整認證流程
     */
    public function testCompleteUserAuthenticationFlow(): void
    {
        // 第一步：建立測試用戶
        $user = User::factory()->create([
            'email' => 'auth@example.com',
            'password' => Hash::make('AuthPassword123!'),
            'email_verified_at' => now()
        ]);

        // 第二步：執行登入
        $loginData = [
            'email' => 'auth@example.com',
            'password' => 'AuthPassword123!',
            'device_name' => 'Test Device'
        ];

        $loginResponse = $this->postJson('/api/v1/auth/login', $loginData);
        $loginResponse->assertStatus(200);
        $loginResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'email_verified_at'
                ],
                'token',
                'token_type',
                'expires_at'
            ]
        ]);

        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        // 第三步：使用 token 存取受保護的資源
        $profileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);
        $profileResponse->assertStatus(200);
        $profileResponse->assertJsonPath('data.user.email', 'auth@example.com');

        // 第四步：檢查認證狀態
        $authCheckResponse = $this->getJson('/api/v1/auth/me', [
            'Authorization' => "Bearer {$token}"
        ]);
        $authCheckResponse->assertStatus(200);
        $authCheckResponse->assertJsonStructure([
            'status',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role'
                ],
                'token_info' => [
                    'id',
                    'name',
                    'last_used_at',
                    'created_at'
                ]
            ]
        ]);

        // 第五步：執行登出
        $logoutResponse = $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => "Bearer {$token}"
        ]);
        $logoutResponse->assertStatus(200);

        // 第六步：驗證 token 已失效
        $invalidTokenResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);
        $invalidTokenResponse->assertStatus(401);
    }

    /**
     * 測試多設備登入管理.
     *
     * 驗證同一用戶在多個設備上的登入管理
     */
    public function testMultiDeviceAuthenticationManagement(): void
    {
        $user = User::factory()->create([
            'email' => 'multidevice@example.com',
            'password' => Hash::make('MultiDevice123!'),
            'email_verified_at' => now()
        ]);

        $loginData = [
            'email' => 'multidevice@example.com',
            'password' => 'MultiDevice123!',
        ];

        // 第一步：在第一個設備登入
        $device1Response = $this->postJson('/api/v1/auth/login', array_merge($loginData, [
            'device_name' => 'iPhone'
        ]));
        $device1Response->assertStatus(200);
        $device1Token = $device1Response->json('data.token');

        // 第二步：在第二個設備登入
        $device2Response = $this->postJson('/api/v1/auth/login', array_merge($loginData, [
            'device_name' => 'MacBook'
        ]));
        $device2Response->assertStatus(200);
        $device2Token = $device2Response->json('data.token');

        // 第三步：在第三個設備登入
        $device3Response = $this->postJson('/api/v1/auth/login', array_merge($loginData, [
            'device_name' => 'iPad'
        ]));
        $device3Response->assertStatus(200);
        $device3Token = $device3Response->json('data.token');

        // 第四步：驗證所有設備都可以存取
        $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$device1Token}"
        ])->assertStatus(200);

        $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$device2Token}"
        ])->assertStatus(200);

        $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$device3Token}"
        ])->assertStatus(200);

        // 第五步：檢查活躍設備清單
        $devicesResponse = $this->getJson('/api/v1/auth/devices', [
            'Authorization' => "Bearer {$device1Token}"
        ]);
        $devicesResponse->assertStatus(200);
        $devicesResponse->assertJsonStructure([
            'status',
            'data' => [
                'devices' => [
                    '*' => [
                        'id',
                        'name',
                        'last_used_at',
                        'is_current',
                        'created_at'
                    ]
                ],
                'total_devices'
            ]
        ]);

        $devices = $devicesResponse->json('data.devices');
        $this->assertCount(3, $devices);

        // 第六步：撤銷特定設備
        $deviceToRevoke = collect($devices)->firstWhere('name', 'MacBook');
        $revokeResponse = $this->deleteJson('/api/v1/auth/devices/' . $deviceToRevoke['id'], [], [
            'Authorization' => "Bearer {$device1Token}"
        ]);
        $revokeResponse->assertStatus(200);

        // 第七步：驗證被撤銷的設備無法存取
        $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$device2Token}"
        ])->assertStatus(401);

        // 第八步：其他設備仍可正常存取
        $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$device1Token}"
        ])->assertStatus(200);

        $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$device3Token}"
        ])->assertStatus(200);
    }

    /**
     * 測試登出所有設備功能.
     *
     * 驗證用戶可以一次登出所有設備
     */
    public function testLogoutAllDevices(): void
    {
        $user = User::factory()->create([
            'email' => 'logoutall@example.com',
            'password' => Hash::make('LogoutAll123!'),
            'email_verified_at' => now()
        ]);

        $loginData = [
            'email' => 'logoutall@example.com',
            'password' => 'LogoutAll123!',
        ];

        // 建立多個設備登入
        $device1Token = $this->postJson('/api/v1/auth/login', array_merge($loginData, [
            'device_name' => 'Device 1'
        ]))->json('data.token');

        $device2Token = $this->postJson('/api/v1/auth/login', array_merge($loginData, [
            'device_name' => 'Device 2'
        ]))->json('data.token');

        $device3Token = $this->postJson('/api/v1/auth/login', array_merge($loginData, [
            'device_name' => 'Device 3'
        ]))->json('data.token');

        // 驗證所有設備都可以存取
        $this->getJson('/api/v1/users/profile', ['Authorization' => "Bearer {$device1Token}"])->assertStatus(200);
        $this->getJson('/api/v1/users/profile', ['Authorization' => "Bearer {$device2Token}"])->assertStatus(200);
        $this->getJson('/api/v1/users/profile', ['Authorization' => "Bearer {$device3Token}"])->assertStatus(200);

        // 執行登出所有設備
        $logoutAllResponse = $this->postJson('/api/v1/auth/logout-all', [], [
            'Authorization' => "Bearer {$device1Token}"
        ]);
        $logoutAllResponse->assertStatus(200);
        $logoutAllResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'revoked_tokens_count'
            ]
        ]);

        // 驗證所有設備的 token 都已失效
        $this->getJson('/api/v1/users/profile', ['Authorization' => "Bearer {$device1Token}"])->assertStatus(401);
        $this->getJson('/api/v1/users/profile', ['Authorization' => "Bearer {$device2Token}"])->assertStatus(401);
        $this->getJson('/api/v1/users/profile', ['Authorization' => "Bearer {$device3Token}"])->assertStatus(401);
    }

    /**
     * 測試 token 自動過期機制.
     *
     * 驗證 token 的自動過期和更新機制
     */
    public function testTokenExpirationAndRefresh(): void
    {
        $user = User::factory()->create([
            'email' => 'tokenexp@example.com',
            'password' => Hash::make('TokenExp123!'),
            'email_verified_at' => now()
        ]);

        // 登入獲取 token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'tokenexp@example.com',
            'password' => 'TokenExp123!',
            'device_name' => 'Expiration Test'
        ]);

        $token = $loginResponse->json('data.token');
        $expiresAt = $loginResponse->json('data.expires_at');

        // 驗證 token 有效
        $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}"
        ])->assertStatus(200);

        // 檢查 token 資訊
        $tokenInfoResponse = $this->getJson('/api/v1/auth/token-info', [
            'Authorization' => "Bearer {$token}"
        ]);
        $tokenInfoResponse->assertStatus(200);
        $tokenInfoResponse->assertJsonStructure([
            'status',
            'data' => [
                'token' => [
                    'id',
                    'name',
                    'last_used_at',
                    'expires_at',
                    'is_expired',
                    'remaining_time'
                ]
            ]
        ]);

        // 模擬 token 接近過期時的刷新
        $refreshResponse = $this->postJson('/api/v1/auth/refresh-token', [], [
            'Authorization' => "Bearer {$token}"
        ]);

        if (200 === $refreshResponse->status()) {
            // 如果系統支持 token 刷新
            $newToken = $refreshResponse->json('data.token');
            $this->assertNotSame($token, $newToken);

            // 驗證新 token 有效
            $this->getJson('/api/v1/users/profile', [
                'Authorization' => "Bearer {$newToken}"
            ])->assertStatus(200);

            // 驗證舊 token 可能仍有效（根據實作決定）
            $oldTokenResponse = $this->getJson('/api/v1/users/profile', [
                'Authorization' => "Bearer {$token}"
            ]);
            $this->assertContains($oldTokenResponse->status(), [200, 401]);
        }
    }

    /**
     * 測試認證失敗場景.
     *
     * 驗證各種認證失敗的處理
     */
    public function testAuthenticationFailureScenarios(): void
    {
        $user = User::factory()->create([
            'email' => 'authfail@example.com',
            'password' => Hash::make('CorrectPassword123!'),
            'email_verified_at' => now()
        ]);

        // 測試案例 1：錯誤的密碼
        $wrongPasswordResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'authfail@example.com',
            'password' => 'WrongPassword123!',
            'device_name' => 'Test Device'
        ]);
        $wrongPasswordResponse->assertStatus(401);

        // 測試案例 2：不存在的郵箱
        $nonExistentEmailResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'CorrectPassword123!',
            'device_name' => 'Test Device'
        ]);
        $nonExistentEmailResponse->assertStatus(401);

        // 測試案例 3：無效的 token
        $invalidTokenResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => 'Bearer invalid-token'
        ]);
        $invalidTokenResponse->assertStatus(401);

        // 測試案例 4：缺少 Authorization header
        $noAuthResponse = $this->getJson('/api/v1/users/profile');
        $noAuthResponse->assertStatus(401);

        // 測試案例 5：格式錯誤的 Authorization header
        $malformedAuthResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => 'InvalidFormat token'
        ]);
        $malformedAuthResponse->assertStatus(401);
    }

    /**
     * 測試帳戶狀態對認證的影響
     *
     * 驗證被刪除或停用帳戶的認證處理
     */
    public function testAccountStatusImpactOnAuthentication(): void
    {
        // 測試軟刪除用戶的認證
        $deletedUser = User::factory()->create([
            'email' => 'deleted@example.com',
            'password' => Hash::make('DeletedUser123!'),
            'email_verified_at' => now(),
            'deleted_at' => now()
        ]);

        $deletedUserLoginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'deleted@example.com',
            'password' => 'DeletedUser123!',
            'device_name' => 'Test Device'
        ]);
        $deletedUserLoginResponse->assertStatus(401);

        // 測試未驗證郵箱用戶的認證
        $unverifiedUser = User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => Hash::make('UnverifiedUser123!'),
            'email_verified_at' => null
        ]);

        $unverifiedUserLoginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified@example.com',
            'password' => 'UnverifiedUser123!',
            'device_name' => 'Test Device'
        ]);

        // 根據系統設計，可能允許或不允許未驗證用戶登入
        $this->assertContains($unverifiedUserLoginResponse->status(), [200, 401, 403]);

        if (200 === $unverifiedUserLoginResponse->status()) {
            // 如果允許登入，檢查是否有特殊標記
            $token = $unverifiedUserLoginResponse->json('data.token');
            $profileResponse = $this->getJson('/api/v1/users/profile', [
                'Authorization' => "Bearer {$token}"
            ]);
            $profileResponse->assertStatus(200);
            $this->assertNull($profileResponse->json('data.user.email_verified_at'));
        }
    }

    /**
     * 測試同時登入限制.
     *
     * 驗證系統可能的同時登入數量限制
     */
    public function testConcurrentLoginLimits(): void
    {
        $user = User::factory()->create([
            'email' => 'concurrent@example.com',
            'password' => Hash::make('ConcurrentLogin123!'),
            'email_verified_at' => now()
        ]);

        $loginData = [
            'email' => 'concurrent@example.com',
            'password' => 'ConcurrentLogin123!',
        ];

        $tokens = [];

        // 嘗試建立多個同時登入
        for ($i = 1; $i <= 10; ++$i) {
            $loginResponse = $this->postJson('/api/v1/auth/login', array_merge($loginData, [
                'device_name' => "Device {$i}"
            ]));

            if (200 === $loginResponse->status()) {
                $tokens[] = $loginResponse->json('data.token');
            } else {
                // 如果有同時登入限制，記錄到達限制的點
                break;
            }
        }

        // 驗證所有獲得的 token 都有效
        foreach ($tokens as $token) {
            $this->getJson('/api/v1/users/profile', [
                'Authorization' => "Bearer {$token}"
            ])->assertStatus(200);
        }

        // 檢查設備清單
        if (!empty($tokens)) {
            $devicesResponse = $this->getJson('/api/v1/auth/devices', [
                'Authorization' => "Bearer {$tokens[0]}"
            ]);
            $devicesResponse->assertStatus(200);
            $deviceCount = \count($devicesResponse->json('data.devices'));
            $this->assertSame(\count($tokens), $deviceCount);
        }
    }

    /**
     * 測試認證相關的安全標頭.
     *
     * 驗證認證回應中的安全標頭
     */
    public function testAuthenticationSecurityHeaders(): void
    {
        $user = User::factory()->create([
            'email' => 'security@example.com',
            'password' => Hash::make('SecurityTest123!'),
            'email_verified_at' => now()
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'security@example.com',
            'password' => 'SecurityTest123!',
            'device_name' => 'Security Test'
        ]);

        $loginResponse->assertStatus(200);

        // 檢查安全相關標頭
        $loginResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $loginResponse->assertHeader('X-Frame-Options');
        $loginResponse->assertHeader('X-XSS-Protection');

        // 驗證回應不包含敏感資訊
        $responseData = $loginResponse->json();
        $this->assertArrayNotHasKey('password', $responseData['data']['user'] ?? []);
        $this->assertArrayNotHasKey('remember_token', $responseData['data']['user'] ?? []);
    }
}
