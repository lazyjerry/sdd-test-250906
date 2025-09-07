# Insomnia API 集合更新摘要

## 📝 更新日期

2025 年 9 月 7 日

## 🔄 主要更新內容

### 1. ✅ 修正管理員登入端點

**變更前:** `/auth/login` (錯誤)  
**變更後:** `/auth/admin-login` (正確)  
**說明:** 管理員現在使用專用的登入端點，與一般用戶完全隔離

### 2. ✅ 更新預設帳號密碼

**管理員帳號:**

- Username: `admin`
- Password: `admin123`
- Email: `admin@example.com`

**測試用戶帳號:**

- Username: `testuser` (原: `normaluser`)
- Password: `UserPassword123!`
- Email: `user@example.com`

### 3. ✅ 新增統一用戶創建 API

**新增端點:** `POST /admin/users`  
**說明:** 管理員可使用統一 API 創建任何角色的用戶，取代舊的分散式創建方式

**範例請求:**

```json
{
	"name": "新創建的用戶",
	"username": "newuser123",
	"email": "newuser@example.com",
	"phone": "0900999888",
	"password": "NewUserPass123!",
	"role": "user"
}
```

### 4. ✅ 更新用戶 ID 參數

**目的:** 使用實際存在的用戶 ID 進行 API 測試

| API 功能 | 原 ID | 新 ID | 說明            |
| -------- | ----- | ----- | --------------- |
| 查看用戶 | 1     | 2     | 指向 Test User  |
| 更新用戶 | 1     | 2     | 指向 Test User  |
| 刪除用戶 | 1     | 3     | 指向 normaluser |
| 啟用用戶 | 1     | 3     | 指向 normaluser |
| 停用用戶 | 1     | 3     | 指向 normaluser |
| 重設密碼 | 1     | 2     | 指向 Test User  |

### 5. ✅ 統一測試資料格式

**郵件地址:** 統一使用 `test@example.com` 作為忘記密碼等功能的測試郵件

## 📊 當前系統用戶狀況

| ID  | Username        | Email             | Role        | 用途       |
| --- | --------------- | ----------------- | ----------- | ---------- |
| 1   | admin           | admin@example.com | super_admin | 系統管理員 |
| 2   | hermann.gustave | test@example.com  | user        | 測試用戶   |
| 3   | normaluser      | user@example.com  | user        | 一般用戶   |

## 🔧 環境變數配置

### 開發環境 (預設)

```yaml
base_url: http://localhost/api/v1
token: ""
user_token: ""
admin_token: ""
```

### 測試環境

```yaml
base_url: https://staging.example.com/api/v1
token: ""
user_token: ""
admin_token: ""
```

### 正式環境

```yaml
base_url: https://api.example.com/api/v1
token: ""
user_token: ""
admin_token: ""
```

## 🚀 API 端點總覽

### 🔐 身份驗證

- `POST /auth/register` - 用戶註冊
- `POST /auth/login` - 一般用戶登入
- `POST /auth/admin-login` - **管理員專用登入** (已修正)
- `POST /auth/logout` - 登出
- `POST /auth/forgot-password` - 忘記密碼
- `POST /auth/reset-password` - 重設密碼
- `POST /auth/verify-email` - 郵箱驗證

### 👤 用戶管理

- `GET /users/profile` - 獲取個人資料
- `PUT /users/profile` - 更新個人資料
- `PUT /users/change-password` - 變更密碼

### 👨‍💼 管理員功能

- `POST /admin/users` - **創建用戶（統一 API）** (新增)
- `POST /admin/register` - 管理員註冊用戶 (舊版，保留)
- `GET /admin/users` - 查看所有用戶
- `GET /admin/users/{id}` - 查看特定用戶
- `PUT /admin/users/{id}` - 更新用戶
- `DELETE /admin/users/{id}` - 刪除用戶
- `POST /admin/users/{id}/activate` - 啟用用戶
- `POST /admin/users/{id}/deactivate` - 停用用戶
- `POST /admin/users/{id}/reset-password` - 重設用戶密碼

### 📊 統計與監控

- `GET /admin/statistics/users` - 用戶統計
- `GET /admin/statistics/system` - 系統統計
- `GET /admin/statistics/activity` - 活動統計
- `GET /admin/system/health` - 系統健康檢查
- `GET /admin/audit-log` - 審計日誌
- `GET /admin/activity-log` - 活動日誌

## ✅ 驗證完成

所有 API 端點路徑、帳號密碼和測試資料都已更新為正確的值，與實際系統保持一致：

1. ✅ 管理員登入端點已修正
2. ✅ 預設帳號密碼已更新
3. ✅ 統一 API 端點已添加
4. ✅ 測試用戶 ID 已調整
5. ✅ 環境變數已配置完成

**🎉 Insomnia API 集合現在可以正常使用！**
