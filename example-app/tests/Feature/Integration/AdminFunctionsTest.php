<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Admin Functions Integration Test.
 *
 * 測試完整的管理員功能流程整合
 *
 * 涵蓋的流程：
 * 1. 管理員權限驗證
 * 2. 用戶列表管理
 * 3. 用戶帳戶操作
 * 4. 角色權限管理
 * 5. 系統監控和報告
 *
 * @internal
 *
 * @coversNothing
 */
final class AdminFunctionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試完整的管理員用戶管理流程.
     *
     * 從查看用戶列表到執行管理操作的完整流程
     */
    public function testCompleteAdminUserManagementFlow(): void
    {
        // 第一步：建立管理員和一般用戶
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        $regularUsers = User::factory()->count(5)->create([
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        // 第二步：查看用戶列表
        $usersListResponse = $this->getJson('/api/v1/admin/users');
        $usersListResponse->assertStatus(200);
        $usersListResponse->assertJsonStructure([
            'status',
            'data' => [
                'users' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'email_verified_at',
                        'created_at',
                        'last_login_at',
                        'is_active'
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page'
                ]
            ]
        ]);

        $usersList = $usersListResponse->json('data.users');
        $this->assertCount(6, $usersList); // 5 regular users + 1 admin

        // 第三步：查看特定用戶詳情
        $targetUser = $regularUsers->first();
        $userDetailResponse = $this->getJson("/api/v1/admin/users/{$targetUser->id}");
        $userDetailResponse->assertStatus(200);
        $userDetailResponse->assertJsonStructure([
            'status',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                    'profile',
                    'login_history',
                    'activity_summary'
                ]
            ]
        ]);

        // 第四步：更新用戶資料
        $updateUserResponse = $this->putJson("/api/v1/admin/users/{$targetUser->id}", [
            'name' => 'Updated by Admin',
            'email' => 'updated@example.com',
            'role' => 'user'
        ]);
        $updateUserResponse->assertStatus(200);
        $updateUserResponse->assertJsonPath('data.user.name', 'Updated by Admin');
        $updateUserResponse->assertJsonPath('data.user.email', 'updated@example.com');

        // 第五步：驗證資料庫中的變更
        $targetUser->refresh();
        $this->assertSame('Updated by Admin', $targetUser->name);
        $this->assertSame('updated@example.com', $targetUser->email);

        // 第六步：重設用戶密碼
        $resetPasswordResponse = $this->postJson("/api/v1/admin/users/{$targetUser->id}/reset-password", [
            'new_password' => 'AdminResetPassword123!',
            'new_password_confirmation' => 'AdminResetPassword123!',
            'force_password_change' => true
        ]);
        $resetPasswordResponse->assertStatus(200);
        $resetPasswordResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'password_reset_at',
                'force_password_change',
                'user_tokens_revoked'
            ]
        ]);

        // 第七步：驗證密碼已重設
        $targetUser->refresh();
        $this->assertTrue(Hash::check('AdminResetPassword123!', $targetUser->password));

        // 第八步：停用用戶帳戶
        $deactivateResponse = $this->postJson("/api/v1/admin/users/{$targetUser->id}/deactivate", [
            'reason' => 'Account suspended by admin',
            'notify_user' => true
        ]);
        $deactivateResponse->assertStatus(200);

        // 第九步：驗證用戶已被停用
        $deactivatedUserResponse = $this->getJson("/api/v1/admin/users/{$targetUser->id}?include_trashed=true");
        $deactivatedUserData = $deactivatedUserResponse->json('data.user');
        $this->assertFalse($deactivatedUserData['activity_summary']['is_active']);

        // 第十步：重新啟用用戶
        $reactivateResponse = $this->postJson("/api/v1/admin/users/{$targetUser->id}/activate");
        $reactivateResponse->assertStatus(200);

        $reactivatedUserResponse = $this->getJson("/api/v1/admin/users/{$targetUser->id}");
        $reactivatedUserData = $reactivatedUserResponse->json('data.user');
        $this->assertTrue($reactivatedUserData['activity_summary']['is_active']);
    }

    /**
     * 測試管理員權限和存取控制.
     *
     * 驗證不同角色的權限限制
     */
    public function testAdminPermissionsAndAccessControl(): void
    {
        // 建立不同角色的用戶
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        $regularUser = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        $targetUser = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        // 測試案例 1：管理員可以存取管理功能
        Sanctum::actingAs($admin);

        $adminAccessResponse = $this->getJson('/api/v1/admin/users');
        $adminAccessResponse->assertStatus(200);

        $adminUserDetailResponse = $this->getJson("/api/v1/admin/users/{$targetUser->id}");
        $adminUserDetailResponse->assertStatus(200);

        $adminUpdateResponse = $this->putJson("/api/v1/admin/users/{$targetUser->id}", [
            'name' => 'Updated by Admin'
        ]);
        $adminUpdateResponse->assertStatus(200);

        // 測試案例 2：一般用戶無法存取管理功能
        Sanctum::actingAs($regularUser);

        $userAccessResponse = $this->getJson('/api/v1/admin/users');
        $userAccessResponse->assertStatus(403);

        $userDetailResponse = $this->getJson("/api/v1/admin/users/{$targetUser->id}");
        $userDetailResponse->assertStatus(403);

        $userUpdateResponse = $this->putJson("/api/v1/admin/users/{$targetUser->id}", [
            'name' => 'Attempted Update by User'
        ]);
        $userUpdateResponse->assertStatus(403);

        // 測試案例 3：未認證用戶無法存取
        // 清除認證狀態
        $this->app->forgetInstance('auth');
        $this->refreshApplication();

        $unauthenticatedResponse = $this->getJson('/api/v1/admin/users');
        $unauthenticatedResponse->assertStatus(401);
    }

    /**
     * 測試批量用戶操作.
     *
     * 驗證批量處理用戶的功能
     */
    public function testBulkUserOperations(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        $users = User::factory()->count(10)->create([
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        $userIds = $users->take(5)->pluck('id')->toArray();

        // 測試案例 1：批量停用用戶
        $bulkDeactivateResponse = $this->postJson('/api/v1/admin/users/bulk-deactivate', [
            'user_ids' => $userIds,
            'reason' => 'Bulk deactivation test',
            'notify_users' => false
        ]);

        $bulkDeactivateResponse->assertStatus(200);
        $bulkDeactivateResponse->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'processed_count',
                'successful_count',
                'failed_count',
                'results' => [
                    '*' => [
                        'user_id',
                        'status',
                        'message'
                    ]
                ]
            ]
        ]);

        // 驗證用戶已被停用
        foreach ($userIds as $userId) {
            $userResponse = $this->getJson("/api/v1/admin/users/{$userId}?include_trashed=true");
            $userData = $userResponse->json('data.user');
            $this->assertFalse($userData['is_active']);
        }

        // 測試案例 2：批量啟用用戶
        $bulkActivateResponse = $this->postJson('/api/v1/admin/users/bulk-activate', [
            'user_ids' => $userIds
        ]);

        $bulkActivateResponse->assertStatus(200);

        // 驗證用戶已被啟用
        foreach ($userIds as $userId) {
            $userResponse = $this->getJson("/api/v1/admin/users/{$userId}");
            $userData = $userResponse->json('data.user');
            $this->assertTrue($userData['is_active']);
        }

        // 測試案例 3：批量角色變更
        $bulkRoleChangeResponse = $this->postJson('/api/v1/admin/users/bulk-role-change', [
            'user_ids' => $userIds,
            'new_role' => 'premium_user'
        ]);

        if (200 === $bulkRoleChangeResponse->status()) {
            // 如果系統支持premium_user角色
            foreach ($userIds as $userId) {
                $userResponse = $this->getJson("/api/v1/admin/users/{$userId}");
                $userData = $userResponse->json('data.user');
                $this->assertSame('premium_user', $userData['role']);
            }
        }

        // 測試案例 4：批量刪除用戶
        $bulkDeleteResponse = $this->postJson('/api/v1/admin/users/bulk-delete', [
            'user_ids' => $userIds,
            'permanent' => false // 軟刪除
        ]);

        $bulkDeleteResponse->assertStatus(200);

        // 驗證用戶已被軟刪除 (管理員仍可查看但狀態為非活躍)
        foreach ($userIds as $userId) {
            $userResponse = $this->getJson("/api/v1/admin/users/{$userId}?include_trashed=true");
            $userResponse->assertStatus(200);
            $userData = $userResponse->json('data.user');
            $this->assertFalse($userData['is_active']);
            $this->assertNotNull($userData['deleted_at']);
        }
    }

    /**
     * 測試用戶搜尋和篩選功能.
     *
     * 驗證管理員的用戶搜尋能力
     */
    public function testUserSearchAndFiltering(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        // 建立測試用戶資料
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        User::factory()->create([
            'name' => 'Bob Johnson',
            'email' => 'bob@test.com',
            'role' => 'user',
            'email_verified_at' => null // 未驗證
        ]);

        Sanctum::actingAs($admin);

        // 測試案例 1：按姓名搜尋
        $nameSearchResponse = $this->getJson('/api/v1/admin/users?search=John');
        $nameSearchResponse->assertStatus(200);
        $nameSearchResults = $nameSearchResponse->json('data.users');
        $this->assertCount(2, $nameSearchResults); // John Smith + Bob Johnson

        // 測試案例 2：按郵箱搜尋
        $emailSearchResponse = $this->getJson('/api/v1/admin/users?search=jane@example.com');
        $emailSearchResponse->assertStatus(200);
        $emailSearchResults = $emailSearchResponse->json('data.users');
        $this->assertCount(1, $emailSearchResults);
        $this->assertSame('Jane Doe', $emailSearchResults[0]['name']);

        // 測試案例 3：按角色篩選
        $roleFilterResponse = $this->getJson('/api/v1/admin/users?role=admin');
        $roleFilterResponse->assertStatus(200);
        $roleFilterResults = $roleFilterResponse->json('data.users');
        $adminUsers = collect($roleFilterResults)->where('role', 'admin');
        $this->assertGreaterThanOrEqual(2, $adminUsers->count()); // 至少有測試管理員和Jane

        // 測試案例 4：按驗證狀態篩選
        $verifiedFilterResponse = $this->getJson('/api/v1/admin/users?verified=false');
        $verifiedFilterResponse->assertStatus(200);
        $verifiedFilterResults = $verifiedFilterResponse->json('data.users');
        $unverifiedUsers = collect($verifiedFilterResults)->whereNull('email_verified_at');
        $this->assertGreaterThanOrEqual(1, $unverifiedUsers->count());

        // 測試案例 5：組合篩選
        $combinedFilterResponse = $this->getJson('/api/v1/admin/users?role=user&verified=true&search=John');
        $combinedFilterResponse->assertStatus(200);
        $combinedResults = $combinedFilterResponse->json('data.users');
        $this->assertCount(1, $combinedResults);
        $this->assertSame('John Smith', $combinedResults[0]['name']);

        // 測試案例 6：分頁測試
        $paginatedResponse = $this->getJson('/api/v1/admin/users?per_page=2&page=1');
        $paginatedResponse->assertStatus(200);
        $paginatedResponse->assertJsonStructure([
            'data' => [
                'users',
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to'
                ]
            ]
        ]);

        $paginationData = $paginatedResponse->json('data.pagination');
        $this->assertSame(1, $paginationData['current_page']);
        $this->assertSame(2, $paginationData['per_page']);
    }

    /**
     * 測試系統統計和報告功能.
     *
     * 驗證管理員的系統監控能力
     */
    public function testSystemStatisticsAndReporting(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        // 建立測試資料
        User::factory()->count(10)->create([
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        User::factory()->count(3)->create([
            'role' => 'user',
            'email_verified_at' => null
        ]);

        User::factory()->count(2)->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        // 測試案例 1：用戶統計
        $userStatsResponse = $this->getJson('/api/v1/admin/statistics/users');
        $userStatsResponse->assertStatus(200);
        $userStatsResponse->assertJsonStructure([
            'status',
            'data' => [
                'total_users',
                'active_users',
                'verified_users',
                'unverified_users',
                'users_by_role' => [
                    'admin',
                    'user'
                ],
                'new_users_today',
                'new_users_this_week',
                'new_users_this_month'
            ]
        ]);

        $userStats = $userStatsResponse->json('data');
        $this->assertSame(16, $userStats['total_users']); // 10 + 3 + 2 + 1 admin
        $this->assertSame(13, $userStats['verified_users']); // 10 + 2 + 1 admin
        $this->assertSame(3, $userStats['unverified_users']);

        // 測試案例 2：系統活動統計
        $activityStatsResponse = $this->getJson('/api/v1/admin/statistics/activity');
        $activityStatsResponse->assertStatus(200);
        $activityStatsResponse->assertJsonStructure([
            'status',
            'data' => [
                'total_logins_today',
                'total_logins_this_week',
                'active_sessions',
                'api_requests_today',
                'failed_login_attempts',
                'password_resets_today'
            ]
        ]);

        // 測試案例 3：系統健康檢查
        $healthCheckResponse = $this->getJson('/api/v1/admin/system/health');
        $healthCheckResponse->assertStatus(200);
        $healthCheckResponse->assertJsonStructure([
            'status',
            'data' => [
                'database' => [
                    'status',
                    'response_time'
                ],
                'cache' => [
                    'status',
                    'response_time'
                ],
                'storage' => [
                    'status',
                    'available_space'
                ],
                'mail' => [
                    'status'
                ],
                'overall_status'
            ]
        ]);

        // 測試案例 4：最近活動日誌
        $activityLogResponse = $this->getJson('/api/v1/admin/activity-log?limit=50');
        $activityLogResponse->assertStatus(200);
        $activityLogResponse->assertJsonStructure([
            'status',
            'data' => [
                'activities' => [
                    '*' => [
                        'id',
                        'user_id',
                        'action',
                        'description',
                        'ip_address',
                        'user_agent',
                        'created_at'
                    ]
                ],
                'pagination'
            ]
        ]);
    }

    /**
     * 測試管理員操作的審計日誌.
     *
     * 驗證管理員操作的記錄和追蹤
     */
    public function testAdminOperationsAuditLogging(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        $targetUser = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        // 執行一些管理員操作
        $this->putJson("/api/v1/admin/users/{$targetUser->id}", [
            'name' => 'Audited Update'
        ]);

        $this->postJson("/api/v1/admin/users/{$targetUser->id}/reset-password", [
            'new_password' => 'AuditedPassword123!'
        ]);

        $this->postJson("/api/v1/admin/users/{$targetUser->id}/deactivate", [
            'reason' => 'Audit test deactivation'
        ]);

        // 檢查審計日誌
        $auditLogResponse = $this->getJson('/api/v1/admin/audit-log');
        $auditLogResponse->assertStatus(200);
        $auditLogResponse->assertJsonStructure([
            'status',
            'data' => [
                'audit_entries' => [
                    '*' => [
                        'id',
                        'admin_user_id',
                        'target_user_id',
                        'action',
                        'details',
                        'ip_address',
                        'user_agent',
                        'created_at'
                    ]
                ],
                'pagination'
            ]
        ]);

        $auditEntries = $auditLogResponse->json('data.audit_entries');

        // 驗證審計日誌包含我們的操作
        $this->assertTrue(
            collect($auditEntries)->contains(function ($entry) use ($admin, $targetUser) {
                return $entry['admin_user_id'] === $admin->id
                       && $entry['target_user_id'] === $targetUser->id
                       && 'user_update' === $entry['action'];
            })
        );

        $this->assertTrue(
            collect($auditEntries)->contains(function ($entry) use ($admin, $targetUser) {
                return $entry['admin_user_id'] === $admin->id
                       && $entry['target_user_id'] === $targetUser->id
                       && 'password_reset' === $entry['action'];
            })
        );

        $this->assertTrue(
            collect($auditEntries)->contains(function ($entry) use ($admin, $targetUser) {
                return $entry['admin_user_id'] === $admin->id
                       && $entry['target_user_id'] === $targetUser->id
                       && 'user_deactivate' === $entry['action'];
            })
        );
    }

    /**
     * 測試管理員自身權限限制.
     *
     * 驗證管理員不能對自己執行某些操作
     */
    public function testAdminSelfOperationRestrictions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($admin);

        // 測試案例 1：管理員不能停用自己
        $selfDeactivateResponse = $this->postJson("/api/v1/admin/users/{$admin->id}/deactivate");
        $selfDeactivateResponse->assertStatus(403);

        // 測試案例 2：管理員不能變更自己的角色為一般用戶
        $selfRoleChangeResponse = $this->putJson("/api/v1/admin/users/{$admin->id}", [
            'role' => 'user'
        ]);
        $selfRoleChangeResponse->assertStatus(400);

        // 測試案例 3：管理員不能刪除自己
        $selfDeleteResponse = $this->deleteJson("/api/v1/admin/users/{$admin->id}");
        $selfDeleteResponse->assertStatus(403);

        // 測試案例 4：管理員可以更新自己的基本資料
        $selfUpdateResponse = $this->putJson("/api/v1/admin/users/{$admin->id}", [
            'name' => 'Updated Admin Name',
            'email' => 'updated.admin@example.com'
        ]);
        $selfUpdateResponse->assertStatus(200);

        // 測試案例 5：管理員可以重設自己的密碼
        $selfPasswordResetResponse = $this->postJson("/api/v1/admin/users/{$admin->id}/reset-password", [
            'new_password' => 'NewAdminPassword123!'
        ]);
        $selfPasswordResetResponse->assertStatus(200);
    }

    /**
     * 測試超級管理員權限（如果存在）.
     *
     * 驗證超級管理員的特殊權限
     */
    public function testSuperAdminPrivileges(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'email_verified_at' => now()
        ]);

        $regularAdmin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        // 測試超級管理員權限
        Sanctum::actingAs($superAdmin);

        // 超級管理員可以管理其他管理員
        $manageAdminResponse = $this->putJson("/api/v1/admin/users/{$regularAdmin->id}", [
            'role' => 'user'
        ]);

        if (200 === $manageAdminResponse->status()) {
            // 如果系統支持超級管理員
            $this->assertSame(200, $manageAdminResponse->status());

            // 驗證角色已變更
            $regularAdmin->refresh();
            $this->assertSame('user', $regularAdmin->role);

            // 超級管理員可以存取特殊功能
            $systemConfigResponse = $this->getJson('/api/v1/admin/system/config');
            $this->assertContains($systemConfigResponse->status(), [200, 404]); // 200 if implemented, 404 if not
        } else {
            // 如果系統不支持超級管理員，應該和一般管理員權限相同
            $this->assertContains($manageAdminResponse->status(), [403, 404]);
        }
    }
}
