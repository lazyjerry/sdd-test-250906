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

        // 支援查看軟刪除的用戶
        $withTrashed = $request->boolean('with_trashed') || $request->boolean('include_deleted');
        $query = $withTrashed ? User::withTrashed() : User::query();

        $user = $query->find($id);

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
                    'last_login_at' => $user->last_login_at ?? null,
                    'api_tokens_count' => $user->tokens()->count(),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'deleted_at' => $user->deleted_at,
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
        $passwordConfirmationField = $passwordField . '_confirmation';

        // 如果沒有提供任何密碼字段，預設要求 new_password
        if (!$request->has('password') && !$request->has('new_password')) {
            $passwordField = 'new_password';
        }

        $validator = Validator::make($request->all(), [
            $passwordField => ['required', 'confirmed', Rules\Password::defaults()],
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

        return response()->json([
            'status' => 'success',
            'message' => '用戶密碼重設成功',
            'data' => [
                'user_id' => $user->id,
                'password_reset_at' => now()->toISOString(),
                'notification_sent' => $request->boolean('notify_user', false),
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

        if (!$user || 'admin' !== $user->role) {
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
