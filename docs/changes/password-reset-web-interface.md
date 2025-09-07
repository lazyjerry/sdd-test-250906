# 密碼重設功能 - Bootstrap 5 網頁實作

## 📋 功能概述

實作了一個完整的密碼重設功能，包含美觀的 Bootstrap 5 網頁介面，提供用戶友善的密碼重設體驗。

## 🎯 功能特色

### 🎨 前端設計

- **Bootstrap 5** 響應式設計
- **Bootstrap Icons** 圖示系統
- **漸層背景** 現代化視覺效果
- **動畫效果** 提升用戶體驗
- **即時驗證** 密碼強度與匹配檢查

### 🔒 安全功能

- **CSRF 保護** Laravel 內建安全機制
- **Token 驗證** 確保重設請求合法性
- **密碼強度檢查** 即時提示密碼安全性
- **輸入驗證** 前後端雙重驗證

### 🚀 用戶體驗

- **密碼顯示切換** 方便確認輸入
- **即時密碼匹配** 立即提示不匹配
- **載入狀態** 提交時顯示處理中
- **自動跳轉** 成功後自動導向 API 文檔

## 📁 檔案結構

```
app/Http/Controllers/Web/
└── PasswordResetController.php        # Web 控制器

resources/views/auth/
├── password-reset.blade.php           # 密碼重設表單頁面
└── password-reset-success.blade.php   # 成功頁面

resources/views/
└── password-reset-test.blade.php      # 測試說明頁面

routes/
└── web.php                           # Web 路由定義
```

## 🛣️ 路由結構

| 方法 | 路由                      | 控制器方法      | 說明             |
| ---- | ------------------------- | --------------- | ---------------- |
| GET  | `/password/reset/{token}` | `showResetForm` | 顯示密碼重設表單 |
| POST | `/password/reset`         | `reset`         | 處理密碼重設請求 |
| GET  | `/password/reset-success` | `success`       | 顯示成功頁面     |
| GET  | `/password-reset-test`    | -               | 測試說明頁面     |

## 🔄 完整流程

### 1. 發送忘記密碼請求

```bash
POST /api/v1/auth/forgot-password
Content-Type: application/json

{
    "email": "test@example.com"
}
```

### 2. 點擊郵件連結

```
http://localhost/password/reset/{token}?email=test@example.com
```

### 3. 填寫重設表單

- 電子郵件（預填且只讀）
- 新密碼（即時強度檢查）
- 確認密碼（即時匹配檢查）

### 4. 系統處理

- 前端驗證
- CSRF 檢查
- 調用 API: `POST /api/v1/auth/reset-password`
- 顯示結果

### 5. 完成重設

- 成功頁面展示
- 10 秒倒數自動跳轉
- 手動前往 API 文檔

## 🎨 視覺設計

### 配色方案

- **主背景**: 紫色漸層 (#667eea → #764ba2)
- **成功頁面**: 綠色漸層 (#28a745 → #20c997)
- **按鈕**: 圓角設計，hover 動畫
- **卡片**: 圓角陰影，現代化外觀

### 響應式設計

- **桌面版**: 居中卡片設計
- **行動版**: 全寬度適應
- **平板**: 適中寬度顯示

## 🔧 技術實作

### 前端驗證

```javascript
// 密碼強度檢查
function checkPasswordStrength(password) {
	if (password.length < 8) {
		return { message: "密碼長度不足", class: "strength-weak" };
	}

	let score = 0;
	const checks = [
		/[a-z]/.test(password), // 小寫字母
		/[A-Z]/.test(password), // 大寫字母
		/\d/.test(password), // 數字
		/[@$!%*?&]/.test(password), // 特殊字符
	];

	score = checks.filter((check) => check).length;
	// 返回強度等級
}

// 密碼匹配檢查
function checkPasswordMatch() {
	const password = passwordInput.value;
	const confirmPassword = passwordConfirmInput.value;
	// 比較密碼是否匹配
}
```

### 後端處理

```php
public function reset(Request $request)
{
    // 1. 驗證表單資料
    $validator = Validator::make($request->all(), [
        'token' => 'required|string',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/'
    ]);

    // 2. 調用 API
    $response = Http::post(url('/api/v1/auth/reset-password'), $data);

    // 3. 處理回應
    if ($response->successful()) {
        return redirect()->route('password.reset.success');
    }
}
```

## 🧪 測試方式

### 1. 訪問測試頁面

```
http://localhost/password-reset-test
```

### 2. 手動測試流程

1. 點擊「發送忘記密碼請求」
2. 點擊「開啟密碼重設頁面」
3. 填寫新密碼表單
4. 查看成功頁面

### 3. API 測試

使用 Insomnia 測試完整 API 流程：

1. 忘記密碼請求
2. 獲取重設 token
3. 重設密碼請求

## 🔍 密碼要求

### 強度規則

- **最少 8 個字符**
- **至少 1 個小寫字母** (a-z)
- **至少 1 個大寫字母** (A-Z)
- **至少 1 個數字** (0-9)
- **至少 1 個特殊字符** (@$!%\*?&)

### 範例有效密碼

- `Password123!`
- `MySecure@Pass1`
- `Strong$Pass99`

## 🚨 錯誤處理

### 前端錯誤

- 密碼強度不足
- 密碼不匹配
- 必填欄位為空

### 後端錯誤

- Token 無效或過期
- Email 格式錯誤
- API 連線失敗
- 伺服器錯誤

### 顯示方式

- Bootstrap Alert 組件
- 圖示 + 文字描述
- 表單欄位高亮顯示

## 📱 使用說明

### 用戶操作流程

1. **收到郵件** - 點擊密碼重設連結
2. **開啟表單** - 系統自動填入 Email
3. **輸入密碼** - 即時查看強度提示
4. **確認密碼** - 即時檢查是否匹配
5. **提交表單** - 系統處理並回饋結果
6. **完成重設** - 自動跳轉到 API 文檔

### 管理員功能

- 可協助用戶重設密碼
- 查看密碼重設日誌
- 系統健康檢查

## 🎉 完成效果

✅ **現代化設計** - Bootstrap 5 響應式介面  
✅ **安全可靠** - CSRF 保護 + Token 驗證  
✅ **用戶友善** - 即時提示 + 動畫效果  
✅ **完整流程** - 從郵件到完成的全程體驗  
✅ **錯誤處理** - 完善的錯誤提示機制

## 🔗 相關連結

- **測試頁面**: http://localhost/password-reset-test
- **API 文檔**: http://localhost/swagger-ui/
- **Insomnia 集合**: `/insomnia/laravel-api.yaml`
