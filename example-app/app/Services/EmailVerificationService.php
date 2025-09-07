<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Verified;

/**
 * 郵件驗證服務類.
 *
 * 提供統一的郵件驗證邏輯，供 API 和 Web 控制器共用
 */
class EmailVerificationService
{
    /**
     * 執行郵件驗證.
     *
     * @param array $credentials 包含 id, hash, expires, signature
     *
     * @return array 返回結果陣列，包含 success, message, user, error_code
     */
    public function verifyEmail(array $credentials): array
    {
        try {
            $user = User::findOrFail($credentials['id']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => '找不到指定的使用者',
                'error_code' => 'USER_NOT_FOUND',
                'user' => null,
            ];
        }

        try {
            // 先檢查用戶是否已驗證，如果已驗證則直接返回成功
            if ($user->hasVerifiedEmail()) {
                return [
                    'success' => true,
                    'message' => '電子郵件已經驗證過了',
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                    ],
                    'error_code' => null,
                ];
            }

            // 生成期望的簽名用於比較
            $expectedUrl = url()->temporarySignedRoute(
                'verification.verify',
                now()->setTimestamp($credentials['expires']),
                ['id' => $credentials['id'], 'hash' => $credentials['hash']]
            );
            $expectedUrlParts = parse_url($expectedUrl);
            parse_str($expectedUrlParts['query'], $expectedParams);
            $expectedSignature = $expectedParams['signature'] ?? '';

            // 檢查簽名
            if (!hash_equals($credentials['signature'], $expectedSignature)) {
                return [
                    'success' => false,
                    'message' => '無效或過期的驗證連結',
                    'error_code' => 'INVALID_VERIFICATION_LINK',
                    'user' => null,
                ];
            }

            // 檢查是否過期
            if (now()->timestamp > $credentials['expires']) {
                return [
                    'success' => false,
                    'message' => '無效或過期的驗證連結',
                    'error_code' => 'INVALID_VERIFICATION_LINK',
                    'user' => null,
                ];
            }

            // 驗證hash
            if (!hash_equals((string) $credentials['hash'], sha1($user->getEmailForVerification()))) {
                return [
                    'success' => false,
                    'message' => '無效或過期的驗證連結',
                    'error_code' => 'INVALID_VERIFICATION_LINK',
                    'user' => null,
                ];
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            return [
                'success' => true,
                'message' => '電子郵件驗證成功',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'error_code' => null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '無效或過期的驗證連結',
                'error_code' => 'INVALID_VERIFICATION_LINK',
                'user' => null,
            ];
        }
    }

    /**
     * 為 API 端點格式化回應.
     *
     * @param array $result 來自 verifyEmail 的結果
     *
     * @return array API 格式的回應陣列
     */
    public function formatApiResponse(array $result): array
    {
        if ($result['success']) {
            return [
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'user' => $result['user'],
                ],
            ];
        }

        return [
            'status' => 'error',
            'message' => $result['message'],
            'error_code' => $result['error_code'],
        ];
    }

    /**
     * 為 Web 端點格式化回應.
     *
     * @param array $result 來自 verifyEmail 的結果
     *
     * @return array Web 格式的回應陣列
     */
    public function formatWebResponse(array $result): array
    {
        return [
            'success' => $result['success'],
            'message' => $result['message'],
            'user' => $result['user'],
            'error_code' => $result['error_code'],
        ];
    }
}
