# 系統架構文檔

## 技術堆疊

- **後端框架**: Laravel 12 (PHP 8.2+)
- **認證系統**: Laravel Sanctum
- **資料庫**: MySQL 8.0
- **開發環境**: Laravel Sail (Docker)
- **測試框架**: PHPUnit
- **郵件測試**: MailHog
- **API 文檔**: Swagger UI

## 專案結構

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
│   │   │   ├── UserValidationRules.php     # 統一驗證規則
│   │   │   ├── ApiResponseFormat.php       # 統一回應格式
│   │   │   ├── UserLoginRequest.php        # 用戶登入驗證
│   │   │   ├── AdminLoginRequest.php       # 管理員登入驗證
│   │   │   └── UserRegistrationRequest.php # 用戶註冊驗證
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
│   │   │   └── LoginRoleIsolationTest.php  # 登入角色隔離測試
│   │   ├── User/                           # 使用者測試
│   │   ├── Admin/                          # 管理員測試
│   │   └── Integration/                    # 整合測試
│   └── Unit/                               # 單元測試
├── routes/
│   └── api.php                             # API 路由定義
├── public/
│   └── swagger-ui/                         # API 文件界面
├── docker-compose.yml                      # Docker 配置
```

## API 集合與文件

- **完整 API 集合**: [insomnia/laravel-api.yaml](../insomnia/laravel-api.yaml) ⭐ 整合版本
- **使用指南**: [insomnia/README.md](../insomnia/README.md)

## 核心路由

### 認證路由

```
POST   /api/v1/auth/register              # 一般註冊
POST   /api/v1/auth/login                 # 一般登入
POST   /api/v1/auth/admin-login           # 管理員專用登入
POST   /api/v1/auth/logout                # 登出
POST   /api/v1/auth/forgot-password       # 忘記密碼
POST   /api/v1/auth/reset-password        # 重設密碼
POST   /api/v1/auth/verify-email          # 郵箱驗證
```

### 用戶管理路由

```
GET    /api/v1/users/profile              # 個人資料
PUT    /api/v1/users/profile              # 更新資料
PUT    /api/v1/users/change-password      # 變更密碼
```

### 管理員路由

```
POST   /api/v1/admin/users                # 創建用戶 (統一 API)
GET    /api/v1/admin/users                # 用戶列表
GET    /api/v1/admin/users/{id}           # 單一用戶詳情
PUT    /api/v1/admin/users/{id}           # 更新用戶
DELETE /api/v1/admin/users/{id}           # 刪除用戶
POST   /api/v1/admin/register             # 管理員註冊用戶 (舊版)
```

## 服務訪問點

- **API 伺服器**: http://localhost
- **API 文件**: http://localhost/swagger-ui/
- **MailHog**: http://localhost:8025
- **MySQL**: localhost:3306

## 安全架構

### 角色基礎存取控制

- **user**: 一般用戶，基本權限
- **admin**: 管理員，管理用戶權限
- **super_admin**: 超級管理員，所有權限

### 登入角色隔離機制

- **用戶登入 API** (`/api/v1/auth/login`) 只允許 `role = 'user'` 的用戶
- **管理員登入 API** (`/api/v1/auth/admin-login`) 只允許 `role = 'admin'` 或 `'super_admin'` 的用戶
- 系統確保用戶和管理員無法互通使用對方的登入 API

### 安全配置

- **密碼強度**: 最少 8 字符，需包含大小寫字母及數字
- **API 速率限制**: 登入 5 次失敗後鎖定 5 分鐘
- **Token 過期**: 24 小時自動過期
- **權限控制**: 基於角色的存取控制 (RBAC)
- **資料驗證**: 所有輸入資料經過嚴格驗證

### Email 驗證控制

透過環境變數控制是否需要 email 驗證：

```bash
# .env 設定
REQUIRE_EMAIL_VERIFICATION=false  # 不需要驗證 (預設: true)
```

- `true`: 一般用戶註冊後需要驗證 email
- `false`: 用戶註冊後直接可登入
- 管理員始終不受此設定影響
