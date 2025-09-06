<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\ApiResponseFormat;
use App\Http\Requests\Traits\UserValidationRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 用戶更新請求驗證.
 */
class UserUpdateRequest extends FormRequest
{
    use ApiResponseFormat;
    use UserValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 檢查用戶是否為管理員
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('id'); // 從路由參數獲取用戶ID

        return $this->getUserUpdateRules($userId);
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
            'role' => '角色',
            'email_verified_at' => 'Email 驗證時間',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        $this->failedAuthorizationForAdmin();
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $this->failedValidationWithDetails($validator);
    }
}
