# 安裝與啟動指南

## 前置要求

- PHP 8.2 或更高版本
- Composer
- Docker & Docker Compose
- Git

## 安裝步驟

### 1. 複製專案

```bash
git clone <repository-url>
cd JDemo/example-app
```

### 2. 安裝相依套件

```bash
composer install
```

### 3. 設定環境變數

```bash
cp .env.example .env
php artisan key:generate
```

### 4. 配置環境變數

編輯 `.env` 檔案，確認以下設定：

```bash
APP_NAME="Laravel API"
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

# Email 驗證設定
REQUIRE_EMAIL_VERIFICATION=true   # 需要驗證
# REQUIRE_EMAIL_VERIFICATION=false  # 不需要驗證
```

### 5. 啟動開發環境

```bash
./vendor/bin/sail up -d
```

### 6. 執行資料庫遷移

```bash
./vendor/bin/sail artisan migrate
```

### 7. 填充預設資料

```bash
./vendor/bin/sail artisan db:seed
```

⚠️ **重要**: 系統會自動創建預設管理員帳號

- **帳號**: `admin`
- **密碼**: `admin123`
- **請在生產環境中立即更改此密碼！**

## 服務訪問點

- **API 伺服器**: http://localhost
- **API 文件**: http://localhost/swagger-ui/
- **MailHog**: http://localhost:8025
- **MySQL**: localhost:3306

## 常用指令

```bash
# 啟動開發環境
./vendor/bin/sail up -d

# 停止開發環境
./vendor/bin/sail down

# 資料庫遷移
./vendor/bin/sail artisan migrate

# 重新建立資料庫
./vendor/bin/sail artisan migrate:fresh --seed

# 填充測試資料
./vendor/bin/sail artisan db:seed

# 清除快取
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear

# 程式碼格式化
./vendor/bin/sail composer pint
```

## 管理員系統設定

### 預設管理員帳號

系統初始化時會自動創建預設管理員：

- **用戶名**: `admin`
- **密碼**: `admin123`
- **角色**: `super_admin`
- **權限**: 所有系統權限

### Email 驗證控制

透過環境變數控制是否需要 email 驗證：

```bash
# .env 設定
REQUIRE_EMAIL_VERIFICATION=false  # 不需要驗證 (預設: true)
```

- `true`: 一般用戶註冊後需要驗證 email
- `false`: 用戶註冊後直接可登入
- 管理員始終不受此設定影響

## 驗證安裝

### 1. 檢查服務狀態

```bash
./vendor/bin/sail ps
```

### 2. 測試 API 連線

```bash
curl http://localhost/api/v1/auth/admin-login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "admin123"}'
```

### 3. 執行基本測試

```bash
./vendor/bin/sail test tests/Feature/Auth/LoginRoleIsolationTest.php
```

## 開發環境重設

如果遇到問題，可以重設整個開發環境：

```bash
# 停止所有服務
./vendor/bin/sail down

# 重新建置容器
./vendor/bin/sail build --no-cache

# 重新啟動
./vendor/bin/sail up -d

# 重新建立資料庫
./vendor/bin/sail artisan migrate:fresh --seed

# 清除所有快取
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
```
