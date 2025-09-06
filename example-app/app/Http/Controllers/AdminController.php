<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAdminUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Admin Controller.
 *
 * 處理管理員相關的操作
 * 使用統一的 User model，通過 role 欄位區分管理員
 */
class AdminController extends Controller
{
    /**
     * 創建新的管理員用戶.
     */
    public function createUser(CreateAdminUserRequest $request): JsonResponse
    {
        try {
            // 創建新用戶
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'permissions' => $this->getDefaultPermissions($request->role),
                'email_verified_at' => 'admin' === $request->role ? now() : null, // 管理員自動驗證
            ]);

            return response()->json([
                'message' => 'User created successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 根據角色獲取預設權限.
     */
    private function getDefaultPermissions(string $role): array
    {
        return match ($role) {
            'admin' => [
                'manage_users',
                'create_users',
                'view_admin_panel',
                'manage_roles',
            ],
            'super_admin' => [
                'manage_users',
                'create_users',
                'delete_users',
                'manage_system',
                'view_admin_panel',
                'manage_roles',
                'manage_permissions',
                'system_maintenance',
            ],
            default => []
        };
    }
}
