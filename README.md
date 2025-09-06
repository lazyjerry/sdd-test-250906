# Laravel 12 使用者管理 API 系統

基於 Laravel 12 構建的完整 RESTful API 使用者管理系統，提供使用者認證、個人資料管理及管理員功能。採用 Test-Driven Development (TDD) 開發方式，具備高品質的測試覆蓋率。

## 主要功能

- 使用者註冊、登入、登出
- JWT Token 認證 (Laravel Sanctum)
- 密碼重設與郵箱驗證
- 個人資料管理
- 管理員使用者管理功能
- API 速率限制與安全防護
- Swagger UI API 文件

## 系統架構

### 技術堆疊

- **後端框架**: Laravel 12 (PHP 8.2+)
- **認證系統**: Laravel Sanctum
- **資料庫**: MySQL 8.0
- **開發環境**: Laravel Sail (Docker)
- **測試框架**: PHPUnit
- **郵件測試**: MailHog
- **API 文件**: Swagger UI

### 專案結構

```
example-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/V1/
│   │   │       ├── AuthController.php      # 認證相關
│   │   │       ├── UserController.php      # 使用者資料管理
│   │   │       └── AdminController.php     # 管理員功能
│   │   ├── Requests/                        # 表單驗證請求
│   │   └── Middleware/                      # 中介軟體
│   ├── Models/
│   │   └── User.php                         # 使用者模型
│   └── Services/                            # 業務邏輯服務
├── database/
│   ├── migrations/                          # 資料庫遷移
│   └── seeders/                            # 資料填充
├── tests/
│   ├── Feature/                            # 功能測試
│   │   ├── Auth/                           # 認證測試
│   │   ├── User/                           # 使用者測試
│   │   ├── Admin/                          # 管理員測試
│   │   └── Integration/                    # 整合測試
│   └── Unit/                               # 單元測試
├── routes/
│   └── api.php                             # API 路由定義
├── public/
│   └── swagger-ui/                         # API 文件界面
├── docker-compose.yml                      # Docker 配置
└── .env.example                           # 環境變數範本
```

## 安裝與啟動

### 前置要求

- PHP 8.2 或更高版本
- Composer
- Docker & Docker Compose
- Git

### 安裝步驟

1. **複製專案**

```bash
git clone <repository-url>
cd JDemo/example-app
```

2. **安裝相依套件**

```bash
composer install
```

3. **設定環境變數**

```bash
cp .env.example .env
php artisan key:generate
```

4. **配置環境變數**
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
```

5. **啟動開發環境**

```bash
./vendor/bin/sail up -d
```

6. **執行資料庫遷移**

```bash
./vendor/bin/sail artisan migrate
```

7. **填充測試資料（可選）**

```bash
./vendor/bin/sail artisan db:seed
```

### 服務訪問點

- **API 伺服器**: http://localhost
- **API 文件**: http://localhost/swagger-ui/
- **MailHog**: http://localhost:8025
- **MySQL**: localhost:3306

## 🧪 測試

本專案採用 Test-Driven Development (TDD) 方法開發，提供完整的測試套件，包含自動化測試和手動測試腳本。

### � 測試類型

- **🔧 自動化測試**: 使用 PHPUnit 的完整測試套件 (139 個測試)
- **🖱️ 手動測試**: 互動式測試腳本 (`test_scripts/`)
- **🔗 整合測試**: 端到端功能驗證
- **📊 效能測試**: API 回應時間和併發測試

### �🚀 自動化測試指令

#### 基本測試指令

```bash
# 執行所有測試
./vendor/bin/sail test

# 執行特定功能模組測試
./vendor/bin/sail test tests/Feature/Auth/           # 認證功能測試
./vendor/bin/sail test tests/Feature/User/          # 用戶功能測試
./vendor/bin/sail test tests/Feature/Admin/         # 管理員功能測試
./vendor/bin/sail test tests/Feature/Integration/   # 整合測試

# 查看測試覆蓋率
./vendor/bin/sail test --coverage

# 顯示詳細測試輸出
./vendor/bin/sail test --verbose

# 停止在第一個失敗的測試
./vendor/bin/sail test --stop-on-failure
```

#### 特定功能測試

```bash
# 郵箱驗證測試
./vendor/bin/sail test tests/Feature/Auth/EmailVerificationTest.php

# 登入功能測試
./vendor/bin/sail test tests/Feature/Auth/LoginContractTest.php

# 密碼重設測試
./vendor/bin/sail test tests/Feature/Auth/ForgotPasswordContractTest.php

# 用戶資料管理測試
./vendor/bin/sail test tests/Feature/User/ProfileTest.php

# 管理員功能測試
./vendor/bin/sail test tests/Feature/Admin/UserManagementTest.php
```

#### 單一測試方法

```bash
# 測試特定的測試方法
./vendor/bin/sail test --filter=user_can_verify_email_via_post_api
./vendor/bin/sail test --filter=user_can_verify_email_via_get_route
./vendor/bin/sail test --filter=email_verification_fails_with_invalid_signature
./vendor/bin/sail test --filter=user_can_login_with_valid_credentials
./vendor/bin/sail test --filter=admin_can_get_users_list
```

### 🖱️ 手動測試指令

```bash
# 認證功能手動測試
./test_scripts/auth/test_email_verification.sh

# 查看所有可用的測試腳本
ls test_scripts/

