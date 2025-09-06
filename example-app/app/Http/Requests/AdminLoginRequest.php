<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 管理員登入請求驗證.
 *
 * 驗證管理員登入時的輸入數據
 * 管理員使用用戶名登入，不需要 email
 */
class AdminLoginRequest extends FormRequest
{
    /**
     * 確定用戶是否有權限發出此請求
     */
    public function authorize(): bool
    {
        // 登入請求不需要預先認證
        return true;
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
                'max:50'
            ],
            'password' => [
                'required',
                'string',
                'min:1' // 允許任何長度的密碼進行驗證
            ],
            'remember' => [
                'nullable',
                'boolean'
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
            'remember' => '記住我',
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
            'username.required' => '請輸入用戶名',
            'username.string' => '用戶名必須是字符串',
            'username.min' => '用戶名至少需要 3 個字符',
            'username.max' => '用戶名不能超過 50 個字符',
            'password.required' => '請輸入密碼',
            'password.string' => '密碼必須是字符串',
            'remember.boolean' => '記住我選項必須是布爾值',
        ];
    }

    /**
     * 獲取已驗證的數據，包含預處理.
     *
     * @param null|string $key
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // 確保 remember 字段有默認值
        if (!isset($validated['remember'])) {
            $validated['remember'] = false;
        }

        return $validated;
    }

    /**
     * 在驗證失敗時自定義錯誤響應.
     *
     * @throws \Illuminate\Validation\ValidationException
     *
     * @return void
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json(['message' => '輸入驗證失敗', 'errors' => $validator->errors()], 422));
    }

    /**
     * 準備驗證數據.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // 清理用戶名（去除空白字符）
        if ($this->has('username')) {
            $this->merge([
                'username' => trim($this->username)
            ]);
        }
    }
}
