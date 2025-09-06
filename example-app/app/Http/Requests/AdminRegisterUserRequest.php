<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\ApiResponseFormat;
use App\Http\Requests\Traits\UserValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 管理員註冊其他用戶請求驗證.
 */
class AdminRegisterUserRequest extends FormRequest
{
    use ApiResponseFormat;
    use UserValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 檢查當前用戶是否為管理員
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return $this->getAdminRegisterUserRules();
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return $this->getCommonValidationMessages();
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => '姓名',
            'username' => '用戶名',
            'email' => '電子郵件',
            'phone' => '手機號碼',
            'password' => '密碼',
            'role' => '角色',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        $this->failedAuthorizationForInsufficientPrivileges();
    }
}
