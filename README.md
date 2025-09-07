# Laravel 12 使用者管理 API 系統

基於 Laravel 12 構建的完整 RESTful API 使用者管理系統，提供使用者認證、個人資料管理及管理員功能。採用 Test-Driven Development (TDD) 開發方式，具備高品質的測試覆蓋率。系統支援角色基礎註冊、統一登入體驗、JWT Token 認證、密碼重設與郵箱驗證等核心功能。

## 主要亮點

- **角色基礎認證系統** - 支援多角色登入隔離與權限管理
- **統一登入體驗** - username 或 email 自動識別登入
- **API 與 Web 分離架構** - 獨立的 API 和 Web 端點處理
- **完整郵箱驗證** - 友好的 Web 驗證介面與 API 支援
- **密碼重設功能** - 安全的密碼重設流程
- **Docker 開發環境** - Laravel Sail 完整開發環境
- **Test-Driven Development** - 170 個測試確保程式碼品質

## 系統結構

```
example-app/
├── app/
│   ├── Http/Controllers/
│   │   ├── API/V1/          # API 控制器
│   │   └── Web/             # Web 控制器
│   ├── Services/            # 業務邏輯服務
│   ├── Models/              # Eloquent 模型
│   └── Middleware/          # 中介軟體
├── config/                  # 配置檔案
├── database/migrations/     # 資料庫遷移
├── resources/views/         # Blade 樣板
├── routes/                  # 路由定義
├── tests/                   # 測試檔案
├── test_scripts/            # 手動測試腳本
└── docs/                    # 專案文件
```

**主要元件:**

- Laravel 12 + Sanctum 認證
- MySQL 8.0 資料庫
- Docker 開發環境 (Laravel Sail)
- PHPUnit 測試框架
- Bootstrap 5 Web 介面
- Swagger UI API 文件

## 安裝與啟動

### 快速開始

```bash
# 複製專案並安裝相依套件
git clone <repository-url>
cd JDemo/example-app
composer install

# 設定環境變數
cp .env.example .env
php artisan key:generate

# 啟動 Docker 開發環境
./vendor/bin/sail up -d

# 建立資料庫與預設資料
./vendor/bin/sail artisan migrate --seed
```

**預設管理員帳號:**

- 帳號: `admin`
- 密碼: `admin123`

### 環境變數設定

建立 `.env` 檔案並設定必要的環境變數：

```env
# 資料庫設定
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# 郵件設定
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# 郵箱驗證控制
REQUIRE_EMAIL_VERIFICATION=false
```

## 使用方法

### 開發環境指令

```bash
# 啟動開發伺服器
./vendor/bin/sail up -d

# 停止開發伺服器
./vendor/bin/sail down

# 查看應用程式日誌
./vendor/bin/sail logs

# 進入容器 shell
./vendor/bin/sail shell

# 執行 Artisan 指令
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan cache:clear
```

### API 測試工具

推薦使用 Insomnia 進行 API 測試，完整的 API 集合位於：

- `insomnia/laravel-api.yaml` - Insomnia 匯入檔案

### 基本使用範例

```bash
# 管理員登入 (支援 username 或 email)
curl -X POST http://localhost/api/v1/auth/admin-login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "admin123"}'

# 一般用戶註冊
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "name": "Test User",
    "email": "test@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'

# 一般用戶登入 (支援 username 或 email)
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "testuser", "password": "Password123!"}'

# 郵箱驗證 (Web 介面)
# 開啟瀏覽器訪問：http://localhost/email/verify/{id}/{hash}?expires={timestamp}&signature={signature}
```

## 測試

本專案採用 Test-Driven Development (TDD) 方法開發，提供完整的測試套件，包含自動化測試和手動測試腳本。

### 測試類型

- **自動化測試**: 使用 PHPUnit 的完整測試套件 (170 個測試)
- **手動測試**: 互動式測試腳本 (`test_scripts/`)
- **整合測試**: 端到端功能驗證
- **Web 介面測試**: Bootstrap 5 使用者介面測試

### 執行測試

```bash
# 執行所有測試
./vendor/bin/sail test

# 執行特定功能模組測試
./vendor/bin/sail test tests/Feature/Auth/           # 認證功能測試
./vendor/bin/sail test tests/Feature/User/          # 用戶功能測試
./vendor/bin/sail test tests/Feature/Admin/         # 管理員功能測試
./vendor/bin/sail test tests/Feature/Web/           # Web 介面測試

# 執行特定測試檔案
./vendor/bin/sail test tests/Feature/Auth/EmailVerificationTest.php
./vendor/bin/sail test tests/Feature/Web/EmailVerificationTest.php
./vendor/bin/sail test tests/Unit/Services/EmailVerificationServiceTest.php

# 執行特定測試方法
./vendor/bin/sail test --filter=user_can_verify_email_via_web_route
./vendor/bin/sail test --filter=user_can_verify_email_via_api
./vendor/bin/sail test --filter=email_verification_service_works_correctly

# 查看測試覆蓋率
./vendor/bin/sail test --coverage
```

### 手動測試

```bash
# 郵箱驗證手動測試
./test_scripts/auth/test_email_verification.sh

# 查看所有手動測試腳本
ls test_scripts/*/

# 查看測試腳本說明文件
cat test_scripts/README.md
cat test_scripts/auth/README.md
```

### 測試統計

**最新測試結果:**

- **總測試數**: 170 個測試，包含 API、Web 介面和服務層測試
- **郵箱驗證測試**: 19 個測試，66 個斷言，全部通過 ✅
  - EmailVerificationServiceTest: 8 個測試，24 個斷言
  - Web EmailVerificationTest: 6 個測試，26 個斷言
  - API EmailVerificationTest: 5 個測試，16 個斷言
