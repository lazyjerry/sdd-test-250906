<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\ApiResponseFormat;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Email 驗證請求驗證.
 */
class EmailVerificationRequest extends FormRequest
{
    use ApiResponseFormat;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Email 驗證請求不需要預先認證
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'hash' => ['required', 'string'],
            'expires' => ['required', 'integer'],
            'signature' => ['required', 'string'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'id.required' => '用戶 ID 為必填項目',
            'id.integer' => '用戶 ID 必須是整數',
            'hash.required' => '驗證雜湊為必填項目',
            'hash.string' => '驗證雜湊必須是字符串',
            'expires.required' => '過期時間為必填項目',
            'expires.integer' => '過期時間必須是整數',
            'signature.required' => '簽名為必填項目',
            'signature.string' => '簽名必須是字符串',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'id' => '用戶 ID',
            'hash' => '驗證雜湊',
            'expires' => '過期時間',
            'signature' => '簽名',
        ];
    }
}
