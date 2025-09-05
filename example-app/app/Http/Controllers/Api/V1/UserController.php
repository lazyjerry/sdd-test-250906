<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * 獲取用戶個人資料.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        // 檢查用戶是否被軟刪除
        if ($user->trashed()) {
            return response()->json([
                'status' => 'error',
                'message' => '使用者帳戶已被停用',
                'error' => [
                    'code' => 'USER_DEACTIVATED',
                    'details' => '該使用者帳戶已被系統管理員停用'
                ]
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'message' => '個人資料取得成功',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ],
        ], 200);
    }

    /**
     * 更新用戶個人資料.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        // 檢查用戶是否被軟刪除
        if ($user->trashed()) {
            return response()->json([
                'status' => 'error',
                'message' => '使用者帳戶已被停用',
                'error' => [
                    'code' => 'USER_DEACTIVATED',
                    'details' => '該使用者帳戶已被系統管理員停用'
                ]
            ], 401);
        }

        // 檢查是否有任何可更新的字段
        $hasUpdateableFields = $request->hasAny(['name', 'email', 'phone']);
        if (!$hasUpdateableFields) {
            return response()->json([
                'status' => 'error',
                'message' => '未提供任何可更新的資料',
                'error' => [
                    'code' => 'NO_UPDATE_DATA',
                    'details' => '請提供至少一個要更新的字段 (name, email, phone)',
                    'validation_errors' => [
                        'request' => ['請提供至少一個要更新的字段']
                    ]
                ]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => '資料驗證失敗',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors()->first(),
                    'validation_errors' => $validator->errors()
                ]
            ], 422);
        }

        $emailChanged = false;
        $oldEmail = $user->email;

        // 更新用戶資料
        $updateData = $request->only(['name', 'email', 'phone']);
        $user->update($updateData);

        // 檢查是否更改了 email
        if (isset($updateData['email']) && $updateData['email'] !== $oldEmail) {
            $emailChanged = true;
            // 重置 email 驗證狀態
            $user->email_verified_at = null;
            $user->save();
        }

        $message = '個人資料更新成功';
        if ($emailChanged) {
            $message .= '，請檢查新信箱以完成驗證';
        }

        $responseData = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ];

        // 如果電子郵件變更，添加驗證相關信息
        if ($emailChanged) {
            $responseData['email_verification_sent'] = true;
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $responseData,
        ], 200);
    }

    /**
     * 更改密碼
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // 檢查用戶是否被軟刪除
        if ($user->trashed()) {
            return response()->json([
                'status' => 'error',
                'message' => '使用者帳戶已被停用',
                'error' => [
                    'code' => 'USER_DEACTIVATED',
                    'details' => '該使用者帳戶已被系統管理員停用'
                ]
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
            'new_password_confirmation' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => '資料驗證失敗',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors()->first(),
                    'validation_errors' => $validator->errors()
                ]
            ], 422);
        }

        // 驗證當前密碼
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => '當前密碼不正確',
                'error' => [
                    'code' => 'CURRENT_PASSWORD_INCORRECT',
                    'details' => '請確認您輸入的當前密碼是否正確'
                ]
            ], 400);
        }

        // 檢查新密碼是否與當前密碼相同
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => '新密碼不能與當前密碼相同',
                'error' => [
                    'code' => 'SAME_PASSWORD',
                    'details' => '請選擇一個不同於當前密碼的新密碼'
                ]
            ], 400);
        }

        // 更新密碼
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => '密碼修改成功',
            'data' => [
                'password_changed_at' => now()->toISOString(),
            ]
        ], 200);
    }
}
