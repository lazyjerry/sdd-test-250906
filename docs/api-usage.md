# API 使用指南

## API 測試工具

**推薦使用 Insomnia 進行 API 測試:**

```bash
# 1. 匯入 API 集合
# 完整功能集合: insomnia/laravel-api-complete.json
# 角色功能集合: insomnia/role-based-auth.json

# 2. 設定環境變數
# base_url: http://localhost/api/v1
# admin_token: (管理員登入後取得)
# user_token: (使用者登入後取得)
```

詳細 API 使用指南請參考: [insomnia/README.md](../insomnia/README.md)

## 統一登入體驗

### 管理員登入

支援 username 或 email 登入：

```bash
# 使用 username 登入
curl -X POST http://localhost/api/v1/auth/admin-login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "admin123"}'

# 使用 email 登入
curl -X POST http://localhost/api/v1/auth/admin-login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin@example.com", "password": "admin123"}'
```

- **自動識別**: 系統自動判斷輸入的是 username 還是 email
- **不受 email 驗證設定影響**: 管理員始終可以直接登入
- **專為管理員設計**: 提供管理員特定的權限和功能

### 一般用戶登入

```bash
# 使用 username 登入
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "testuser", "password": "UserPass123"}'

# 使用 email 登入
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "user@example.com", "password": "UserPass123"}'
```

- **統一介面**: 使用相同的 `username` 欄位，系統自動識別類型
- **一致體驗**: 與管理員登入提供相同的靈活性
- **email 驗證控制**: 受 `REQUIRE_EMAIL_VERIFICATION` 設定影響

## API 使用範例

### 1. 使用者註冊

```bash
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "name": "Test User",
    "email": "test@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

### 2. 獲取個人資料

```bash
curl -X GET http://localhost/api/v1/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 3. 管理員創建用戶

```bash
# 獲取管理員 token (先登入)
TOKEN="your_admin_token_here"

# 創建新管理員
curl -X POST http://localhost/api/v1/admin/users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "新管理員",
    "username": "newadmin",
    "email": "newadmin@example.com",
    "password": "SecurePass123",
    "role": "admin"
  }'

# 創建一般用戶
curl -X POST http://localhost/api/v1/admin/users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "一般用戶",
    "username": "user1",
    "email": "user1@example.com",
    "password": "UserPass123",
    "role": "user"
  }'
```

### 4. 管理員獲取使用者列表

```bash
curl -X GET "http://localhost/api/v1/admin/users?search=test&role=user&per_page=10" \
  -H "Authorization: Bearer ADMIN_TOKEN_HERE"
```

## API 端點總覽

### 身份驗證

- `POST /api/v1/auth/register` - 用戶註冊
- `POST /api/v1/auth/login` - 一般登入 (支援 username 或 email)
- `POST /api/v1/auth/admin-login` - 管理員登入 (支援 username 或 email)
- `POST /api/v1/auth/logout` - 登出
- `POST /api/v1/auth/refresh` - 刷新 token

### 用戶管理

- `GET /api/v1/user` - 獲取當前用戶資訊
- `PUT /api/v1/user` - 更新用戶資訊

### 管理員功能

- `POST /api/v1/admin/users` - 創建新用戶 (任何角色)
- `GET /api/v1/admin/users` - 查看所有用戶
- `GET /api/v1/admin/users/{id}` - 查看特定用戶
- `PUT /api/v1/admin/users/{id}` - 更新用戶資訊
- `DELETE /api/v1/admin/users/{id}` - 刪除用戶

### Email 驗證

- `POST /api/v1/email/verification-notification` - 重新發送驗證郵件
- `GET /api/v1/email/verify/{id}/{hash}` - 驗證 email

### 系統工具

- `GET /swagger-ui/` - API 文檔
- `GET /adminer.php` - 資料庫管理工具

## API 參數變更說明

### 登入 API 參數更新

為了提供更好的用戶體驗，我們統一了所有登入 API 的參數格式：

#### 新的參數格式 (推薦)

```bash
# 所有登入 API 都使用 "username" 欄位
POST /api/v1/auth/login
POST /api/v1/auth/admin-login

{
  "username": "可以是 username 或 email",
  "password": "密碼"
}
```

#### 舊的參數格式 (已棄用)

```bash
# 舊格式：只有一般登入支援 email 欄位
POST /api/v1/auth/login
{
  "email": "user@example.com",  # 不再支援
  "password": "密碼"
}
```

### 向下相容性

- **完全向下相容**: 使用 `username` 欄位傳入 email 值仍然有效
- **API 結構不變**: Response 格式保持一致
- **錯誤處理一致**: 錯誤碼和訊息格式不變

### 遷移建議

1. **現有客戶端**: 將所有登入請求的 `email` 欄位改為 `username`
2. **新開發**: 統一使用 `username` 欄位，系統會自動識別類型
3. **測試**: 確保你的應用程式能處理 username 和 email 兩種輸入

## 支援的用戶角色

- **`user`**: 一般用戶，基本權限
- **`admin`**: 管理員，管理用戶權限
- **`super_admin`**: 超級管理員，所有權限

詳細的 API 文件請訪問：http://localhost/swagger-ui/
