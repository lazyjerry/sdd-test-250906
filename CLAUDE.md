# JDemo Development Guidelines

Auto-generated from all feature plans. Last updated: 2025-09-05

## Active Technologies

- PHP 8.2+ with Laravel 12 (001-laravel12-example-app)
- Laravel Sanctum for API 認證
- Laravel Sail (Docker) for 開發環境
- MySQL 8.0 for 資料儲存
- PHPUnit for 測試
- MailHog for 郵件測試

## Project Structure

```
example-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── Admin/
│   │   │   └── User/
│   │   ├── Requests/
│   │   └── Middleware/
│   ├── Models/
│   ├── Services/
│   └── Policies/
├── database/
│   ├── migrations/
│   └── seeders/
├── tests/
│   ├── Feature/
│   └── Unit/
└── routes/
    └── api.php
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

## 安全要求

- 密碼最少 8 字符，包含大小寫字母及數字
- API 速率限制: 5 次失敗後鎖定 5 分鐘
- 所有敏感操作需要認證
- Token 24 小時過期

## Recent Changes

- 001-laravel12-example-app: Added Laravel 12 使用者管理 API 系統 (2025-09-05)

<!-- MANUAL ADDITIONS START -->
<!-- MANUAL ADDITIONS END -->
