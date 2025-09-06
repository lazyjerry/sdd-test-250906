<?php

namespace App\Http\Requests\Traits;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

/**
 * 用戶驗證規則 Trait.
 *
 * 統一管理所有用戶相關的驗證規則
 */
trait UserValidationRules
{
    /**
     * 獲取姓名驗證規則.
     */
    protected function getNameRules(bool $required = false): array
    {
        $rules = ['string', 'max:255'];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * 獲取用戶名驗證規則.
     */
    protected function getUsernameRules(?int $ignoreUserId = null): array
    {
        $rules = [
            'required',
            'string',
            'max:255',
            'regex:/^[a-zA-Z0-9_.-]+$/' // 只允許字母、數字、下劃線、點和短橫線
        ];

        // 添加唯一性驗證
        if ($ignoreUserId) {
            $rules[] = Rule::unique('users', 'username')->ignore($ignoreUserId);
        } else {
            $rules[] = 'unique:users,username';
        }

        return $rules;
    }

    /**
     * 獲取電子郵件驗證規則.
     */
    protected function getEmailRules(bool $required = true, ?int $ignoreUserId = null): array
    {
        $rules = ['email', 'max:255'];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        // 添加唯一性驗證
        if ($ignoreUserId) {
            $rules[] = Rule::unique('users', 'email')->ignore($ignoreUserId);
        } else {
            $rules[] = 'unique:users,email';
        }

        return $rules;
    }

    /**
     * 獲取手機號碼驗證規則.
     */
    protected function getPhoneRules(): array
    {
        return ['nullable', 'string', 'max:20'];
    }

    /**
     * 獲取密碼驗證規則.
     */
    protected function getPasswordRules(bool $confirmed = false, bool $useStrongRules = false): array
    {
        if ($useStrongRules) {
            // 使用強密碼規則（舊的 regex 方式）
            $rules = [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/' // 至少包含大小寫字母和數字
            ];
        } else {
            // 使用 Laravel 預設密碼規則
            $rules = ['required'];

            if ($confirmed) {
                $rules[] = 'confirmed';
            }

            $rules[] = Rules\Password::defaults();
        }

        return $rules;
    }

    /**
     * 獲取角色驗證規則.
     */
    protected function getRoleRules(array $allowedRoles = ['user', 'admin']): array
    {
        return [
            'required',
            'string',
            Rule::in($allowedRoles)
        ];
    }

    /**
     * 獲取預設的用戶角色選項.
     */
    protected function getDefaultUserRoles(): array
    {
        return ['user', 'admin'];
    }

    /**
     * 獲取管理員角色選項.
     */
    protected function getAdminRoles(): array
    {
        return ['admin', 'super_admin', 'user'];
    }

    /**
     * 獲取用戶註冊的驗證規則.
     */
    protected function getUserRegistrationRules(): array
    {
        return [
            'name' => $this->getNameRules(false),
            'username' => $this->getUsernameRules(),
            'email' => $this->getEmailRules(true),
            'phone' => $this->getPhoneRules(),
            'password' => $this->getPasswordRules(true, false),
        ];
    }

    /**
     * 獲取管理員創建用戶的驗證規則.
     */
    protected function getAdminCreateUserRules(): array
    {
        return [
            'name' => $this->getNameRules(true),
            'username' => $this->getUsernameRules(),
            'email' => $this->getEmailRules(false), // 管理員創建時 email 可選
            'phone' => $this->getPhoneRules(),
            'password' => $this->getPasswordRules(false, true), // 使用強密碼規則，不需要確認
            'role' => $this->getRoleRules($this->getAdminRoles()),
        ];
    }

    /**
     * 獲取管理員註冊其他用戶的驗證規則.
     */
    protected function getAdminRegisterUserRules(): array
    {
        return [
            'name' => $this->getNameRules(false),
            'username' => $this->getUsernameRules(),
            'email' => $this->getEmailRules(true),
            'phone' => $this->getPhoneRules(),
            'password' => $this->getPasswordRules(true, false),
            'role' => $this->getRoleRules($this->getDefaultUserRoles()),
        ];
    }

    /**
     * 獲取用戶更新的驗證規則.
     */
    protected function getUserUpdateRules(int $userId): array
    {
        return [
            'name' => ['sometimes'] + $this->getNameRules(false),
            'username' => ['sometimes'] + $this->getUsernameRules($userId),
            'email' => ['sometimes'] + $this->getEmailRules(true, $userId),
            'phone' => ['sometimes'] + $this->getPhoneRules(),
            'role' => ['sometimes'] + $this->getRoleRules($this->getDefaultUserRoles()),
            'email_verified_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * 獲取管理員登入的驗證規則.
     */
    protected function getAdminLoginRules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50'
            ],
            'password' => [
                'required',
                'string',
                'min:1' // 允許任何長度的密碼進行登入驗證
            ],
            'remember' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    /**
     * 獲取一般用戶登入的驗證規則.
     */
    protected function getUserLoginRules(): array
    {
        return [
            'username' => [
                'required',
                'string'
            ],
            'password' => [
                'required',
                'string'
            ]
        ];
    }

    /**
     * 獲取通用的驗證錯誤訊息.
     */
    protected function getCommonValidationMessages(): array
    {
        return [
            'name.required' => '姓名為必填項目',
            'name.string' => '姓名必須是字符串',
            'name.max' => '姓名不能超過 255 個字符',

            'username.required' => '請輸入用戶名',
            'username.string' => '用戶名必須是字符串',
            'username.max' => '用戶名不能超過 255 個字符',
            'username.min' => '用戶名至少需要 3 個字符',
            'username.unique' => '用戶名已存在',
            'username.regex' => '用戶名只能包含字母、數字、下劃線、點和短橫線',

            'email.required' => 'Email 為必填項目',
            'email.email' => '請提供有效的 Email 地址',
            'email.max' => 'Email 不能超過 255 個字符',
            'email.unique' => 'Email 已存在',

            'phone.string' => '手機號碼必須是字符串',
            'phone.max' => '手機號碼不能超過 20 個字符',

            'password.required' => '請輸入密碼',
            'password.string' => '密碼必須是字符串',
            'password.min' => '密碼至少需要 8 個字符',
            'password.confirmed' => '密碼確認不匹配',
            'password.regex' => '密碼必須包含至少一個大寫字母、一個小寫字母和一個數字',

            'remember.boolean' => '記住我選項必須是布爾值',

            'role.required' => '角色為必填項目',
            'role.string' => '角色必須是字符串',
            'role.in' => '指定的角色無效',

            'email_verified_at.date' => 'Email 驗證時間必須是有效的日期',
        ];
    }

    /**
     * 獲取英文版驗證錯誤訊息.
     */
    protected function getEnglishValidationMessages(): array
    {
        return [
            'name.required' => 'Name is required',
            'name.string' => 'Name must be a string',
            'name.max' => 'Name may not be greater than 255 characters',

            'username.required' => 'Username is required',
            'username.string' => 'Username must be a string',
            'username.max' => 'Username may not be greater than 255 characters',
            'username.unique' => 'Username already exists',
            'username.regex' => 'Username can only contain letters, numbers, underscores, dots and hyphens',

            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
            'email.max' => 'Email may not be greater than 255 characters',
            'email.unique' => 'Email already exists',

            'phone.string' => 'Phone must be a string',
            'phone.max' => 'Phone may not be greater than 20 characters',

            'password.required' => 'Password is required',
            'password.string' => 'Password must be a string',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number',

            'role.required' => 'Role is required',
            'role.string' => 'Role must be a string',
            'role.in' => 'Invalid role specified',

            'email_verified_at.date' => 'Email verification time must be a valid date',
        ];
    }
}
