# 登入角色隔離功能

實作了用戶登入和管理員登入的 API 隔離機制，確保系統安全性。

## 功能說明

### 角色隔離機制

1. **用戶登入 API** (`/api/v1/auth/login`) 只允許 `role = 'user'` 的用戶登入
2. **管理員登入 API** (`/api/v1/auth/admin-login`) 只允許 `role = 'admin'` 或 `role = 'super_admin'` 的用戶登入
3. 任何嘗試使用錯誤 API 的行為都會返回 401 錯誤

### 安全特色

- **軟刪除檢查**: 被軟刪除的用戶無法登入任何 API
- **詳細日誌記錄**: 記錄所有登入嘗試，包含失敗原因
- **統一錯誤格式**: 避免洩露敏感資訊
- **密碼安全**: 使用安全的密碼驗證機制

## 實作內容

### AuthController 修改

#### 用戶登入方法 (`login`)

- 新增角色檢查，只允許 `role = 'user'` 的用戶
- 檢查軟刪除狀態
- 詳細的失敗日誌記錄
- 統一的錯誤回應格式

#### 管理員登入方法 (`adminLogin`)

- 現有的角色檢查機制
- 支援 `admin` 和 `super_admin` 角色
- 增強的錯誤處理和日誌記錄

### 測試覆蓋

完整的測試套件 `LoginRoleIsolationTest.php` 包含 10 個測試案例：

- ✅ 普通用戶無法使用管理員登入 API
- ✅ 管理員無法使用普通用戶登入 API
- ✅ 超級管理員無法使用普通用戶登入 API
- ✅ 同時測試 username 和 email 登入
- ✅ 軟刪除用戶無法登入
- ✅ 正確的 API 可以正常登入

## API 回應格式

### 成功登入

```json
{
	"status": "success",
	"message": "登入成功",
	"data": {
		"user": {
			/* 用戶資料 */
		},
		"token": "token_string",
		"expires_at": "2023-12-31T23:59:59.000000Z"
	}
}
```

### 角色隔離錯誤

```json
{
	"status": "error",
	"message": "使用者名稱或密碼錯誤",
	"error_code": "INVALID_CREDENTIALS"
}
```

## 使用範例

### 正確使用方式

```bash
# 普通用戶登入
POST /api/v1/auth/login
{
  "username": "user123",
  "password": "password"
}

# 管理員登入
POST /api/v1/auth/admin-login
{
  "username": "admin123",
  "password": "admin_password"
}
```

### 錯誤使用方式 (會被拒絕)

```bash
# 管理員嘗試用普通用戶 API - 返回 401
POST /api/v1/auth/login
{
  "username": "admin123",
  "password": "admin_password"
}

# 普通用戶嘗試用管理員 API - 返回 401
POST /api/v1/auth/admin-login
{
  "username": "user123",
  "password": "password"
}
```

## 日誌記錄

系統會記錄所有登入嘗試：

```log
[INFO] 用戶登入成功: {"user_id": 1, "username": "user123", "ip": "127.0.0.1"}
[WARNING] 用戶登入失敗: {"username": "admin123", "reason": "user_not_found_or_not_user_role", "ip": "127.0.0.1"}
[INFO] 管理員登入成功: {"admin_id": 2, "username": "admin123", "ip": "127.0.0.1"}
[WARNING] 管理員登入失敗: {"username": "user123", "reason": "user_not_found_or_not_admin", "ip": "127.0.0.1"}
```

## 測試執行

```bash
# 執行角色隔離測試
./vendor/bin/sail test tests/Feature/Auth/LoginRoleIsolationTest.php

# 測試結果: 10/10 通過 (53 assertions)
Tests:    10 passed (53 assertions)
```

## 總結

✅ **功能完成**: 用戶和管理員登入 API 完全隔離  
✅ **測試覆蓋**: 全面的測試確保功能正確性  
✅ **安全增強**: 詳細日誌記錄和統一錯誤處理  
✅ **向後相容**: 現有功能不受影響
