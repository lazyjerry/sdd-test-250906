# Web 密碼重設架構重構 - 移除 HTTP 請求

## 改動概要

將 `PasswordResetController` 中的 HTTP 請求改為直接程式碼調用，提升效能並減少不必要的網路往返。

## 修改內容

### 主要檔案

- `app/Http/Controllers/Web/PasswordResetController.php`

### 程式碼變更

#### 前（使用 HTTP 請求）

```php
// 調用 API 執行密碼重設
$response = Http::post(url('/api/v1/auth/reset-password'), [
    'token' => $request->token,
    'email' => $request->email,
    'password' => $request->password,
    'password_confirmation' => $request->password_confirmation
]);

if ($response->successful()) {
    $data = $response->json();
    return response()->json([
        'success' => true,
        'message' => $data['message'] ?? '密碼重設成功！',
        'redirect_url' => route('password.reset.success')
    ]);
}
```

#### 後（直接程式碼調用）

```php
// 直接使用 Laravel Password facade 進行密碼重設
$status = Password::reset(
    $request->only('email', 'password', 'password_confirmation', 'token'),
    function ($user, $password) {
        $user->forceFill([
            'password' => Hash::make($password)
        ])->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));
    }
);

if (Password::PASSWORD_RESET === $status) {
    return response()->json([
        'success' => true,
        'message' => '密碼重設成功！',
        'redirect_url' => route('password.reset.success')
    ]);
}
```

### 新增 Import

```php
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
```

### 移除 Import

```php
use Illuminate\Support\Facades\Http;
```

## 優勢

### 1. 效能提升

- **消除網路延遲**：直接調用避免 HTTP 請求的網路往返時間
- **減少序列化開銷**：無需 JSON 編碼/解碼過程
- **降低記憶體使用**：不需要建立額外的 HTTP 客戶端

### 2. 程式碼簡化

- **統一錯誤處理**：使用 Laravel 原生的 Password facade 狀態碼
- **更直接的邏輯**：移除中間層，程式碼更易理解
- **減少維護複雜度**：少了一個潛在的故障點

### 3. 安全性提升

- **內部調用**：密碼重設邏輯完全在應用程式內部執行
- **避免網路暴露**：不會有額外的網路請求被攔截風險

## 功能保持

- ✅ **CSRF 保護**：維持原有的 CSRF token 驗證
- ✅ **表單驗證**：保持相同的驗證規則
- ✅ **錯誤處理**：維持相同的錯誤訊息和狀態碼
- ✅ **事件觸發**：保持 `PasswordReset` 事件的觸發
- ✅ **特殊處理**：維持 `test@example.com` 的特殊測試邏輯

## 測試驗證

新增 `PasswordResetDirectCallTest` 測試類別，包含 5 個測試案例：

1. ✅ `testSuccessfulPasswordResetDirectCall` - 成功的密碼重設
2. ✅ `testInvalidTokenPasswordResetDirectCall` - 無效 token 處理
3. ✅ `testNonExistentUserPasswordResetDirectCall` - 不存在用戶處理
4. ✅ `testValidationFailureDirectCall` - 表單驗證失敗
5. ✅ `testSpecialTestUserCaseDirectCall` - 特殊測試用戶情況

**測試結果：5 passed (15 assertions)**

## 相容性

- ✅ **前端程式碼**：無需修改，API 回應格式完全相同
- ✅ **路由設定**：無需修改
- ✅ **中介軟體**：CSRF 保護繼續運作
- ✅ **資料庫**：無影響

## 後續考量

1. **監控效能**：可以監控改動後的回應時間改善程度
2. **日誌記錄**：考慮添加更詳細的內部調用日誌
3. **擴展性**：為未來可能的額外密碼重設邏輯預留空間

---

**總結**：此次重構成功將 HTTP 請求改為直接程式碼調用，在保持功能完整性的同時，提升了效能和程式碼的可維護性。
