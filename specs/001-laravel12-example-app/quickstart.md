# Laravel12 Example-App 快速開始指南

## 前置需求

- Docker Desktop
- Git
- 基本的 Laravel 知識

## 環境設定

### 1. 專案初始化

```bash
# 建立 Laravel 專案
curl -s "https://laravel.build/example-app?with=mysql,redis,mailhog" | bash

cd example-app

# 啟動 Sail
./vendor/bin/sail up -d

# 安裝 Sanctum (API 認證)
./vendor/bin/sail artisan install:api
```

### 2. 環境配置

編輯 `.env` 檔案：

```env
# 應用程式設定
APP_NAME="Example App"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# 資料庫設定
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=example_app
DB_USERNAME=sail
DB_PASSWORD=password

# 郵件設定 (開發環境使用 MailHog)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Queue 設定
QUEUE_CONNECTION=redis

# Cache 設定
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### 3. 資料庫遷移

```bash
# 執行遷移
./vendor/bin/sail artisan migrate

# 執行 Seeder (建立測試資料)
./vendor/bin/sail artisan db:seed
```

## 核心功能測試流程

### 使用者註冊流程

```bash
# 1. 註冊新使用者
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "Password123",
    "password_confirmation": "Password123",
    "name": "測試使用者"
  }'

# 預期回應: 201 Created
{
  "success": true,
  "message": "註冊成功，請檢查您的信箱進行驗證",
  "data": {
    "user": {
      "id": 1,
      "username": "testuser",
      "email": "test@example.com",
      "name": "測試使用者",
      "email_verified_at": null,
      "created_at": "2025-09-05T12:00:00.000000Z",
      "updated_at": "2025-09-05T12:00:00.000000Z"
    }
  }
}
```

### 信箱驗證流程

```bash
# 2. 檢查 MailHog (http://localhost:8025) 取得驗證 token

# 3. 驗證信箱
curl -X POST http://localhost/api/v1/auth/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "token": "驗證token從郵件中取得"
  }'

# 預期回應: 200 OK
{
  "success": true,
  "message": "信箱驗證成功"
}
```

### 使用者登入流程

```bash
# 4. 使用者登入
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "Password123"
  }'

# 預期回應: 200 OK
{
  "success": true,
  "message": "登入成功",
  "data": {
    "user": {
      "id": 1,
      "username": "testuser",
      "email": "test@example.com",
      "name": "測試使用者",
      "email_verified_at": "2025-09-05T12:05:00.000000Z",
      "created_at": "2025-09-05T12:00:00.000000Z",
      "updated_at": "2025-09-05T12:05:00.000000Z"
    },
    "token": "1|abc123def456...",
    "expires_at": "2025-09-06T12:00:00.000000Z"
  }
}

# 保存 token 用於後續 API 呼叫
export API_TOKEN="1|abc123def456..."
```

### 個人資料管理流程

```bash
# 5. 取得個人資料
curl -X GET http://localhost/api/v1/users/profile \
  -H "Authorization: Bearer $API_TOKEN"

# 6. 更新個人資料
curl -X PUT http://localhost/api/v1/users/profile \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "更新後的姓名",
    "phone": "+886912345678"
  }'

# 7. 修改密碼
curl -X PUT http://localhost/api/v1/users/change-password \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "current_password": "Password123",
    "password": "NewPassword123",
    "password_confirmation": "NewPassword123"
  }'
```

### 忘記密碼流程

```bash
# 8. 申請密碼重設
curl -X POST http://localhost/api/v1/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com"
  }'

# 9. 檢查 MailHog 取得重設 token

# 10. 重設密碼
curl -X POST http://localhost/api/v1/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "token": "重設token從郵件中取得",
    "password": "ResetPassword123",
    "password_confirmation": "ResetPassword123"
  }'
```

## 管理員功能測試

### 建立管理員帳戶

```bash
# 使用 Artisan 命令建立管理員
./vendor/bin/sail artisan make:admin-user \
  --username=admin \
  --email=admin@example.com \
  --password=AdminPass123 \
  --name="系統管理員"
```

### 管理員登入與操作

```bash
# 1. 管理員登入 (使用相同的登入端點)
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "AdminPass123"
  }'

export ADMIN_TOKEN="取得的管理員token"

# 2. 取得使用者清單
curl -X GET "http://localhost/api/v1/admin/users?page=1&per_page=10" \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# 3. 取得特定使用者資料
curl -X GET http://localhost/api/v1/admin/users/1 \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# 4. 更新使用者資料
curl -X PUT http://localhost/api/v1/admin/users/1 \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "管理員修改的姓名",
    "email": "newemail@example.com"
  }'

# 5. 重設使用者密碼
curl -X POST http://localhost/api/v1/admin/users/1/reset-password \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "AdminResetPass123",
    "password_confirmation": "AdminResetPass123"
  }'
```

## 測試驗證

### 自動化測試執行

```bash
# 執行所有測試
./vendor/bin/sail test

# 執行特定測試套件
./vendor/bin/sail test --testsuite=Feature
./vendor/bin/sail test --testsuite=Unit

# 執行測試覆蓋率報告
./vendor/bin/sail test --coverage
```

### 預期測試結果

- ✅ 使用者註冊功能測試
- ✅ 信箱驗證功能測試
- ✅ 使用者登入功能測試
- ✅ 個人資料管理功能測試
- ✅ 密碼重設功能測試
- ✅ 管理員功能測試
- ✅ API 權限驗證測試
- ✅ 資料驗證測試

## 開發工具

### API 文件

- OpenAPI 規格: `/docs/api-spec.yaml`
- Swagger UI: `http://localhost/api/documentation`

### 郵件測試

- MailHog 介面: `http://localhost:8025`
- 查看所有發送的郵件

### 資料庫管理

```bash
# 進入 MySQL
./vendor/bin/sail mysql

# 重置資料庫
./vendor/bin/sail artisan migrate:fresh --seed
```

### 日誌查看

```bash
# 查看 Laravel 日誌
./vendor/bin/sail logs -f

# 查看特定服務日誌
./vendor/bin/sail logs mysql -f
```

## 效能測試

### API 負載測試

```bash
# 使用 Apache Bench 測試
ab -n 1000 -c 10 -H "Authorization: Bearer $API_TOKEN" \
  http://localhost/api/v1/users/profile

# 預期結果: 平均回應時間 < 200ms
```

### 資料庫效能

```bash
# 查看慢查詢
./vendor/bin/sail artisan db:monitor

# 查看資料庫索引使用情況
./vendor/bin/sail artisan db:show --counts
```

## 故障排除

### 常見問題

1. **無法連接資料庫**: 檢查 Sail 服務是否正常運行
2. **郵件無法發送**: 確認 MailHog 服務狀態
3. **Token 無效**: 檢查 Token 是否過期或格式正確
4. **權限錯誤**: 確認使用者類型和權限設定

### 重置環境

```bash
# 完全重置環境
./vendor/bin/sail down -v
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
```

## 部署準備

### 生產環境設定

```bash
# 最佳化快取
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache

# 產生應用程式金鑰
./vendor/bin/sail artisan key:generate
```

### 安全檢查清單

- [ ] 更新所有預設密碼
- [ ] 設定適當的 CORS 政策
- [ ] 啟用 HTTPS
- [ ] 配置生產環境 SMTP
- [ ] 設定資料庫備份策略
- [ ] 配置監控和日誌系統
