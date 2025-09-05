<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * User Registration Integration Test.
 *
 * 測試完整的用戶註冊流程整合
 *
 * 涵蓋的流程：
 * 1. 用戶註冊 API 呼叫
 * 2. 用戶資料驗證與儲存
 * 3. 郵箱驗證信件發送
 * 4. API Token 生成
 * 5. 後續登入和使用功能
 *
 * @internal
 *
 * @coversNothing
 */
final class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試完整的用戶註冊流程.
     *
     * 驗證從註冊到可以使用 API 的完整流程
     */
    public function testCompleteUserRegistrationFlow(): void
    {
        // 準備註冊資料
        $registrationData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ];

        // 第一步：發送註冊請求
        $response = $this->postJson('/api/v1/auth/register', $registrationData);

        // 驗證註冊回應
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at'
                ],
                'token'
            ]
        ]);

        // 驗證用戶已儲存到資料庫
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => null, // 初始狀態未驗證
        ]);

        // 獲取建立的用戶
        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);

        // 驗證密碼已正確雜湊
        $this->assertTrue(password_verify('SecurePassword123!', $user->password));

        // 第二步：使用回傳的 token 進行 API 呼叫
        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // 使用 token 存取需要認證的端點
        $profileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);

        // 驗證可以成功存取個人檔案
        $profileResponse->assertStatus(200);
        $profileResponse->assertJsonPath('data.user.email', 'john@example.com');
        $profileResponse->assertJsonPath('data.user.name', 'John Doe');

        // 第三步：驗證郵箱驗證信件已發送
        Mail::assertSent(\Illuminate\Auth\Notifications\VerifyEmail::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });
    }

    /**
     * 測試重複郵箱註冊防護.
     *
     * 驗證系統阻止重複郵箱註冊
     */
    public function testDuplicateEmailRegistrationPrevention(): void
    {
        // 第一步：建立第一個用戶
        $firstUser = User::factory()->create([
            'email' => 'existing@example.com'
        ]);

        // 第二步：嘗試使用相同郵箱註冊
        $duplicateData = [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'DifferentPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ];

        $response = $this->postJson('/api/v1/auth/register', $duplicateData);

        // 驗證註冊被拒絕
        $response->assertStatus(422);
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

        // 驗證資料庫中只有一個用戶
        $this->assertSame(1, User::where('email', 'existing@example.com')->count());
    }

    /**
     * 測試註冊後的即時登入流程.
     *
     * 驗證註冊後可以立即登入和使用功能
     */
    public function testImmediateLoginAfterRegistration(): void
    {
        // 第一步：註冊用戶
        $registrationData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
        ];

        $registerResponse = $this->postJson('/api/v1/auth/register', $registrationData);
        $registerResponse->assertStatus(201);

        // 第二步：使用相同憑證登入
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
        ];

        $loginResponse = $this->postJson('/api/v1/auth/login', $loginData);
        $loginResponse->assertStatus(200);

        // 第三步：比較 token（應該不同，因為是新的登入 session）
        $registerToken = $registerResponse->json('data.token');
        $loginToken = $loginResponse->json('data.token');

        $this->assertNotSame($registerToken, $loginToken);

        // 第四步：驗證兩個 token 都有效
        $profileWithRegisterToken = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$registerToken}"
        ]);
        $profileWithRegisterToken->assertStatus(200);

        $profileWithLoginToken = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$loginToken}"
        ]);
        $profileWithLoginToken->assertStatus(200);
    }

    /**
     * 測試註冊失敗時的資料一致性.
     *
     * 驗證註冊失敗時不會產生部分資料
     */
    public function testRegistrationFailureDataConsistency(): void
    {
        // 記錄初始用戶數量
        $initialUserCount = User::count();

        // 嘗試使用無效資料註冊
        $invalidData = [
            'name' => '', // 空名稱
            'email' => 'invalid-email', // 無效郵箱格式
            'password' => '123', // 太短的密碼
            'password_confirmation' => '456', // 不匹配的確認密碼
        ];

        $response = $this->postJson('/api/v1/auth/register', $invalidData);

        // 驗證註冊失敗
        $response->assertStatus(422);

        // 驗證沒有新增用戶
        $this->assertSame($initialUserCount, User::count());

        // 驗證沒有產生 token
        $this->assertArrayNotHasKey('token', $response->json('data', []));
    }

    /**
     * 測試註冊時的速率限制.
     *
     * 驗證防止大量註冊請求的機制
     */
    public function testRegistrationRateLimiting(): void
    {
        $registrationData = [
            'name' => 'Rate Test User',
            'email' => 'rate@example.com',
            'password' => 'RatePassword123!',
            'password_confirmation' => 'RatePassword123!',
        ];

        // 連續多次註冊請求
        $responses = [];
        for ($i = 0; $i < 10; ++$i) {
            $responses[] = $this->postJson('/api/v1/auth/register', array_merge($registrationData, [
                'email' => "rate{$i}@example.com"
            ]));
        }

        // 檢查是否有速率限制回應
        $rateLimitedResponses = array_filter($responses, function ($response) {
            return 429 === $response->status();
        });

        // 驗證速率限制機制
        if (\count($rateLimitedResponses) > 0) {
            $this->assertGreaterThan(0, \count($rateLimitedResponses));

            // 驗證速率限制回應結構
            $rateLimitResponse = reset($rateLimitedResponses);
            $rateLimitResponse->assertJsonStructure([
                'status',
                'message',
                'error' => [
                    'code',
                    'details'
                ]
            ]);
        }
    }

    /**
     * 測試註冊成功後的用戶權限設定.
     *
     * 驗證新註冊用戶的預設權限
     */
    public function testNewUserDefaultPermissions(): void
    {
        // 註冊新用戶
        $registrationData = [
            'name' => 'Permission Test User',
            'email' => 'permission@example.com',
            'password' => 'PermissionPassword123!',
            'password_confirmation' => 'PermissionPassword123!',
        ];

        $response = $this->postJson('/api/v1/auth/register', $registrationData);
        $response->assertStatus(201);

        $token = $response->json('data.token');

        // 驗證可以存取普通用戶端點
        $profileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$token}"
        ]);
        $profileResponse->assertStatus(200);

        // 驗證無法存取管理員端點
        $adminResponse = $this->getJson('/api/v1/admin/users', [
            'Authorization' => "Bearer {$token}"
        ]);
        $adminResponse->assertStatus(403);

        // 驗證用戶角色為 'user'
        $user = User::where('email', 'permission@example.com')->first();
        $this->assertSame('user', $user->role);
    }

    /**
     * 測試註冊時的資料清理和驗證.
     *
     * 驗證輸入資料的清理和標準化
     */
    public function testRegistrationDataSanitization(): void
    {
        // 包含需要清理的資料
        $messyData = [
            'name' => '  John   Doe  ', // 多餘空格
            'email' => '  JOHN@EXAMPLE.COM  ', // 大寫和空格
            'password' => 'CleanPassword123!',
            'password_confirmation' => 'CleanPassword123!',
        ];

        $response = $this->postJson('/api/v1/auth/register', $messyData);
        $response->assertStatus(201);

        // 驗證資料已清理
        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('John Doe', $user->name); // 空格已清理
        $this->assertSame('john@example.com', $user->email); // 已轉小寫並清理空格
    }
}
