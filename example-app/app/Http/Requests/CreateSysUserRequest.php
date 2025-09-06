<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * 創建系統管理員用戶請求驗證.
 *
 * 驗證管理員創建新管理員用戶時的輸入數據
 */
class CreateSysUserRequest extends FormRequest
{
    /**
     * 確定用戶是否有權限發出此請求
     */
    public function authorize(): bool
    {
        // 檢查當前用戶是否為已認證的管理員且具有創建管理員權限
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // 檢查是否為管理員且有創建管理員權限
        if ($user instanceof User && $user->isAdmin()) {
            $hasPermission = $user->hasPermission('create_admins');
            if (!$hasPermission) {
                throw new \Illuminate\Auth\Access\AuthorizationException('權限不足：無法創建管理員用戶');
            }

            return $hasPermission;
        }

        return false;
    }

    /**
     * 獲取適用於請求的驗證規則.
     *
     * @return array<string, array<mixed>|\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                'unique:users,username'
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100'
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'permissions' => [
                'nullable',
                'array'
            ],
            'permissions.*' => [
                'string',
                'in:' . implode(',', $this->getAllowedPermissions())
            ]
        ];
    }

    /**
     * 獲取驗證錯誤的自定義屬性名稱.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'username' => '用戶名',
            'password' => '密碼',
            'password_confirmation' => '確認密碼',
            'name' => '顯示名稱',
            'email' => '電子郵件',
            'permissions' => '權限',
        ];
    }

    /**
     * 獲取驗證錯誤的自定義消息.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => '用戶名是必填項',
            'username.unique' => '用戶名已存在',
            'username.min' => '用戶名至少需要 3 個字符',
            'username.alpha_dash' => '用戶名只能包含字母、數字、破折號和下劃線',
            'password.required' => '密碼是必填項',
            'password.confirmed' => '密碼確認不匹配',
            'password.min' => '密碼至少需要 8 個字符',
            'name.required' => '顯示名稱是必填項',
            'name.min' => '顯示名稱至少需要 2 個字符',
            'email.email' => '請輸入有效的電子郵件地址',
            'email.unique' => '此電子郵件已被使用',
            'permissions.array' => '權限必須是數組格式',
            'permissions.*.in' => '包含無效的權限'
        ];
    }

    /**
     * 在驗證通過後處理數據.
     *
     * @param null|mixed $key
     * @param null|mixed $default
     *
     * @return array
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // 如果沒有提供權限，給予基本權限
        if (!isset($validated['permissions']) || empty($validated['permissions'])) {
            $validated['permissions'] = ['manage_users'];
        }

        // 確保創建者不能賦予超過自己權限的權限
        $currentUser = $this->user();

        if ($currentUser instanceof User && $currentUser->isAdmin()) {
            // 只保留創建者擁有的權限
            $allowedPermissions = array_intersect(
                $validated['permissions'],
                $currentUser->permissions ?? []
            );
            // 如果沒有有效權限，至少給予基本權限（如果創建者有的話）
            if (empty($allowedPermissions) && \in_array('manage_users', $currentUser->permissions ?? [])) {
                $allowedPermissions = ['manage_users'];
            }
            $validated['permissions'] = array_values($allowedPermissions);
        }

        return $validated;
    }

    /**
     * 獲取允許的權限列表.
     */
    protected function getAllowedPermissions(): array
    {
        return [
            'manage_users',
            'manage_system',
            'create_admins',
            'view_all_data',
            'view_reports',
            'manage_settings',
            'system_maintenance',
            'user_management',
            'audit_logs'
        ];
    }
}
