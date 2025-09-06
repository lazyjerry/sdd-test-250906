<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\ApiResponseFormat;
use App\Http\Requests\Traits\UserValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 用戶註冊請求驗證.
 */
class UserRegistrationRequest extends FormRequest
{
    use ApiResponseFormat;
    use UserValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 註冊請求不需要預先認證
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return $this->getUserRegistrationRules();
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
        ];
    }
}
