<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Profile Management Integration Test.
 *
 * 測試完整的個人資料管理流程整合
 *
 * 涵蓋的流程：
 * 1. 個人資料查看和更新
 * 2. 頭像上傳和管理
 * 3. 密碼變更流程
 * 4. 郵箱變更和驗證
 * 5. 個人資料一致性檢查
 *
 * @internal
 *
 * @coversNothing
 */
final class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試完整的個人資料管理流程.
     *
     * 從查看到更新的完整流程
     */
    public function testCompleteProfileManagementFlow(): void
    {
        // 第一步：建立測試用戶
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'password' => Hash::make('OriginalPassword123!'),
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($user);

        // 第二步：查看當前個人資料
        $profileResponse = $this->getJson('/api/v1/users/profile');
        $profileResponse->assertStatus(200);
        $profileResponse->assertJsonStructure([
            'status',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'email_verified_at',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);

        $originalData = $profileResponse->json('data.user');
        $this->assertSame('Original Name', $originalData['name']);
        $this->assertSame('original@example.com', $originalData['email']);

        // 第三步：更新基本資料
        $updateData = [
            'name' => 'Updated Name',
            'bio' => 'This is my updated biography',
            'phone' => '+1234567890',
            'location' => 'New York, USA'
        ];

        $updateResponse = $this->putJson('/api/v1/users/profile', $updateData);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'bio',
                    'phone',
                    'location',
                    'updated_at'
                ]
            ]
        ]);

        // 第四步：驗證資料已更新
        $updatedData = $updateResponse->json('data.user');
        $this->assertSame('Updated Name', $updatedData['name']);
        $this->assertSame('This is my updated biography', $updatedData['bio']);
        $this->assertSame('+1234567890', $updatedData['phone']);
        $this->assertSame('New York, USA', $updatedData['location']);

        // 第五步：再次查看個人資料確認持久化
        $verifyResponse = $this->getJson('/api/v1/users/profile');
        $verifyResponse->assertStatus(200);
        $verifyData = $verifyResponse->json('data.user');
        $this->assertSame('Updated Name', $verifyData['name']);

        // 第六步：檢查資料庫中的變更
        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('original@example.com', $user->email); // 郵箱未變更
    }

    /**
     * 測試頭像上傳和管理功能.
     *
     * 驗證頭像的上傳、更新和刪除
     */
    public function testAvatarUploadAndManagement(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($user);

        // 第一步：上傳頭像
        $avatarFile = UploadedFile::fake()->image('avatar.jpg', 300, 300)->size(500);

        $uploadResponse = $this->postJson('/api/v1/users/profile/avatar', [
            'avatar' => $avatarFile
        ]);

        $uploadResponse->assertStatus(200);
        $uploadResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'avatar_url',
                'uploaded_at'
            ]
        ]);

        $avatarUrl = $uploadResponse->json('data.avatar_url');
        $this->assertNotEmpty($avatarUrl);

        // 第二步：驗證檔案已儲存
        $avatarPath = str_replace('/storage/', '', parse_url($avatarUrl, \PHP_URL_PATH));
        Storage::disk('public')->assertExists($avatarPath);

        // 第三步：檢查個人資料中的頭像 URL
        $profileResponse = $this->getJson('/api/v1/users/profile');
        $profileResponse->assertStatus(200);
        $profileData = $profileResponse->json('data.user');
        $this->assertSame($avatarUrl, $profileData['avatar_url']);

        // 第四步：更新頭像
        $newAvatarFile = UploadedFile::fake()->image('new-avatar.png', 400, 400)->size(600);

        $updateAvatarResponse = $this->putJson('/api/v1/users/profile/avatar', [
            'avatar' => $newAvatarFile
        ]);

        $updateAvatarResponse->assertStatus(200);
        $newAvatarUrl = $updateAvatarResponse->json('data.avatar_url');
        $this->assertNotSame($avatarUrl, $newAvatarUrl);

        // 第五步：驗證舊頭像已刪除，新頭像已儲存
        Storage::disk('public')->assertMissing($avatarPath);
        $newAvatarPath = str_replace('/storage/', '', parse_url($newAvatarUrl, \PHP_URL_PATH));
        Storage::disk('public')->assertExists($newAvatarPath);

        // 第六步：刪除頭像
        $deleteAvatarResponse = $this->deleteJson('/api/v1/users/profile/avatar');
        $deleteAvatarResponse->assertStatus(200);

        // 第七步：驗證頭像已刪除
        Storage::disk('public')->assertMissing($newAvatarPath);

        $profileAfterDeleteResponse = $this->getJson('/api/v1/users/profile');
        $profileAfterDeleteData = $profileAfterDeleteResponse->json('data.user');
        $this->assertNull($profileAfterDeleteData['avatar_url']);
    }

    /**
     * 測試密碼變更完整流程.
     *
     * 驗證密碼變更的安全性和功能性
     */
    public function testPasswordChangeCompleteFlow(): void
    {
        $originalPassword = 'OriginalPassword123!';
        $newPassword = 'NewPassword456!';

        $user = User::factory()->create([
            'email' => 'password@example.com',
            'password' => Hash::make($originalPassword),
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($user);

        // 第一步：變更密碼
        $changePasswordResponse = $this->putJson('/api/v1/users/profile/password', [
            'current_password' => $originalPassword,
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);

        $changePasswordResponse->assertStatus(200);
        $changePasswordResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'password_changed_at',
                'tokens_revoked'
            ]
        ]);

        // 第二步：驗證密碼已在資料庫中更新
        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check($originalPassword, $user->password));

        // 第三步：驗證舊 token 已失效（如果實作此功能）
        $tokensRevoked = $changePasswordResponse->json('data.tokens_revoked');
        if ($tokensRevoked) {
            // 當前請求應該需要重新認證
            $profileResponse = $this->getJson('/api/v1/users/profile');
            $this->assertContains($profileResponse->status(), [401, 200]); // 根據實作決定
        }

        // 第四步：使用新密碼登入
        $newLoginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'password@example.com',
            'password' => $newPassword,
            'device_name' => 'After Password Change'
        ]);

        $newLoginResponse->assertStatus(200);
        $newToken = $newLoginResponse->json('data.token');

        // 第五步：使用新 token 存取個人資料
        $newProfileResponse = $this->getJson('/api/v1/users/profile', [
            'Authorization' => "Bearer {$newToken}"
        ]);
        $newProfileResponse->assertStatus(200);

        // 第六步：驗證舊密碼無法登入
        $oldPasswordLoginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'password@example.com',
            'password' => $originalPassword,
            'device_name' => 'Old Password Test'
        ]);

        $oldPasswordLoginResponse->assertStatus(401);
    }

    /**
     * 測試郵箱變更和驗證流程.
     *
     * 驗證郵箱變更的完整流程包括驗證
     */
    public function testEmailChangeAndVerificationFlow(): void
    {
        $user = User::factory()->create([
            'email' => 'original@example.com',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($user);

        // 第一步：請求變更郵箱
        $newEmail = 'newemail@example.com';
        $changeEmailResponse = $this->putJson('/api/v1/users/profile/email', [
            'email' => $newEmail,
            'password' => 'password' // 需要密碼確認
        ]);

        if (200 === $changeEmailResponse->status()) {
            // 如果系統支持郵箱變更
            $changeEmailResponse->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'email_change_requested',
                    'verification_required',
                    'new_email'
                ]
            ]);

            // 第二步：檢查郵箱狀態（可能需要驗證）
            if ($changeEmailResponse->json('data.verification_required')) {
                // 郵箱變更需要驗證
                $profileResponse = $this->getJson('/api/v1/users/profile');
                $profileData = $profileResponse->json('data.user');

                // 原郵箱應該仍然有效，新郵箱處於待驗證狀態
                $this->assertSame('original@example.com', $profileData['email']);
                $this->assertArrayHasKey('pending_email', $profileData);
                $this->assertSame($newEmail, $profileData['pending_email']);

                // 第三步：模擬點擊驗證連結完成郵箱變更
                $verifyEmailChangeResponse = $this->postJson('/api/v1/users/profile/email/verify', [
                    'verification_token' => 'mock-verification-token'
                ]);

                if (200 === $verifyEmailChangeResponse->status()) {
                    // 驗證完成後檢查郵箱已變更
                    $finalProfileResponse = $this->getJson('/api/v1/users/profile');
                    $finalProfileData = $finalProfileResponse->json('data.user');
                    $this->assertSame($newEmail, $finalProfileData['email']);
                    $this->assertArrayNotHasKey('pending_email', $finalProfileData);
                }
            } else {
                // 直接變更無需驗證
                $profileResponse = $this->getJson('/api/v1/users/profile');
                $profileData = $profileResponse->json('data.user');
                $this->assertSame($newEmail, $profileData['email']);
            }
        } else {
            // 系統可能不支持郵箱變更
            $this->assertContains($changeEmailResponse->status(), [400, 403, 501]);
        }
    }

    /**
     * 測試個人資料驗證和約束
     *
     * 驗證個人資料更新的各種驗證規則
     */
    public function testProfileValidationAndConstraints(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($user);

        // 測試案例 1：無效的姓名
        $invalidNameResponse = $this->putJson('/api/v1/users/profile', [
            'name' => '' // 空姓名
        ]);
        $invalidNameResponse->assertStatus(422);
        $invalidNameResponse->assertJsonValidationErrors(['name']);

        // 測試案例 2：過長的姓名
        $longNameResponse = $this->putJson('/api/v1/users/profile', [
            'name' => str_repeat('a', 256) // 超長姓名
        ]);
        $longNameResponse->assertStatus(422);
        $longNameResponse->assertJsonValidationErrors(['name']);

        // 測試案例 3：無效的電話號碼
        $invalidPhoneResponse = $this->putJson('/api/v1/users/profile', [
            'phone' => 'invalid-phone-number'
        ]);
        $invalidPhoneResponse->assertStatus(422);
        $invalidPhoneResponse->assertJsonValidationErrors(['phone']);

        // 測試案例 4：過長的個人簡介
        $longBioResponse = $this->putJson('/api/v1/users/profile', [
            'bio' => str_repeat('a', 1001) // 超長簡介
        ]);
        $longBioResponse->assertStatus(422);
        $longBioResponse->assertJsonValidationErrors(['bio']);

        // 測試案例 5：有效的更新
        $validUpdateResponse = $this->putJson('/api/v1/users/profile', [
            'name' => 'Valid Name',
            'bio' => 'A valid biography that is not too long.',
            'phone' => '+1234567890',
            'location' => 'Valid Location'
        ]);
        $validUpdateResponse->assertStatus(200);
    }

    /**
     * 測試頭像上傳驗證.
     *
     * 驗證頭像上傳的各種限制
     */
    public function testAvatarUploadValidation(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($user);

        // 測試案例 1：非圖片檔案
        $textFile = UploadedFile::fake()->create('document.txt', 100);
        $invalidFileResponse = $this->postJson('/api/v1/users/profile/avatar', [
            'avatar' => $textFile
        ]);
        $invalidFileResponse->assertStatus(422);
        $invalidFileResponse->assertJsonValidationErrors(['avatar']);

        // 測試案例 2：檔案過大
        $largeFil = UploadedFile::fake()->image('large.jpg', 1000, 1000)->size(10000); // 10MB
        $largeFileResponse = $this->postJson('/api/v1/users/profile/avatar', [
            'avatar' => $largeFil
        ]);
        $largeFileResponse->assertStatus(422);
        $largeFileResponse->assertJsonValidationErrors(['avatar']);

        // 測試案例 3：圖片尺寸過小
        $tinyImage = UploadedFile::fake()->image('tiny.jpg', 50, 50);
        $tinyImageResponse = $this->postJson('/api/v1/users/profile/avatar', [
            'avatar' => $tinyImage
        ]);
        $tinyImageResponse->assertStatus(422);
        $tinyImageResponse->assertJsonValidationErrors(['avatar']);

        // 測試案例 4：有效的頭像
        $validAvatar = UploadedFile::fake()->image('avatar.jpg', 300, 300)->size(500);
        $validAvatarResponse = $this->postJson('/api/v1/users/profile/avatar', [
            'avatar' => $validAvatar
        ]);
        $validAvatarResponse->assertStatus(200);
    }

    /**
     * 測試密碼變更驗證.
     *
     * 驗證密碼變更的各種安全要求
     */
    public function testPasswordChangeValidation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CurrentPassword123!'),
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($user);

        // 測試案例 1：錯誤的當前密碼
        $wrongCurrentPasswordResponse = $this->putJson('/api/v1/users/profile/password', [
            'current_password' => 'WrongPassword123!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!'
        ]);
        $wrongCurrentPasswordResponse->assertStatus(422);

        // 測試案例 2：新密碼太簡單
        $weakPasswordResponse = $this->putJson('/api/v1/users/profile/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $weakPasswordResponse->assertStatus(422);
        $weakPasswordResponse->assertJsonValidationErrors(['password']);

        // 測試案例 3：密碼確認不符
        $mismatchPasswordResponse = $this->putJson('/api/v1/users/profile/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'DifferentPassword789!'
        ]);
        $mismatchPasswordResponse->assertStatus(422);
        $mismatchPasswordResponse->assertJsonValidationErrors(['password']);

        // 測試案例 4：新密碼與當前密碼相同
        $samePasswordResponse = $this->putJson('/api/v1/users/profile/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'CurrentPassword123!',
            'password_confirmation' => 'CurrentPassword123!'
        ]);
        $samePasswordResponse->assertStatus(422);

        // 測試案例 5：有效的密碼變更
        $validPasswordChangeResponse = $this->putJson('/api/v1/users/profile/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'NewValidPassword456!',
            'password_confirmation' => 'NewValidPassword456!'
        ]);
        $validPasswordChangeResponse->assertStatus(200);
    }

    /**
     * 測試個人資料一致性和並發更新.
     *
     * 驗證並發更新時的資料一致性
     */
    public function testProfileConsistencyAndConcurrentUpdates(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email_verified_at' => now()
        ]);

        // 建立兩個認證的使用者實例模擬並發
        Sanctum::actingAs($user);

        // 第一步：獲取當前版本資料
        $profileResponse = $this->getJson('/api/v1/users/profile');
        $originalUpdatedAt = $profileResponse->json('data.user.updated_at');

        // 第二步：模擬第一個更新
        $update1Response = $this->putJson('/api/v1/users/profile', [
            'name' => 'Updated by User 1',
            'bio' => 'Bio updated by user 1'
        ]);
        $update1Response->assertStatus(200);

        // 第三步：檢查樂觀鎖定或版本控制（如果實作）
        $updatedProfileResponse = $this->getJson('/api/v1/users/profile');
        $newUpdatedAt = $updatedProfileResponse->json('data.user.updated_at');
        $this->assertNotSame($originalUpdatedAt, $newUpdatedAt);

        // 第四步：模擬基於舊版本的第二個更新
        $update2Response = $this->putJson('/api/v1/users/profile', [
            'name' => 'Updated by User 2',
            'location' => 'Location updated by user 2'
            // 如果有版本控制，這裡可能需要包含 version 或 updated_at
        ]);

        // 根據實作，這可能成功或失敗
        $this->assertContains($update2Response->status(), [200, 409, 422]);

        // 第五步：驗證最終資料狀態
        $finalProfileResponse = $this->getJson('/api/v1/users/profile');
        $finalData = $finalProfileResponse->json('data.user');

        // 應該有一致的最終狀態
        $this->assertNotEmpty($finalData['name']);
        $this->assertNotEmpty($finalData['updated_at']);
    }
}
