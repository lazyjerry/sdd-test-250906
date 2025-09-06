<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\ApiResponseFormat;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 忘記密碼請求驗證.
 */
class ForgotPasswordRequest extends FormRequest
{
    use ApiResponseFormat;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 忘記密碼請求不需要預先認證
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email 為必填項目',
            'email.email' => '請提供有效的 Email 地址',
            'email.max' => 'Email 不能超過 255 個字符',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => '電子郵件',
        ];
    }
}
