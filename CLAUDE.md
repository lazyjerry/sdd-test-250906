# JDemo Development Guidelines

Auto-generated from all feature plans. Last updated: 2025-09-07

## Active Technologies

- PHP 8.2+ with Laravel 12 (001-laravel12-example-app)
- Laravel Sanctum for API 認證
- Laravel Sail (Docker) for 開發環境
- MySQL 8.0 for 資料儲存
- PHPUnit for 測試
- MailHog for 郵件測試
- Bootstrap 5 for Web 介面
- Swagger UI for API 文檔

## Project Structure

```
example-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── UserController.php
│   │   │   │   └── AdminController.php
│   │   │   └── Web/
│   │   ├── Requests/
│   │   │   ├── CreateAdminUserRequest.php
│   │   │   └── UserUpdateRequest.php
│   │   └── Middleware/
│   ├── Models/
│   │   └── User.php
│   ├── Services/
│   └── Policies/
├── database/
│   ├── migrations/
│   └── seeders/
├── tests/
│   ├── Feature/
│   │   ├── Auth/
│   │   ├── User/
│   │   └── Admin/
│   └── Unit/
├── routes/
│   ├── api.php
│   └── web.php
├── public/
│   └── swagger-ui/
│       ├── index.html
│       └── api-docs.json
└── docs/
    ├── changes/
    ├── laravel-sanctum-guide.md
    └── README.md
```

## Commands

```bash
# 環境管理
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed

# 測試
./vendor/bin/sail test
./vendor/bin/sail test --coverage

# 快取管理
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
```

## Code Style

- 使用中文 PHPDoc 註解
- 遵循 Laravel 標準架構
- 嚴格遵循 TDD 開發流程
- API 回應使用一致的 JSON 格式

## API 設計規範

- 所有 API 端點使用 `/api/v1/` 前綴
- 認證使用 Laravel Sanctum Bearer Token
- 回應格式: `{success: boolean, message: string, data: object}`
- 錯誤格式: `{success: false, message: string, errors: object}`

### API 端點分類

#### 認證相關 (Authentication)
- `POST /api/v1/auth/register` - 用戶註冊
- `POST /api/v1/auth/login` - 用戶登入
- `POST /api/v1/auth/admin-login` - 管理員登入
- `POST /api/v1/auth/logout` - 登出
- `POST /api/v1/auth/forgot-password` - 忘記密碼
- `POST /api/v1/auth/reset-password` - 重設密碼
- `POST /api/v1/auth/verify-email` - 驗證郵箱

#### 用戶管理 (User Profile)
- `GET /api/v1/users/profile` - 獲取個人資料
- `PUT /api/v1/users/profile` - 更新個人資料
- `PUT /api/v1/users/change-password` - 修改密碼

#### 管理員功能 (Admin)
**基本用戶管理:**
- `GET /api/v1/admin/users` - 獲取用戶列表
- `POST /api/v1/admin/users` - 創建新用戶
- `GET /api/v1/admin/users/{id}` - 獲取用戶詳情
- `PUT /api/v1/admin/users/{id}` - 更新用戶資料
- `DELETE /api/v1/admin/users/{id}` - 刪除用戶
- `POST /api/v1/admin/users/{id}/reset-password` - 重設用戶密碼
- `POST /api/v1/admin/users/{id}/deactivate` - 停用用戶
- `POST /api/v1/admin/users/{id}/activate` - 啟用用戶

**批量操作:**
- `POST /api/v1/admin/users/bulk-deactivate` - 批量停用用戶
- `POST /api/v1/admin/users/bulk-activate` - 批量啟用用戶
- `POST /api/v1/admin/users/bulk-update` - 批量更新用戶
- `POST /api/v1/admin/users/bulk-role-change` - 批量角色變更
- `POST /api/v1/admin/users/bulk-delete` - 批量刪除用戶

**統計功能:**
- `GET /api/v1/admin/statistics/users` - 用戶統計資訊
- `GET /api/v1/admin/statistics/system` - 系統統計資訊
- `GET /api/v1/admin/statistics/activity` - 活動統計資訊

**系統管理:**
- `GET /api/v1/admin/system/health` - 系統健康檢查
- `GET /api/v1/admin/audit-log` - 審計日誌
- `GET /api/v1/admin/activity-log` - 活動日誌

## 安全要求

- 密碼最少 8 字符，包含大小寫字母及數字
- API 速率限制: 5 次失敗後鎖定 5 分鐘
- 所有敏感操作需要認證
- Token 24 小時過期

## Recent Changes

- 002-1-admin-2: 擴展管理員功能 - 批量操作、統計、審計日誌 (2025-09-07)
- docs: 建立變更日誌結構和文檔規範 (2025-09-07)
- swagger: 更新 API 文檔，移除概覽保留純 Swagger 介面 (2025-09-07)
- sanctum: 建立 Laravel Sanctum 完整使用指南 (2025-09-07)
- refactor: 郵箱驗證與密碼重設功能 API/Web 分離重構 (2025-09-06)
- 001-laravel12-example-app: Added Laravel 12 使用者管理 API 系統 (2025-09-05)

## Documentation Structure

### 核心文檔
- `docs/laravel-sanctum-guide.md` - Laravel Sanctum 套件完整指南
- `docs/role-based-registration.md` - 角色基礎註冊系統

### 變更記錄
- `docs/changes/` - 專案變更日誌目錄
- `.github/instructions/changelog.instructions.md` - 變更日誌撰寫規範

### API 文檔
- `public/swagger-ui/` - Swagger UI 介面
- `public/swagger-ui/api-docs.json` - OpenAPI 3.0 規格文檔

## Development Features

### 完成的功能模組
- ✅ 用戶註冊與認證系統
- ✅ JWT Token 認證 (Sanctum)
- ✅ 郵箱驗證 (API + Web 介面)
- ✅ 密碼重設 (API + Web 介面)
- ✅ 用戶個人資料管理
- ✅ 管理員用戶管理
- ✅ 管理員批量操作
- ✅ 系統統計與監控
- ✅ 審計日誌功能
- ✅ 系統健康檢查

### 測試覆蓋
- 170+ 個 PHPUnit 測試
- Feature Tests: 認證、用戶管理、管理員功能
- Unit Tests: 模型、服務、工具類別
- 完整的 API 端點測試覆蓋

<!-- MANUAL ADDITIONS START -->
<!-- MANUAL ADDITIONS END -->
