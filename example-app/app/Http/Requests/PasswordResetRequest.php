<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\ApiResponseFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

/**
 * 密碼重設請求驗證.
 */
class PasswordResetRequest extends FormRequest
{
    use ApiResponseFormat;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 密碼重設請求不需要預先認證
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'token.required' => '重設令牌為必填項目',
            'email.required' => 'Email 為必填項目',
            'email.email' => '請提供有效的 Email 地址',
            'password.required' => '密碼為必填項目',
            'password.confirmed' => '密碼確認不匹配',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'token' => '重設令牌',
            'email' => '電子郵件',
            'password' => '密碼',
        ];
    }
}
