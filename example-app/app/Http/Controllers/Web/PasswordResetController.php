<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Web 端密碼重設控制器.
 *
 * 提供簡易的 Bootstrap 5 網頁介面來處理密碼重設功能
 */
class PasswordResetController extends Controller
{
    /**
     * 顯示密碼重設表單.
     *
     * @param string $token
     *
     * @return \Illuminate\View\View
     */
    public function showResetForm(Request $request, $token)
    {
        $email = $request->query('email');

        return view('auth.password-reset', [
            'token' => $token,
            'email' => $email
        ]);
    }

    /**
     * 處理密碼重設請求 (AJAX 端點).
     *
     * 前端使用 JavaScript 直接調用此方法，返回 JSON 響應
     * 使用共用的 PasswordResetService 處理密碼重設邏輯
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        // 驗證表單資料
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'
        ], [
            'password.regex' => '密碼必須包含至少一個大寫字母、一個小寫字母、一個數字和一個特殊字符',
            'password.confirmed' => '密碼確認不匹配',
            'password.min' => '密碼至少需要8個字符'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '表單驗證失敗',
                'errors' => $validator->errors()
            ], 422);
        }

        // 使用共用服務處理密碼重設
        try {
            $passwordResetService = new PasswordResetService();

            $result = $passwordResetService->resetPassword(
                $request->only('email', 'password', 'password_confirmation', 'token')
            );

            $response = $passwordResetService->formatWebResponse(
                $result,
                route('password.reset.success')
            );

            $statusCode = $result['success'] ? 200 : 400;

            return response()->json($response, $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '系統錯誤，請稍後再試',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 顯示密碼重設成功頁面.
     *
     * @return \Illuminate\View\View
     */
    public function success()
    {
        return view('auth.password-reset-success');
    }
}
