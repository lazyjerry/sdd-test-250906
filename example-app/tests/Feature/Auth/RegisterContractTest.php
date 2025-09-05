<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 使用者註冊 API 合約測試.
 *
 * 測試 POST /api/v1/auth/register 端點的請求/回應結構
 * 確保 API 合約符合 OpenAPI 規格定義
 *
 * @internal
 *
 * @coversNothing
 */
final class RegisterContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試註冊成功時的回應結構.
     */
    public function testRegisterSuccessResponseStructure()
    {
        $requestData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123'
        ];

        $response = $this->postJson('/api/v1/auth/register', $requestData);

        // 期望狀態碼 201 Created
        $response->assertStatus(201);

        // 期望回應結構符合 OpenAPI 規格
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'username',
                    'email',
                    'name',
                    'phone',
                    'email_verified_at',
                    'created_at',
                    'updated_at'
                ],
                'token'
            ]
        ]);

        // 驗證回應值
        $response->assertJson([
            'status' => 'success',
            'message' => '註冊成功'
        ]);
    }

    /**
     * 測試註冊驗證失敗時的回應結構.
     */
    public function testRegisterValidationErrorResponseStructure()
    {
        $requestData = [
            'username' => '', // 必填欄位留空
            'email' => 'invalid-email', // 無效 email 格式
            'password' => '123' // 密碼太短
        ];

        $response = $this->postJson('/api/v1/auth/register', $requestData);

        // 期望狀態碼 422 Unprocessable Entity
        $response->assertStatus(422);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'username',
                'email',
                'password'
            ]
        ]);

        $response->assertJson([
            'status' => 'error',
            'message' => '資料驗證失敗'
        ]);
    }

    /**
     * 測試重複註冊時的回應結構.
     */
    public function testRegisterDuplicateUserResponseStructure()
    {
        // 建立已存在的使用者
        $existingUser = [
            'username' => 'existinguser',
            'email' => 'existing@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123'
        ];

        // 第一次註冊（應該成功）
        $this->postJson('/api/v1/auth/register', $existingUser);

        // 嘗試重複註冊相同 username
        $duplicateRequest = [
            'username' => 'existinguser',
            'email' => 'different@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123'
        ];

        $response = $this->postJson('/api/v1/auth/register', $duplicateRequest);

        // 期望狀態碼 422 Unprocessable Entity
        $response->assertStatus(422);

        // 期望錯誤回應結構
        $response->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'username'
            ]
        ]);
    }

    /**
     * 測試請求內容類型要求
     */
    public function testRegisterRequiresJsonContentType()
    {
        $response = $this->post('/api/v1/auth/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123'
        ]);

        // 非 JSON 請求應該回傳 406 Not Acceptable 或適當錯誤
        $this->assertTrue(\in_array($response->status(), [406, 415, 422]));
    }
}
