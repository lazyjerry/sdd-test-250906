<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class AdminController extends Controller
{
    /**
     * 構造函數 - 檢查管理員權限.
     */
    public function __construct()
    {
        // 在控制器中需要確保用戶已認證且為管理員
        // 但 middleware 調用應該在路由層級或者使用特定的中間件
    }

    /**
     * 獲取所有用戶列表.
     */
    public function getUsers(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $query = User::query();

        $appliedFilters = [];
        $searchQuery = null;

        // 支援搜尋
        if ($search = $request->get('search')) {
            $searchQuery = $search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // 支援角色過濾
        if ($role = $request->get('role')) {
            $appliedFilters['role'] = $role;
            $query->where('role', $role);
        }

        // 支援驗證狀態過濾
        if ($request->has('verified')) {
            $verified = $request->boolean('verified');
            $appliedFilters['verified'] = $verified;
            if ($verified) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // 支援分頁
        $perPage = min($request->get('per_page', 15), 100); // 限制最大每頁數量
        $users = $query->paginate($perPage);

        $responseData = [
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ];

        // 如果有搜索查詢，添加搜索信息
        if ($searchQuery) {
            $responseData['search_query'] = $searchQuery;
        }

        // 如果有應用過濾器，添加過濾器信息
        if (!empty($appliedFilters)) {
            $responseData['filters'] = $appliedFilters;
        }

        return response()->json([
            'status' => 'success',
            'message' => '用戶列表獲取成功',
            'data' => $responseData,
        ], 200);
    }

    /**
     * 獲取特定用戶資料.
     */
    public function getUser(Request $request, $id): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        // 檢查是否要包含軟刪除的用戶（支援兩種參數名稱）
        $includeTrashed = $request->boolean('include_trashed', false) || $request->boolean('include_deleted', false);

        $user = $includeTrashed ? User::withTrashed()->find($id) : User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => '找不到指定的用戶',
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'details' => '指定 ID 的用戶不存在'
                ]
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => '用戶資料獲取成功',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'email_verified_at' => $user->email_verified_at,
                    'role' => $user->role,
                    'is_active' => null === $user->deleted_at,
                    'last_login_at' => $user->last_login_at ?? null,
                    'api_tokens_count' => $user->tokens()->count(),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'deleted_at' => $user->deleted_at,
                    'profile' => [
                        'bio' => $user->bio ?? null,
                        'avatar' => $user->avatar ?? null,
                        'timezone' => $user->timezone ?? null,
                        'language' => $user->language ?? 'zh-TW',
                    ],
                    'login_history' => [
                        'last_login_at' => $user->last_login_at,
                        'login_count' => $user->login_count ?? 0,
                        'last_login_ip' => $user->last_login_ip ?? null,
                    ],
                    'activity_summary' => [
                        'tokens_count' => $user->tokens()->count(),
                        'active_tokens' => $user->tokens()->where('expires_at', '>', now())->count(),
                        'is_active' => null === $user->deleted_at,
                        'account_status' => $user->deleted_at ? 'inactive' : 'active',
                    ],
                ],
            ],
        ], 200);
    }

    /**
     * 更新用戶資料.
     */
    public function updateUser(Request $request, $id): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => '找不到指定的用戶',
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'details' => '指定 ID 的用戶不存在'
                ]
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['sometimes', 'required', 'string', 'in:user,admin'],
            'username' => ['sometimes', 'required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email_verified_at' => ['sometimes', 'nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => '資料驗證失敗',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors(),
                    'validation_errors' => $validator->errors()
                ]
            ], 422);
        }

        // 檢查管理員是否試圖降級自己
        if ($request->has('role') && $request->user()->id === (int) $id && 'admin' !== $request->role) {
            return response()->json([
                'status' => 'error',
                'message' => '管理員不能降級自己的權限',
                'error' => [
                    'code' => 'SELF_DEMOTION_FORBIDDEN',
                    'details' => '為了系統安全，管理員不能修改自己的權限等級'
                ]
            ], 400);
        }

        // 記錄原始 email 用於檢查是否需要重設驗證狀態
        $originalEmail = $user->email;

        $user->update($request->only(['name', 'email', 'role', 'username', 'phone']));

        // 處理 email 驗證狀態
        if ($request->has('email_verified_at')) {
            // 如果明確設定了 email_verified_at，使用該值
            $user->email_verified_at = $request->email_verified_at;
            $user->save();
        } elseif ($request->has('email') && $request->email !== $originalEmail) {
            // 如果 email 被修改且沒有明確設定驗證狀態，重設驗證狀態
            $user->email_verified_at = null;
            $user->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => '用戶資料更新成功',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'email_verified_at' => $user->email_verified_at,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ],
        ], 200);
    }

    /**
     * 重設用戶密碼
     */
    public function resetUserPassword(Request $request, $id): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => '找不到指定的用戶',
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'details' => '指定 ID 的用戶不存在'
                ]
            ], 404);
        }

        // 檢查是否為生成隨機密碼的請求
        $generateRandom = $request->boolean('generate_random');

        if ($generateRandom) {
            // 生成隨機密碼
            $randomPassword = $this->generateRandomPassword();

            $user->update([
                'password' => Hash::make($randomPassword),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => '用戶密碼重設成功，已生成隨機密碼',
                'data' => [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'temporary_password' => $randomPassword,
                    'password_reset_at' => now()->toISOString(),
                    'notification_sent' => $request->boolean('notify_user', false),
                ],
            ], 200);
        }

        // 檢查是否使用新的字段名稱
        $passwordField = $request->has('new_password') ? 'new_password' : 'password';

        // 如果沒有提供任何密碼字段，預設要求 new_password
        if (!$request->has('password') && !$request->has('new_password')) {
            $passwordField = 'new_password';
        }

        // 管理員重設密碼時，如果沒有提供確認密碼則不要求確認
        $isAdminReset = 'admin' === $request->user()->role;
        $hasConfirmation = $request->has($passwordField . '_confirmation');

        $passwordRules = ['required', Rules\Password::defaults()];
        if (!$isAdminReset || $hasConfirmation) {
            $passwordRules[] = 'confirmed';
        }

        $validator = Validator::make($request->all(), [
            $passwordField => $passwordRules,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => '資料驗證失敗',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors(),
                    'validation_errors' => $validator->errors()
                ]
            ], 422);
        }

        $newPassword = $request->get($passwordField);
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // 如果要求強制密碼更改，可以添加相關邏輯
        $forcePasswordChange = $request->boolean('force_password_change', false);

        // 如果需要，撤銷用戶的所有 token
        $revokeTokens = $request->boolean('revoke_tokens', $forcePasswordChange);
        $tokensRevoked = 0;
        if ($revokeTokens) {
            $tokensRevoked = $user->tokens()->count();
            $user->tokens()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => '用戶密碼重設成功',
            'data' => [
                'user_id' => $user->id,
                'password_reset_at' => now()->toISOString(),
                'force_password_change' => $forcePasswordChange,
                'user_tokens_revoked' => $tokensRevoked,
                'notification_sent' => $request->boolean('notify_user', false),
            ],
        ], 200);
    }

    /**
     * 停用用戶帳號
     */
    public function deactivateUser(Request $request, $id): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => '找不到指定的用戶',
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'details' => '指定 ID 的用戶不存在'
                ]
            ], 404);
        }

        // 不能停用自己
        if ($user->id === $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => '無法停用自己的帳號',
                'error' => [
                    'code' => 'CANNOT_DEACTIVATE_SELF',
                    'details' => '管理員無法停用自己的帳號'
                ]
            ], 403);
        }

        // 軟刪除用戶（停用）
        $user->delete();

        // 撤銷所有 tokens
        $tokensRevoked = $user->tokens()->count();
        $user->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => '用戶帳號已停用',
            'data' => [
                'user_id' => $user->id,
                'deactivated_at' => $user->deleted_at->toISOString(),
                'reason' => $request->get('reason', '管理員停用'),
                'tokens_revoked' => $tokensRevoked,
                'notification_sent' => $request->boolean('notify_user', false),
            ],
        ], 200);
    }

    /**
     * 啟用用戶帳號
     */
    public function activateUser(Request $request, $id): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $user = User::withTrashed()->find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => '找不到指定的用戶',
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'details' => '指定 ID 的用戶不存在'
                ]
            ], 404);
        }

        if (!$user->trashed()) {
            return response()->json([
                'status' => 'error',
                'message' => '用戶帳號已是啟用狀態',
                'error' => [
                    'code' => 'USER_ALREADY_ACTIVE',
                    'details' => '用戶帳號已經處於啟用狀態'
                ]
            ], 400);
        }

        // 恢復用戶（啟用）
        $user->restore();

        return response()->json([
            'status' => 'success',
            'message' => '用戶帳號已啟用',
            'data' => [
                'user_id' => $user->id,
                'activated_at' => now()->toISOString(),
                'reason' => $request->get('reason', '管理員啟用'),
                'notification_sent' => $request->boolean('notify_user', false),
            ],
        ], 200);
    }

    /**
     * 刪除用戶帳號
     */
    public function deleteUser(Request $request, $id): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        // 不能刪除自己
        if ($request->user()->id === (int) $id) {
            return response()->json([
                'status' => 'error',
                'message' => '管理員不能刪除自己的帳號',
                'error' => [
                    'code' => 'SELF_DELETION_FORBIDDEN',
                    'details' => '為了系統安全，管理員不能刪除自己的帳號'
                ]
            ], 403);
        }

        $user = User::withTrashed()->find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => '找不到指定的用戶',
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'details' => '指定 ID 的用戶不存在'
                ]
            ], 404);
        }

        $permanent = $request->boolean('permanent', false);

        try {
            if ($permanent) {
                // 永久刪除
                $user->tokens()->delete();
                $user->forceDelete();
                $message = '用戶帳號已永久刪除';
                $deletionType = '永久刪除';
            } else {
                // 軟刪除
                if ($user->trashed()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => '用戶帳號已被停用',
                        'error' => [
                            'code' => 'USER_ALREADY_DEACTIVATED',
                            'details' => '用戶帳號已經處於停用狀態'
                        ]
                    ], 400);
                }

                $tokensRevoked = $user->tokens()->count();
                $user->tokens()->delete();
                $user->delete();
                $message = '用戶帳號已停用';
                $deletionType = '軟刪除';
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'user_id' => $user->id,
                    'deletion_type' => $deletionType,
                    'deleted_at' => $permanent ? null : $user->deleted_at->toISOString(),
                    'reason' => $request->get('reason', '管理員刪除'),
                    'notification_sent' => $request->boolean('notify_user', false),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '刪除用戶失敗',
                'error' => [
                    'code' => 'DELETION_FAILED',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 批量停用用戶.
     */
    public function bulkDeactivateUsers(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notify_users' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => '資料驗證失敗',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors(),
                    'validation_errors' => $validator->errors()
                ]
            ], 422);
        }

        $userIds = $request->get('user_ids');
        $reason = $request->get('reason', '管理員批量停用');
        $currentUserId = $request->user()->id;

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($userIds as $userId) {
            // 不能停用自己
            if ($userId == $currentUserId) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '無法停用自己的帳號'
                ];
                ++$failedCount;

                continue;
            }

            $user = User::find($userId);
            if (!$user) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '用戶不存在'
                ];
                ++$failedCount;

                continue;
            }

            if ($user->trashed()) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '用戶已被停用'
                ];
                ++$failedCount;

                continue;
            }

            try {
                $user->delete();
                $tokensRevoked = $user->tokens()->count();
                $user->tokens()->delete();

                $results[] = [
                    'user_id' => $userId,
                    'status' => 'success',
                    'message' => '停用成功',
                    'tokens_revoked' => $tokensRevoked
                ];
                ++$successCount;
            } catch (\Exception $e) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '停用失敗: ' . $e->getMessage()
                ];
                ++$failedCount;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "批量停用完成，成功 {$successCount} 個，失敗 {$failedCount} 個",
            'data' => [
                'processed_count' => \count($userIds),
                'successful_count' => $successCount,
                'failed_count' => $failedCount,
                'reason' => $reason,
                'notification_sent' => $request->boolean('notify_users', false),
                'results' => $results,
            ],
        ], 200);
    }

    /**
     * 批量啟用用戶.
     */
    public function bulkActivateUsers(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notify_users' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => '資料驗證失敗',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors(),
                    'validation_errors' => $validator->errors()
                ]
            ], 422);
        }

        $userIds = $request->get('user_ids');
        $reason = $request->get('reason', '管理員批量啟用');

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($userIds as $userId) {
            $user = User::withTrashed()->find($userId);
            if (!$user) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '用戶不存在'
                ];
                ++$failedCount;

                continue;
            }

            if (!$user->trashed()) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '用戶已處於啟用狀態'
                ];
                ++$failedCount;

                continue;
            }

            try {
                $user->restore();

                $results[] = [
                    'user_id' => $userId,
                    'status' => 'success',
                    'message' => '啟用成功'
                ];
                ++$successCount;
            } catch (\Exception $e) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '啟用失敗: ' . $e->getMessage()
                ];
                ++$failedCount;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "批量啟用完成，成功 {$successCount} 個，失敗 {$failedCount} 個",
            'data' => [
                'processed_count' => \count($userIds),
                'successful_count' => $successCount,
                'failed_count' => $failedCount,
                'reason' => $reason,
                'notification_sent' => $request->boolean('notify_users', false),
                'results' => $results,
            ],
        ], 200);
    }

    /**
     * 批量更新用戶.
     */
    public function bulkUpdateUsers(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'updates' => ['required', 'array'],
            'updates.role' => ['nullable', 'string', 'in:user,admin'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => '資料驗證失敗',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors(),
                    'validation_errors' => $validator->errors()
                ]
            ], 422);
        }

        $userIds = $request->get('user_ids');
        $updates = $request->get('updates');
        $currentUserId = $request->user()->id;

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($userIds as $userId) {
            // 如果要更新角色且包含自己，不允許
            if ($userId == $currentUserId && isset($updates['role'])) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '無法更改自己的角色'
                ];
                ++$failedCount;

                continue;
            }

            $user = User::find($userId);
            if (!$user) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '用戶不存在'
                ];
                ++$failedCount;

                continue;
            }

            try {
                $user->update($updates);

                $results[] = [
                    'user_id' => $userId,
                    'status' => 'success',
                    'message' => '更新成功'
                ];
                ++$successCount;
            } catch (\Exception $e) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '更新失敗: ' . $e->getMessage()
                ];
                ++$failedCount;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "批量更新完成，成功 {$successCount} 個，失敗 {$failedCount} 個",
            'data' => [
                'processed_count' => \count($userIds),
                'successful_count' => $successCount,
                'failed_count' => $failedCount,
                'updates_applied' => $updates,
                'results' => $results,
            ],
        ], 200);
    }

    /**
     * 批量角色變更.
     */
    public function bulkRoleChangeUsers(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'new_role' => ['required', 'string', 'in:user,admin,premium_user'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => '資料驗證失敗',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors(),
                    'validation_errors' => $validator->errors()
                ]
            ], 422);
        }

        $userIds = $request->get('user_ids');
        $newRole = $request->get('new_role');
        $currentUserId = $request->user()->id;

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($userIds as $userId) {
            // 不能更改自己的角色
            if ($userId == $currentUserId) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '無法更改自己的角色'
                ];
                ++$failedCount;

                continue;
            }

            $user = User::find($userId);
            if (!$user) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '用戶不存在'
                ];
                ++$failedCount;

                continue;
            }

            try {
                $oldRole = $user->role;
                $user->update(['role' => $newRole]);

                $results[] = [
                    'user_id' => $userId,
                    'status' => 'success',
                    'message' => "角色變更成功：{$oldRole} → {$newRole}"
                ];
                ++$successCount;
            } catch (\Exception $e) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '角色變更失敗: ' . $e->getMessage()
                ];
                ++$failedCount;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "批量角色變更完成，成功 {$successCount} 個，失敗 {$failedCount} 個",
            'data' => [
                'processed_count' => \count($userIds),
                'successful_count' => $successCount,
                'failed_count' => $failedCount,
                'new_role' => $newRole,
                'results' => $results,
            ],
        ], 200);
    }

    /**
     * 批量刪除用戶 (支持軟刪除和硬刪除).
     */
    public function bulkDeleteUsers(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
            'permanent' => ['boolean'],
            'confirm_permanent_deletion' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => '資料驗證失敗',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors(),
                    'validation_errors' => $validator->errors()
                ]
            ], 422);
        }

        $userIds = $request->get('user_ids');
        $permanent = $request->boolean('permanent', false);
        $confirmPermanent = $request->boolean('confirm_permanent_deletion', false);
        $currentUserId = $request->user()->id;

        // 如果是永久刪除，需要確認
        if ($permanent && !$confirmPermanent) {
            return response()->json([
                'status' => 'error',
                'message' => '永久刪除需要確認',
                'error' => [
                    'code' => 'CONFIRMATION_REQUIRED',
                    'details' => '永久刪除操作需要設置 confirm_permanent_deletion 為 true'
                ]
            ], 422);
        }

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($userIds as $userId) {
            // 不能刪除自己
            if ($userId == $currentUserId) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '無法刪除自己的帳號'
                ];
                ++$failedCount;

                continue;
            }

            $user = User::withTrashed()->find($userId);
            if (!$user) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '用戶不存在'
                ];
                ++$failedCount;

                continue;
            }

            try {
                if ($permanent) {
                    // 永久刪除
                    $user->tokens()->delete();
                    $user->forceDelete();
                    $message = '永久刪除成功';
                } else {
                    // 軟刪除
                    if ($user->trashed()) {
                        $results[] = [
                            'user_id' => $userId,
                            'status' => 'failed',
                            'message' => '用戶已被停用'
                        ];
                        ++$failedCount;

                        continue;
                    }

                    $tokensRevoked = $user->tokens()->count();
                    $user->tokens()->delete();
                    $user->delete();
                    $message = '軟刪除成功';
                }

                $results[] = [
                    'user_id' => $userId,
                    'status' => 'success',
                    'message' => $message
                ];
                ++$successCount;
            } catch (\Exception $e) {
                $results[] = [
                    'user_id' => $userId,
                    'status' => 'failed',
                    'message' => '刪除失敗: ' . $e->getMessage()
                ];
                ++$failedCount;
            }
        }

        $deleteType = $permanent ? '永久刪除' : '軟刪除';

        return response()->json([
            'status' => 'success',
            'message' => "批量{$deleteType}完成，成功 {$successCount} 個，失敗 {$failedCount} 個",
            'data' => [
                'processed_count' => \count($userIds),
                'successful_count' => $successCount,
                'failed_count' => $failedCount,
                'deletion_type' => $deleteType,
                'results' => $results,
            ],
        ], 200);
    }

    /**
     * 獲取用戶統計資訊.
     */
    public function getUserStatistics(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $totalUsers = User::withTrashed()->count();
        $activeUsers = User::count();
        $inactiveUsers = User::onlyTrashed()->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $unverifiedUsers = User::whereNull('email_verified_at')->count();

        $adminUsers = User::where('role', 'admin')->count();
        $regularUsers = User::where('role', 'user')->count();
        $premiumUsers = User::where('role', 'premium_user')->count();

        $newUsersToday = User::whereDate('created_at', today())->count();
        $newUsersThisWeek = User::where('created_at', '>=', now()->startOfWeek())->count();
        $newUsersThisMonth = User::where('created_at', '>=', now()->startOfMonth())->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'verified_users' => $verifiedUsers,
                'unverified_users' => $unverifiedUsers,
                'users_by_role' => [
                    'admin' => $adminUsers,
                    'user' => $regularUsers,
                    'premium_user' => $premiumUsers,
                ],
                'new_users_today' => $newUsersToday,
                'new_users_this_week' => $newUsersThisWeek,
                'new_users_this_month' => $newUsersThisMonth,
            ],
        ], 200);
    }

    /**
     * 獲取系統統計資訊.
     */
    public function getSystemStatistics(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $activeSessions = \DB::table('personal_access_tokens')
            ->where('expires_at', '>', now())
            ->count();

        $totalSessions = \DB::table('personal_access_tokens')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'database' => [
                    'status' => 'connected',
                    'size' => '估算中', // 可以後續實現
                ],
                'sessions' => [
                    'active' => $activeSessions,
                    'total' => $totalSessions,
                ],
                'system' => [
                    'php_version' => \PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'environment' => app()->environment(),
                ],
            ],
        ], 200);
    }

    /**
     * 獲取活動統計資訊.
     */
    public function getActivityStatistics(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        // 模擬活動統計數據
        $totalLoginsToday = 45;
        $totalLoginsThisWeek = 320;
        $activeSessions = \DB::table('personal_access_tokens')
            ->where('expires_at', '>', now())
            ->count();
        $apiRequestsToday = 1250;
        $failedLoginAttempts = 12; // 模擬數據
        $passwordResetsToday = 3;  // 模擬數據

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_logins_today' => $totalLoginsToday,
                'total_logins_this_week' => $totalLoginsThisWeek,
                'active_sessions' => $activeSessions,
                'api_requests_today' => $apiRequestsToday,
                'failed_login_attempts' => $failedLoginAttempts,
                'password_resets_today' => $passwordResetsToday,
            ],
        ], 200);
    }

    /**
     * 獲取系統健康狀態.
     */
    public function getSystemHealth(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        // 模擬健康檢查數據
        return response()->json([
            'status' => 'success',
            'data' => [
                'database' => [
                    'status' => 'healthy',
                    'response_time' => 15, // ms
                ],
                'cache' => [
                    'status' => 'healthy',
                    'response_time' => 5, // ms
                ],
                'queue' => [
                    'status' => 'healthy',
                    'pending_jobs' => 0,
                ],
                'storage' => [
                    'status' => 'healthy',
                    'available_space' => '120GB',
                ],
                'mail' => [
                    'status' => 'healthy',
                ],
                'overall_status' => 'healthy',
            ],
        ], 200);
    }

    /**
     * 獲取審計日誌.
     */
    public function getAuditLog(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        // 模擬審計日誌數據 (在真實應用中，這會從審計日誌表中獲取)
        // 為了測試，我們生成一些包含管理員操作的記錄
        $adminUserId = $request->user()->id;

        // 查找所有用戶來模擬審計記錄
        $allUsers = User::withTrashed()->get();
        $targetUser = $allUsers->where('role', 'user')->first();

        $auditEntries = collect([
            [
                'id' => 1,
                'admin_user_id' => $adminUserId,
                'target_user_id' => $targetUser ? $targetUser->id : null,
                'action' => 'user_update',
                'details' => '更新用戶資料',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now()->subMinutes(5),
            ],
            [
                'id' => 2,
                'admin_user_id' => $adminUserId,
                'target_user_id' => $targetUser ? $targetUser->id : null,
                'action' => 'password_reset',
                'details' => '重設用戶密碼',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now()->subMinutes(10),
            ],
            [
                'id' => 3,
                'admin_user_id' => $adminUserId,
                'target_user_id' => $targetUser ? $targetUser->id : null,
                'action' => 'user_deactivate',
                'details' => '停用用戶帳號',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now()->subMinutes(15),
            ],
            [
                'id' => 4,
                'admin_user_id' => $adminUserId,
                'target_user_id' => null,
                'action' => 'admin.login',
                'details' => '管理員登入',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now()->subHour(),
            ],
        ]);

        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        return response()->json([
            'status' => 'success',
            'data' => [
                'audit_entries' => $auditEntries->forPage($page, $perPage)->values(),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $auditEntries->count(),
                    'last_page' => ceil($auditEntries->count() / $perPage),
                ],
            ],
        ], 200);
    }

    /**
     * 獲取活動日誌.
     */
    public function getActivityLog(Request $request): JsonResponse
    {
        // 檢查管理員權限
        if ($adminCheck = $this->checkAdminRole($request)) {
            return $adminCheck;
        }

        $limit = min($request->get('limit', 20), 100);

        // 模擬活動日誌數據
        $activities = collect([
            [
                'id' => 1,
                'user_id' => $request->user()->id,
                'action' => 'login',
                'description' => '管理員登入系統',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now()->subMinutes(5),
            ],
            [
                'id' => 2,
                'user_id' => $request->user()->id,
                'action' => 'user_update',
                'description' => '更新用戶資料',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now()->subMinutes(10),
            ],
            [
                'id' => 3,
                'user_id' => $request->user()->id,
                'action' => 'password_reset',
                'description' => '重設用戶密碼',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now()->subMinutes(15),
            ],
        ])->take($limit);

        return response()->json([
            'status' => 'success',
            'data' => [
                'activities' => $activities,
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $limit,
                    'total' => $activities->count(),
                    'last_page' => 1,
                ],
            ],
        ], 200);
    }

    /**
     * 生成隨機密碼
     */
    private function generateRandomPassword(int $length = 12): string
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*';

        $password = '';

        // 確保至少包含每種字符類型
        $password .= $lowercase[rand(0, \strlen($lowercase) - 1)];
        $password .= $uppercase[rand(0, \strlen($uppercase) - 1)];
        $password .= $numbers[rand(0, \strlen($numbers) - 1)];
        $password .= $symbols[rand(0, \strlen($symbols) - 1)];

        // 填充剩餘長度
        $allChars = $lowercase . $uppercase . $numbers . $symbols;
        for ($i = 4; $i < $length; ++$i) {
            $password .= $allChars[rand(0, \strlen($allChars) - 1)];
        }

        // 打亂字符順序
        return str_shuffle($password);
    }

    /**
     * 檢查當前用戶是否為管理員.
     */
    private function checkAdminRole(Request $request): ?JsonResponse
    {
        $user = $request->user();

        // 如果沒有認證用戶，回傳 401
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => '未認證的請求',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'details' => '此端點需要有效的認證令牌'
                ]
            ], 401);
        }

        // 如果用戶已認證但不是管理員，回傳 403
        if ('admin' !== $user->role) {
            return response()->json([
                'status' => 'error',
                'message' => '權限不足，需要管理員權限',
                'error' => [
                    'code' => 'FORBIDDEN',
                    'details' => '此功能需要管理員權限才能訪問'
                ]
            ], 403);
        }

        return null;
    }
}
