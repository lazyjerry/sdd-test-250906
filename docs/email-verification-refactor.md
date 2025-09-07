# 郵件驗證功能重構 - API 與 Web 分離

## 重構概要

將 `AuthController::verifyEmailByLink` 方法從 API 控制器移動到獨立的 Web 控制器 `EmailVerificationController`，同時創建共用的 `EmailVerificationService` 服務，實現 API 和 Web 端點的邏輯分離。

## 問題分析

### 職責混淆

- **API 控制器處理 Web 請求**：`AuthController::verifyEmailByLink` 處理瀏覽器 GET 請求
- **回應格式不一致**：Web 用戶需要友好的 HTML 頁面，而非 JSON 回應
- **程式碼重複**：`verifyEmail` 和 `verifyEmailByLink` 有大量重複邏輯
- **RESTful 違反**：GET 請求修改服務器狀態

### 維護困難

- **混合職責**：API 控制器既處理 API 請求又處理 Web 請求
- **測試複雜**：需要在 API 測試中模擬 Web 行為
- **擴展受限**：難以為 Web 用戶提供專屬功能

## 重構方案

### 1. 新增 EmailVerificationService

**檔案：** `app/Services/EmailVerificationService.php`

```php
class EmailVerificationService
{
    // 核心郵件驗證邏輯
    public function verifyEmail(array $credentials): array

    // API 格式回應
    public function formatApiResponse(array $result): array

    // Web 格式回應
    public function formatWebResponse(array $result): array
}
```

**核心特色：**

- ✅ **統一邏輯**：所有郵件驗證邏輯集中在一處
- ✅ **格式分離**：支援 API 和 Web 不同的回應格式
- ✅ **安全驗證**：完整的簽名、過期時間、哈希驗證
- ✅ **錯誤處理**：統一的錯誤代碼和訊息

### 2. 新增 EmailVerificationController (Web)

**檔案：** `app/Http/Controllers/Web/EmailVerificationController.php`

```php
class EmailVerificationController extends Controller
{
    // 處理 Web 端郵件驗證連結
    public function verifyByLink(Request $request, $id, $hash)

    // 顯示驗證結果頁面
    private function showVerificationResult(array $result)
}
```

**Web 特色：**

- 🎨 **友好界面**：Bootstrap 5 設計的驗證結果頁面
- 📱 **響應式設計**：支援桌面和移動設備
- 🔄 **自動跳轉**：驗證成功後自動跳轉到登入頁面
- ⚡ **AJAX 支援**：同時支援普通請求和 AJAX 請求

### 3. 重構 AuthController

**變更前：**

```php
public function verifyEmailByLink(Request $request, $id, $hash): JsonResponse
{
    // 60+ 行的參數驗證和郵件驗證邏輯...
    return $this->verifyEmail($request);
}

public function verifyEmail(Request $request): JsonResponse
{
    // 80+ 行的郵件驗證邏輯...
}
```

**變更後：**

```php
public function verifyEmail(Request $request): JsonResponse
{
    $emailVerificationService = new EmailVerificationService();

    $result = $emailVerificationService->verifyEmail([
        'id' => $request->id,
        'hash' => $request->hash,
        'expires' => $request->expires,
        'signature' => $request->signature,
    ]);

    $response = $emailVerificationService->formatApiResponse($result);
    $statusCode = $result['success'] ? 200 : ($result['error_code'] === 'USER_NOT_FOUND' ? 404 : 400);

    return response()->json($response, $statusCode);
}

// verifyEmailByLink 方法已移除
```

### 4. 創建驗證結果頁面

**檔案：** `resources/views/auth/email-verification-result.blade.php`

**功能特色：**

- ✅ **成功狀態**：顯示歡迎訊息和用戶資訊
- ❌ **失敗狀態**：顯示具體錯誤原因和解決方案
- 🎯 **用戶友好**：清晰的圖示和色彩指示
- 🔗 **便捷操作**：提供重新發送驗證郵件和返回登入的按鈕

### 5. 更新路由設定

**檔案：** `routes/web.php`

```php
// 變更前
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmailByLink'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// 變更後
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verifyByLink'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
```

## 測試策略

### 1. 服務類別單元測試

**檔案：** `tests/Unit/Services/EmailVerificationServiceTest.php`

- ✅ 8 個測試案例，24 個斷言
- 測試核心邏輯、各種錯誤情況、格式化方法

### 2. Web 端點測試

**檔案：** `tests/Feature/Web/EmailVerificationTest.php`

- ✅ 6 個測試案例，26 個斷言
- 測試 HTML 頁面顯示、AJAX 回應、各種錯誤情況

### 3. API 端點測試

**檔案：** `tests/Feature/Api/V1/AuthEmailVerificationServiceTest.php`

- ✅ 5 個測試案例，16 個斷言
- 測試 JSON API 回應、錯誤處理、參數驗證

**總測試覆蓋：** 19 個測試案例，66 個斷言，全部通過 ✅

## 重構成果

### 職責清晰分離

- **API 控制器**：專注於 JSON API 回應
- **Web 控制器**：專注於 HTML 頁面和用戶體驗
- **服務類別**：專注於業務邏輯處理

### 用戶體驗提升

- **Web 用戶**：獲得友好的驗證結果頁面
- **API 用戶**：獲得標準化的 JSON 回應
- **開發者**：代碼結構更清晰，易於維護

### 程式碼品質改善

- **DRY 原則**：消除重複的驗證邏輯
- **單一職責**：每個類別有明確的職責
- **開放封閉**：易於擴展新的驗證端點或格式

### 安全性保持

- ✅ **簽名驗證**：保持原有的 Laravel 簽名驗證機制
- ✅ **速率限制**：維持防暴力攻擊的限制機制
- ✅ **參數驗證**：完整的輸入參數驗證
- ✅ **錯誤處理**：安全的錯誤訊息，不洩露敏感資訊

## 架構優勢

### 1. 清晰的邊界

```
┌─────────────────┬─────────────────┐
│   API 請求      │   Web 請求      │
│                 │                 │
│ AuthController  │ EmailVerificationController
│       │         │       │         │
│       └─────────┴───────┘         │
│             │                     │
│    EmailVerificationService       │
│             │                     │
│        業務邏輯處理                │
└─────────────────┴─────────────────┘
```

### 2. 擴展性

- **新增端點**：可輕鬆添加新的驗證端點
- **自訂格式**：可為不同客戶端提供專屬格式
- **業務邏輯**：核心邏輯變更只需修改服務類別

### 3. 可測試性

- **單元測試**：服務邏輯獨立測試
- **整合測試**：端點行為完整測試
- **隔離測試**：API 和 Web 功能分別測試

## 未來改進

1. **快取機制**：可在服務層添加驗證狀態快取
2. **事件系統**：可擴展更豐富的驗證事件
3. **多語言支援**：可在 Web 頁面添加國際化
4. **自訂主題**：可提供可配置的驗證頁面主題

---

**總結**：此次重構成功將 API 和 Web 功能分離，提升了用戶體驗和代碼可維護性，同時保持了所有原有的安全機制和功能特性。透過完整的測試覆蓋，確保重構過程的安全性和可靠性。
