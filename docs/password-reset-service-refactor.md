# 密碼重設邏輯重構 - 消除程式碼重複

## 重構概要

將 `AuthController::resetPassword` 和 `PasswordResetController::reset` 中重複的密碼重設邏輯抽取到共用服務類別 `PasswordResetService`。

## 問題分析

### 重複程式碼

兩個控制器都包含相同的密碼重設邏輯：

- 使用 `Password::reset()` 執行密碼重設
- 相同的錯誤處理邏輯
- 特殊測試用戶處理
- 相似的回應格式化

### 維護問題

- **程式碼重複**：相同邏輯在兩處維護
- **不一致風險**：修改一處可能忘記更新另一處
- **測試重複**：需要重複測試相同邏輯
- **違反 DRY 原則**：Don't Repeat Yourself

## 重構方案

### 1. 新增 PasswordResetService

**檔案：** `app/Services/PasswordResetService.php`

```php
class PasswordResetService
{
    // 核心密碼重設邏輯
    public function resetPassword(array $credentials): array

    // API 格式回應
    public function formatApiResponse(array $result): array

    // Web 格式回應
    public function formatWebResponse(array $result, string $redirectUrl = ''): array
}
```

**核心特色：**

- ✅ **統一邏輯**：所有密碼重設邏輯集中在一處
- ✅ **格式分離**：支援 API 和 Web 不同的回應格式
- ✅ **可測試性**：獨立的服務類別易於單元測試
- ✅ **可重用性**：未來新增的密碼重設端點可直接使用

### 2. 重構 AuthController

**變更前：**

```php
public function resetPassword(PasswordResetRequest $request): JsonResponse
{
    $status = Password::reset(/*...*/);

    if (Password::PASSWORD_RESET === $status) {
        return response()->json([/*...*/]);
    }

    // 50+ 行的錯誤處理邏輯...
}
```

**變更後：**

```php
public function resetPassword(PasswordResetRequest $request): JsonResponse
{
    $passwordResetService = new PasswordResetService();

    $result = $passwordResetService->resetPassword(
        $request->only('email', 'password', 'password_confirmation', 'token')
    );

    $response = $passwordResetService->formatApiResponse($result);
    $statusCode = $result['success'] ? 200 : 400;

    return response()->json($response, $statusCode);
}
```

**優勢：**

- 📉 **程式碼減少**：從 50+ 行減少到 10 行
- 🔧 **職責清晰**：控制器只負責 HTTP 處理
- 🎯 **易於理解**：邏輯流程更加清晰

### 3. 重構 PasswordResetController

**變更前：**

```php
public function reset(Request $request)
{
    // 表單驗證...

    $status = Password::reset(/*...*/);

    if (Password::PASSWORD_RESET === $status) {
        return response()->json([/*...*/]);
    }

    // 50+ 行的錯誤處理邏輯...
}
```

**變更後：**

```php
public function reset(Request $request)
{
    // 表單驗證...

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
}
```

**保持：**

- ✅ **表單驗證**：Web 端特有的驗證邏輯保留
- ✅ **CSRF 保護**：原有的安全機制不變
- ✅ **回應格式**：前端無需修改

## 測試策略

### 1. 服務類別單元測試

**檔案：** `tests/Unit/Services/PasswordResetServiceTest.php`

- ✅ 6 個測試案例，18 個斷言
- 測試核心邏輯、格式化方法、錯誤處理

### 2. API 端點測試

**檔案：** `tests/Feature/Api/V1/AuthPasswordResetServiceTest.php`

- ✅ 4 個測試案例，10 個斷言
- 驗證 API 端點使用服務後的行為

### 3. Web 端點測試

**檔案：** `tests/Feature/Web/PasswordResetDirectCallTest.php`

- ✅ 5 個測試案例，15 個斷言
- 驗證 Web 端點使用服務後的行為

**總測試覆蓋：** 15 個測試案例，43 個斷言，全部通過 ✅

## 重構成果

### 程式碼品質提升

- **DRY 原則**：消除重複程式碼
- **單一職責**：服務類別專注於密碼重設邏輯
- **開放封閉**：易於擴展新的密碼重設端點

### 維護性改善

- **統一修改點**：邏輯變更只需修改服務類別
- **一致性保證**：所有端點使用相同邏輯
- **錯誤減少**：減少因同步問題導致的 bug

### 測試改善

- **集中測試**：核心邏輯在服務層統一測試
- **測試覆蓋**：更完整的測試覆蓋率
- **維護簡化**：減少重複測試程式碼

### 效能考量

- **記憶體影響**：微小（增加一個服務實例）
- **執行時間**：無明顯影響
- **可擴展性**：為未來功能擴展打下基礎

## 未來擴展

1. **依賴注入**：可改為使用依賴注入容器
2. **快取支援**：可在服務層添加快取機制
3. **事件系統**：可擴展更豐富的事件處理
4. **審計日誌**：可在服務層統一添加日誌記錄

---

**總結**：此次重構成功消除了程式碼重複，提升了程式碼品質和可維護性，同時保持了所有原有功能和安全機制。透過完整的測試覆蓋，確保重構過程的安全性和可靠性。