- **密碼重設測試**: 完整的 API 和 Web 流程測試
- **認證系統測試**: 角色隔離和權限控制測試

**重要測試成果:**

- ✅ **API 與 Web 分離**: 郵箱驗證和密碼重設功能完全分離
- ✅ **共用服務架構**: EmailVerificationService 和 PasswordResetService
- ✅ **Bootstrap 5 介面**: 友好的 Web 驗證結果頁面
- ✅ **簽名驗證**: Laravel 內建的安全驗證機制

## 使用情境

### 角色基礎認證系統

系統支援三種角色：`user`、`admin`、`super_admin`，並實作嚴格的登入角色隔離：

- **一般用戶** 使用 `/api/v1/auth/login`
- **管理員** 使用 `/api/v1/auth/admin-login`
- **角色隔離** 防止跨角色登入攻擊

### 統一登入體驗

所有用戶都支援使用 username 或 email 登入，系統自動識別輸入類型：

```bash
# 兩種方式都有效
{"username": "admin"}                    # 使用 username
{"username": "admin@example.com"}        # 使用 email
```

### API 與 Web 分離架構

系統採用清晰的架構分離，提供不同的使用者體驗：

**API 端點:**

- `/api/v1/auth/verify-email` - JSON 回應，適合前端 SPA
- 標準化的錯誤代碼和訊息格式

**Web 端點:**

- `/email/verify/{id}/{hash}` - Bootstrap 5 友好介面
- 自動跳轉和使用者提示功能

### 郵箱驗證功能

提供完整的郵箱驗證流程，支援 API 和 Web 兩種介面：

**Web 驗證體驗:**

- 點擊郵件連結直接在瀏覽器中完成驗證
- Bootstrap 5 設計的友好結果頁面
- 驗證成功自動跳轉到登入頁面
- 清晰的錯誤提示和解決方案

**API 驗證支援:**

- RESTful API 端點提供 JSON 回應
- 支援 AJAX 請求和前端框架整合
- 標準化的錯誤代碼便於程式處理

### 密碼重設功能

採用相同的分離架構，提供安全的密碼重設流程：

- **共用服務邏輯** - PasswordResetService 處理核心業務邏輯
- **Web 友好介面** - Bootstrap 5 重設結果頁面
- **API 標準化** - JSON 格式的 API 回應

### 環境變數控制

透過環境變數靈活控制系統行為：

```env
# 郵箱驗證控制
REQUIRE_EMAIL_VERIFICATION=true   # 需要驗證
REQUIRE_EMAIL_VERIFICATION=false  # 不需要驗證

# 郵件設定
MAIL_MAILER=log                   # 開發環境使用 log
MAIL_FROM_ADDRESS="noreply@example.com"
```

## 錯誤排除

### 常見問題

```bash
# Docker 容器啟動問題
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d

# 資料庫連線問題
./vendor/bin/sail artisan migrate:fresh --seed

# 清除應用程式快取
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear

# 檢查容器狀態
./vendor/bin/sail ps

# 查看應用程式日誌
./vendor/bin/sail logs
```

### 測試相關問題

```bash
# 測試資料庫重設
./vendor/bin/sail artisan migrate:fresh --env=testing

# 清除測試快取
./vendor/bin/sail artisan cache:clear --env=testing

# 執行特定失敗的測試
./vendor/bin/sail test --filter=failing_test_name

# 顯示詳細測試錯誤
./vendor/bin/sail test --verbose --stop-on-failure
```

### 郵箱驗證問題

如果郵箱驗證功能異常：

1. **檢查環境變數設定**:

   ```env
   REQUIRE_EMAIL_VERIFICATION=false  # 開發環境建議關閉
   MAIL_MAILER=log                   # 開發環境使用 log
   ```

2. **檢查路由註冊**:

   ```bash
   ./vendor/bin/sail artisan route:list | grep verify
   ```

3. **測試 Web 驗證介面**:
   ```bash
   ./test_scripts/auth/test_email_verification.sh
   ```

### 權限相關問題

```bash
# 檢查檔案權限
sudo chown -R $USER:$USER .
chmod -R 755 storage bootstrap/cache

# 重新產生應用程式金鑰
./vendor/bin/sail artisan key:generate
```

## 授權條款

本專案採用 MIT 授權條款。詳見 [LICENSE](LICENSE) 檔案。

## 文件索引

### 核心文件

- [Laravel Sanctum 完整指南](docs/laravel-sanctum-guide.md) - Sanctum 驗證套件詳細說明
- [角色基礎註冊系統](docs/role-based-registration.md) - 角色註冊機制說明

### 變更記錄

- [變更記錄索引](docs/changes/README.md) - 所有專案變更與重構文件
- [郵箱驗證功能重構](docs/changes/email-verification-refactor.md) - API 與 Web 分離重構
- [密碼重設功能重構](docs/changes/password-reset-architecture-refactor.md) - 密碼重設架構改進
- [數據庫索引優化](docs/changes/database-indexes-summary.md) - 數據庫效能優化記錄

### 系統架構

- [系統架構文件](docs/system-architecture.md) - 技術堆疊與專案結構

### 安裝與使用

- [安裝與啟動指南](docs/installation.md) - 完整安裝步驟
- [API 使用指南](docs/api-usage.md) - API 端點與使用範例

### 測試與品質

- [測試指南](docs/testing.md) - 自動化與手動測試
- [錯誤排除指南](docs/troubleshooting.md) - 常見問題解決方案

### API 資源

- [Insomnia API 集合](insomnia/) - 完整 API 測試集合
- [手動測試腳本](test_scripts/) - 互動式測試工具

---

**專案狀態**: 郵箱驗證與密碼重設功能已完成 API 與 Web 分離重構，提供優秀的使用者體驗和開發者友好的架構。