# 查看測試腳本使用說明
cat test_scripts/README.md
cat test_scripts/auth/README.md
cat test_scripts/user/README.md
cat test_scripts/admin/README.md
cat test_scripts/integration/README.md
```

### 📊 測試統計

- **總測試數**: 139 個測試
- **通過率**: 99.2% (138/139 通過)
- **測試覆蓋模組**:
  - 認證功能: 33 個測試
  - 用戶管理: 18 個測試
  - 管理員功能: 34 個測試
  - 整合測試: 53 個測試

### 📁 測試資源

- **測試腳本目錄**: [`test_scripts/`](test_scripts/) - 包含手動測試腳本和指南
- **自動化測試**: [`example-app/tests/`](example-app/tests/) - PHPUnit 測試套件
- **測試說明**: [`test_scripts/README.md`](test_scripts/README.md) - 測試腳本使用指南

## 使用方法

### 常用指令

```bash
# 啟動開發環境
./vendor/bin/sail up -d

# 停止開發環境
./vendor/bin/sail down

# 資料庫遷移
./vendor/bin/sail artisan migrate

# 填充測試資料
./vendor/bin/sail artisan db:seed

# 清除快取
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear

# 程式碼格式化
./vendor/bin/sail composer pint
```

### API 使用範例

## 郵箱驗證功能

### 🔧 自動化測試

郵箱驗證功能包含完整的自動化測試套件，涵蓋所有主要功能和錯誤情況：

```bash
# 執行完整的郵箱驗證測試套件
./vendor/bin/sail test tests/Feature/Auth/EmailVerificationTest.php --verbose
```

### 📋 測試涵蓋範圍

- ✅ **POST API 驗證**: 測試 `/api/v1/auth/verify-email` 端點
- ✅ **GET 路由驗證**: 測試 `/api/email/verify/{id}/{hash}` 端點
- ✅ **無效簽名處理**: 驗證簽名驗證機制
- ✅ **過期連結處理**: 測試時間戳驗證
- ✅ **錯誤 Hash 處理**: 測試 Hash 比對邏輯
- ✅ **重複驗證處理**: 測試已驗證用戶的處理
- ✅ **中間件功能**: 測試 `signed` 和 `throttle` 中間件
- ✅ **多用戶角色**: 測試不同角色用戶的驗證

### 🖱️ 手動測試

如需進行手動測試或整合測試，請使用提供的測試腳本：

```bash
# 執行手動測試腳本
./test_scripts/auth/test_email_verification.sh

# 查看詳細的手動測試指南
cat test_scripts/auth/EMAIL_VERIFICATION_TESTING_GUIDE.md
```

### ⚠️ RESTful API 設計注意事項

**重要**: GET 路由的郵箱驗證違反了 RESTful API 設計原則：

- **冪等性違反**: GET 請求修改了伺服器狀態
- **安全性違反**: GET 請求執行了狀態變更操作
- **語意不明確**: GET 通常表示獲取資源，但此處執行驗證動作

建議的最佳實踐：

- 生產環境優先使用 `POST /api/v1/auth/verify-email`
- GET 路由僅用於向後相容和使用者便利性
- 前端應用應調用 POST API 而非直接使用 GET 連結

#### 1. 使用者註冊

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

#### 2. 使用者登入

```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "Password123!"
  }'
```

#### 3. 獲取個人資料

```bash
curl -X GET http://localhost/api/v1/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### 4. 管理員獲取使用者列表

```bash
curl -X GET "http://localhost/api/v1/admin/users?search=test&role=user&per_page=10" \
  -H "Authorization: Bearer ADMIN_TOKEN_HERE"
```

## API 端點

### 認證 API (`/api/v1/auth/`)

- `POST /register` - 使用者註冊
- `POST /login` - 使用者登入
- `POST /logout` - 使用者登出
- `POST /forgot-password` - 忘記密碼
- `POST /reset-password` - 重設密碼
- `POST /verify-email` - 郵箱驗證

### 使用者 API (`/api/v1/users/`)

- `GET /profile` - 獲取個人資料
- `PUT /profile` - 更新個人資料
- `PUT /change-password` - 修改密碼

### 管理員 API (`/api/v1/admin/`)

- `GET /users` - 獲取使用者列表（支援搜尋、過濾、分頁）
- `GET /users/{id}` - 獲取使用者詳情
- `PUT /users/{id}` - 更新使用者資料
- `POST /users/{id}/reset-password` - 重設使用者密碼

詳細的 API 文件請訪問：http://localhost/swagger-ui/

## 錯誤排除

### 常見問題與解決方案

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

#### 3. 測試失敗 - 速率限制

```bash
# 清除快取並重新執行測試
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail test tests/Feature/Auth/ForgotPasswordContractTest.php
```

#### 4. JWT Token 無效

```bash
# 重新生成應用程式金鑰
./vendor/bin/sail artisan key:generate

# 清除設定快取
./vendor/bin/sail artisan config:clear
```

#### 5. 郵件發送問題

檢查 MailHog 界面：http://localhost:8025

#### 6. API 回應格式錯誤

檢查 `app/Http/Controllers/Api/V1/` 中的控制器回應格式，確保符合標準：

```json
{
	"status": "success|error",
	"message": "訊息內容",
	"data": {}
}
```

## 安全配置

- **密碼強度**: 最少 8 字符，需包含大小寫字母及數字
- **API 速率限制**: 登入 5 次失敗後鎖定 5 分鐘
- **Token 過期**: 24 小時自動過期
- **權限控制**: 基於角色的存取控制 (RBAC)
- **資料驗證**: 所有輸入資料經過嚴格驗證

## 授權條款

本專案採用 MIT 授權條款。詳見 [composer.json](example-app/composer.json) 中的授權宣告。

```
MIT License

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```
