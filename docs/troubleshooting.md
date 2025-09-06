# 錯誤排除指南

## 常見問題與解決方案

### Docker 容器問題

#### 1. Docker 容器啟動失敗

```bash
# 檢查 Docker 是否正在運行
docker --version

# 重新建置容器
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d
```

#### 2. 資料庫連線錯誤

```bash
# 確認資料庫容器運行狀態
./vendor/bin/sail ps

# 重新執行遷移
./vendor/bin/sail artisan migrate:fresh
```

### 認證與 Token 問題

#### 3. JWT Token 無效

```bash
# 重新生成應用程式金鑰
./vendor/bin/sail artisan key:generate

# 清除設定快取
./vendor/bin/sail artisan config:clear
```

#### 4. Sanctum 認證問題

```bash
# 檢查 Sanctum 設定
cat example-app/config/sanctum.php

# 重新發布 Sanctum 配置
./vendor/bin/sail artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

#### 5. 角色隔離登入失敗

```bash
# 檢查用戶角色
./vendor/bin/sail tinker
User::where('username', 'admin')->first()->role;

# 確認使用正確的登入 API
# 普通用戶: POST /api/v1/auth/login
# 管理員: POST /api/v1/auth/admin-login
```

### 測試相關問題

#### 6. 測試失敗 - 速率限制

```bash
# 清除快取並重新執行測試
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear

# 重新執行特定測試
./vendor/bin/sail test tests/Feature/Auth/ForgotPasswordContractTest.php

# 檢查速率限制設定
cat example-app/config/app.php | grep -i throttle
```

#### 7. 測試環境問題

```bash
# 檢查測試環境狀態
./vendor/bin/sail ps

# 重新建置測試環境
./vendor/bin/sail down
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d

# 確認測試資料庫狀態
./vendor/bin/sail artisan migrate:status

# 重新設定測試環境
./vendor/bin/sail artisan migrate:fresh
./vendor/bin/sail artisan db:seed --class=TestSeeder
```

### API 相關問題

#### 8. API 回應格式錯誤

檢查 `app/Http/Controllers/Api/V1/` 中的控制器回應格式，確保符合標準：

```json
{
	"status": "success|error",
	"message": "訊息內容",
	"data": {}
}
```

```bash
# 檢查 API 控制器
ls example-app/app/Http/Controllers/Api/V1/

# 測試 API 回應格式
./vendor/bin/sail test tests/Feature/Auth/LoginContractTest.php
```

#### 9. 路由找不到 (404 錯誤)

```bash
# 檢查路由列表
./vendor/bin/sail artisan route:list

# 清除路由快取
./vendor/bin/sail artisan route:clear

# 重新快取路由
./vendor/bin/sail artisan route:cache
```

### 郵件相關問題

#### 10. 郵件發送問題

```bash
# 檢查 MailHog 界面
# http://localhost:8025

# 檢查郵件設定
cat example-app/.env | grep MAIL

# 測試郵件配置
./vendor/bin/sail artisan tinker
Mail::raw('Test message', function ($message) {
    $message->to('test@example.com')->subject('Test');
});
```

#### 11. Email 驗證功能問題

```bash
# 檢查 Email 驗證設定
cat example-app/.env | grep REQUIRE_EMAIL_VERIFICATION

# 執行 Email 驗證測試
./vendor/bin/sail test tests/Feature/Auth/EmailVerificationTest.php

# 手動測試 Email 驗證
./test_scripts/auth/test_email_verification.sh
```

### 權限與角色問題

#### 12. 管理員權限問題

```bash
# 檢查用戶角色和權限
./vendor/bin/sail tinker
$user = User::find(1);
echo $user->role;
echo $user->isAdmin();

# 重新建立預設管理員
./vendor/bin/sail artisan db:seed --class=AdminSeeder
```

#### 13. API 授權失敗

```bash
# 檢查 Token 是否有效
./vendor/bin/sail tinker
$token = PersonalAccessToken::findToken('your_token_here');
echo $token ? 'Valid' : 'Invalid';

# 檢查中間件設定
cat example-app/routes/api.php | grep middleware
```

### 開發環境問題

#### 14. 快取問題

```bash
# 清除所有快取
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
./vendor/bin/sail artisan view:clear

# 重新產生最佳化檔案
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
```

#### 15. 依賴套件問題

```bash
# 重新安裝 Composer 套件
./vendor/bin/sail composer install

# 更新套件
./vendor/bin/sail composer update

# 檢查套件衝突
./vendor/bin/sail composer diagnose
```

### 資料庫問題

#### 16. 遷移失敗

```bash
# 檢查遷移狀態
./vendor/bin/sail artisan migrate:status

# 回滾遷移
./vendor/bin/sail artisan migrate:rollback

# 重新執行遷移
./vendor/bin/sail artisan migrate

# 完全重建資料庫
./vendor/bin/sail artisan migrate:fresh --seed
```

#### 17. 資料庫連線問題

```bash
# 檢查資料庫配置
cat example-app/.env | grep DB_

# 測試資料庫連線
./vendor/bin/sail artisan tinker
DB::connection()->getPdo();

# 檢查 MySQL 容器日誌
./vendor/bin/sail logs mysql
```

## 效能監控與調試

### 測試執行時間監控

```bash
# 顯示最慢的測試
./vendor/bin/sail test --profile

# 只執行快速測試
./vendor/bin/sail test --testsuite=Unit

# 執行特定標籤的測試
./vendor/bin/sail test --group=auth
```

### 記錄與調試

```bash
# 檢視測試期間的日誌
./vendor/bin/sail logs

# 檢查測試資料庫狀態
./vendor/bin/sail artisan db:show

# 檢查佇列狀態
./vendor/bin/sail artisan queue:work --once

# 即時查看日誌
./vendor/bin/sail logs -f
```

### 診斷工具

```bash
# 檢查系統狀態
./vendor/bin/sail artisan about

# 檢查環境配置
./vendor/bin/sail artisan env

# 檢查路由定義
./vendor/bin/sail artisan route:list --compact

# 檢查事件監聽器
./vendor/bin/sail artisan event:list
```

## 安全問題排除

### CORS 問題

```bash
# 檢查 CORS 設定
cat example-app/config/cors.php

# 測試 CORS 標頭
curl -H "Origin: http://localhost:3000" \
     -H "Access-Control-Request-Method: POST" \
     -H "Access-Control-Request-Headers: X-Requested-With" \
     -X OPTIONS \
     http://localhost/api/v1/auth/login
```

### 安全標頭問題

```bash
# 測試安全標頭
curl -I http://localhost/api/v1/auth/login

# 檢查安全中間件
cat example-app/app/Http/Middleware/SecurityHeaders.php
```

## 完整環境重設

如果遇到嚴重問題，可以完全重設環境：

```bash
# 1. 停止所有服務
./vendor/bin/sail down

# 2. 清除 Docker 資源
docker system prune -a

# 3. 重新建置
./vendor/bin/sail build --no-cache

# 4. 重新啟動
./vendor/bin/sail up -d

# 5. 重新安裝依賴
./vendor/bin/sail composer install

# 6. 重新設定環境
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed

# 7. 清除所有快取
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear

# 8. 執行基本測試驗證
./vendor/bin/sail test tests/Feature/Auth/LoginRoleIsolationTest.php
```
