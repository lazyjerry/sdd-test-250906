<?php

namespace App\Http\Requests\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * API 響應格式化 Trait.
 *
 * 統一 API 請求驗證失敗時的響應格式
 */
trait ApiResponseFormat
{
    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['status' => 'error', 'message' => '資料驗證失敗', 'errors' => $validator->errors()], 422));
    }

    /**
     * Handle a failed authorization attempt for admin resources.
     */
    protected function failedAuthorizationForAdmin()
    {
        throw new HttpResponseException(response()->json(['status' => 'error', 'message' => '權限不足，需要管理員權限', 'error' => ['code' => 'FORBIDDEN', 'details' => '此功能需要管理員權限才能訪問']], 403));
    }

    /**
     * Handle a failed authorization attempt for insufficient privileges.
     */
    protected function failedAuthorizationForInsufficientPrivileges()
    {
        throw new HttpResponseException(response()->json(['status' => 'error', 'message' => '沒有權限執行此操作', 'error_code' => 'INSUFFICIENT_PRIVILEGES'], 403));
    }

    /**
     * Handle a failed validation attempt with detailed error structure.
     */
    protected function failedValidationWithDetails(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['status' => 'error', 'message' => '資料驗證失敗', 'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors(), 'validation_errors' => $validator->errors()]], 422));
    }
}
