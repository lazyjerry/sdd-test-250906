<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\ApiResponseFormat;
use App\Http\Requests\Traits\UserValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 管理員登入請求驗證.
 *
 * 驗證管理員登入時的輸入數據
 * 管理員使用用戶名登入，不需要 email
 */
class AdminLoginRequest extends FormRequest
{
    use ApiResponseFormat;
    use UserValidationRules;

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
        return $this->getAdminLoginRules();
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
        return $this->getCommonValidationMessages();
    }

    /**
     * 獲取已驗證的數據，包含預處理.
     *
     * @param null|string $key
     * @param null|mixed  $default
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
