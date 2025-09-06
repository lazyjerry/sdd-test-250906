# 郵箱驗證功能測試指南

## 🔧 測試環境準備

### 1. 啟動開發環境

```bash
cd example-app
./vendor/bin/sail up -d
```

### 2. 設置測試資料庫

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

### 3. 配置郵件測試

在 `.env` 文件中設置：

```env
MAIL_DRIVER=log
MAIL_LOG_CHANNEL=single
```

## 🧪 自動化測試

### 運行完整測試套件

```bash
./vendor/bin/sail artisan test tests/Feature/Auth/EmailVerificationTest.php
```

### 運行特定測試

```bash
# 測試 POST API 驗證
./vendor/bin/sail artisan test --filter=user_can_verify_email_via_post_api

# 測試 GET 路由驗證
./vendor/bin/sail artisan test --filter=user_can_verify_email_via_get_route

# 測試無效簽名
./vendor/bin/sail artisan test --filter=email_verification_fails_with_invalid_signature

# 測試過期連結
./vendor/bin/sail artisan test --filter=email_verification_fails_with_expired_link
```

## 🔍 手動測試步驟

### 步驟 1：註冊新用戶

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "username": "testuser123",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**預期結果：**

- 狀態碼：201
- 返回用戶信息和提示需要驗證郵箱

### 步驟 2：檢查郵件日誌

```bash
./vendor/bin/sail logs | grep "verification"
# 或者查看日誌文件
cat storage/logs/laravel.log | grep "verification"
```

**預期結果：**

- 找到包含驗證連結的郵件內容

### 步驟 3：提取驗證連結

從日誌中複製驗證連結，格式類似：

```
http://localhost:8000/api/email/verify/1/abc123?expires=1725616800&signature=xyz789
```

### 步驟 4A：測試 GET 路由驗證

直接在瀏覽器中打開驗證連結，或使用 curl：

```bash
curl -v "http://localhost:8000/api/email/verify/1/abc123?expires=1725616800&signature=xyz789"
```

**預期結果：**

- 狀態碼：200
- 返回成功驗證訊息

### 步驟 4B：測試 POST API 驗證

```bash
curl -X POST http://localhost:8000/api/v1/auth/verify-email \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "id": 1,
    "hash": "abc123",
    "expires": 1725616800,
    "signature": "xyz789"
  }'
```

**預期結果：**

- 狀態碼：200
- 返回成功驗證訊息

### 步驟 5：驗證用戶登入

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "username": "testuser123",
    "password": "password123"
  }'
```

**預期結果：**

- 狀態碼：200
- 返回認證 token（表示郵箱已驗證，可以登入）

## 🚫 錯誤情境測試

### 測試 1：無效簽名

```bash
curl -v "http://localhost:8000/api/email/verify/1/abc123?expires=1725616800&signature=invalid_signature"
```

**預期結果：** 403 Forbidden

### 測試 2：過期連結

使用過去的時間戳：

```bash
curl -v "http://localhost:8000/api/email/verify/1/abc123?expires=1600000000&signature=xyz789"
```

**預期結果：** 403 Forbidden

### 測試 3：錯誤的 Hash

```bash
curl -X POST http://localhost:8000/api/v1/auth/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "id": 1,
    "hash": "wrong_hash",
    "expires": 1725616800,
    "signature": "xyz789"
  }'
```

**預期結果：** 400 Bad Request

### 測試 4：不存在的用戶

```bash
curl -X POST http://localhost:8000/api/v1/auth/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "id": 999,
    "hash": "abc123",
    "expires": 1725616800,
    "signature": "xyz789"
  }'
```

**預期結果：** 404 Not Found

### 測試 5：已驗證的用戶

對同一個用戶重複驗證：

```bash
curl -X POST http://localhost:8000/api/v1/auth/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "id": 1,
    "hash": "abc123",
    "expires": 1725616800,
    "signature": "xyz789"
  }'
```

**預期結果：** 200 OK，但訊息顯示「已經驗證過了」

## 🔄 中間件測試

### 測試 Throttle 限制

快速發送多個請求：

```bash
for i in {1..8}; do
  echo "Request $i:"
  curl -w "%{http_code}\n" -o /dev/null -s "http://localhost:8000/api/email/verify/1/abc123?expires=1725616800&signature=xyz789"
  sleep 1
done
```

**預期結果：** 前 6 個請求成功，第 7、8 個返回 429 Too Many Requests

## 📊 測試結果檢查清單

- [ ] 用戶註冊成功
- [ ] 驗證郵件發送成功
- [ ] GET 路由驗證成功
- [ ] POST API 驗證成功
- [ ] 無效簽名被拒絕
- [ ] 過期連結被拒絕
- [ ] 錯誤 hash 被拒絕
- [ ] 不存在用戶被拒絕
- [ ] 重複驗證正確處理
- [ ] Throttle 限制生效
- [ ] 已驗證用戶可以登入

## 🐛 故障排除

### 問題 1：測試失敗

```bash
# 檢查資料庫連接
./vendor/bin/sail artisan migrate:status

# 重新建立測試資料庫
./vendor/bin/sail artisan migrate:fresh --env=testing
```

### 問題 2：郵件未發送

檢查郵件配置：

```bash
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan queue:work
```

### 問題 3：簽名驗證失敗

檢查 APP_KEY 是否設置：

```bash
./vendor/bin/sail artisan key:generate
```

## 📈 效能測試

### 並發測試

```bash
# 使用 ab (Apache Bench) 進行並發測試
ab -n 100 -c 10 "http://localhost:8000/api/email/verify/1/abc123?expires=1725616800&signature=xyz789"
```

### 記憶體使用測試

```bash
# 監控 PHP 記憶體使用
./vendor/bin/sail artisan about
```
