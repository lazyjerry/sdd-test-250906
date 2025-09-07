<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Web 端郵件驗證控制器.
 *
 * 處理透過 Web 連結的郵件驗證功能
 */
class EmailVerificationController extends Controller
{
    /**
     * 透過連結驗證郵箱 (GET 路由專用).
     *
     * 此方法專門處理用戶點擊郵件中的驗證連結
     * 提供友好的 Web 介面回應
     *
     * @param string $id   用戶 ID
     * @param string $hash 郵件哈希值
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function verifyByLink(Request $request, $id, $hash)
    {
        // 手動驗證參數
        $validator = Validator::make([
            'id' => $id,
            'hash' => $hash,
            'expires' => $request->query('expires'),
            'signature' => $request->query('signature'),
        ], [
            'id' => ['required', 'integer'],
            'hash' => ['required', 'string'],
            'expires' => ['required', 'integer'],
            'signature' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->showVerificationResult([
                'success' => false,
                'message' => '驗證連結格式錯誤',
                'error_code' => 'INVALID_LINK_FORMAT',
                'errors' => $validator->errors()
            ]);
        }

        // 使用共用服務處理郵件驗證
        try {
            $emailVerificationService = new EmailVerificationService();

            $result = $emailVerificationService->verifyEmail([
                'id' => $id,
                'hash' => $hash,
                'expires' => $request->query('expires'),
                'signature' => $request->query('signature'),
            ]);

            return $this->showVerificationResult($result);
        } catch (\Exception $e) {
            return $this->showVerificationResult([
                'success' => false,
                'message' => '系統錯誤，請稍後再試',
                'error_code' => 'SYSTEM_ERROR',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 顯示驗證結果頁面.
     *
     * @param array $result 驗證結果
     *
     * @return \Illuminate\View\View
     */
    private function showVerificationResult(array $result)
    {
        // 如果請求期望 JSON 回應（AJAX 請求）
        if (request()->expectsJson()) {
            $statusCode = $result['success'] ? 200 : 400;

            return response()->json($result, $statusCode);
        }

        // 否則顯示 Web 頁面
        return view('auth.email-verification-result', [
            'success' => $result['success'],
            'message' => $result['message'],
            'user' => $result['user'] ?? null,
            'error_code' => $result['error_code'] ?? null,
        ]);
    }
}
