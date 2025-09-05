# Research & Technical Decisions

## 需求澄清解決方案

### FR-021: 密碼複雜度要求

**Decision**: 最小 8 字符，包含至少 1 個大寫字母、1 個小寫字母、1 個數字
**Rationale**: Laravel 內建驗證規則平衡安全性和使用性
**Alternatives considered**: 更複雜的規則 (拒絕 - 使用者體驗差)

### FR-022: 會話管理

**Decision**: Laravel Sanctum Token-based 認證，Token 24 小時過期
**Rationale**: 適合 API-only 架構，支援 SPA 和行動應用
**Alternatives considered**: Session-based (拒絕 - 不適合 API), JWT (拒絕 - 複雜度較高)

### FR-023: 暴力破解防護

**Decision**: Laravel Rate Limiting - 5 次失敗後鎖定 5 分鐘
**Rationale**: Laravel 內建功能，簡單有效
**Alternatives considered**: CAPTCHA (拒絕 - API-only), 硬體解決方案 (拒絕 - 過度複雜)

### FR-024: 安全事件記錄

**Decision**: 記錄登入失敗、密碼重設、帳戶鎖定事件，保存 30 天
**Rationale**: 符合基本安全稽核需求
**Alternatives considered**: 完整行為日誌 (拒絕 - 隱私考量)

## 技術選擇研究

### Laravel 12 最佳實踐

**Decision**: 使用 Laravel 12 LTS 標準架構
**Rationale**:

- 長期支援版本 (3 年)
- 內建完整的認證、郵件、測試功能
- 社群支援完整
  **Key Features Used**:
- Eloquent ORM for 資料模型
- Sanctum for API 認證
- Mail for 郵件功能
- Validation for 資料驗證
- PHPUnit for 測試

### API 設計模式

**Decision**: RESTful API with 標準 HTTP 動詞
**Rationale**: 符合使用者要求，業界標準
**Endpoints Pattern**:

```
GET    /api/users          # 列出使用者 (管理員)
POST   /api/users          # 註冊新使用者
GET    /api/users/{id}     # 取得使用者資料
PUT    /api/users/{id}     # 更新使用者資料
DELETE /api/users/{id}     # 刪除使用者 (管理員)
POST   /api/auth/login     # 登入
POST   /api/auth/logout    # 登出
POST   /api/auth/forgot    # 忘記密碼
POST   /api/auth/reset     # 重設密碼
POST   /api/auth/verify    # 驗證信箱
```

### 資料庫設計

**Decision**: MySQL 8.0 with Laravel Migrations
**Rationale**:

- Laravel Sail 預設支援
- 完善的關聯式資料庫功能
- UTF8MB4 支援中文
  **Tables**:
- users: 一般使用者
- sys_users: 系統管理員
- personal_access_tokens: API tokens (Sanctum)
- password_reset_tokens: 密碼重設
- email_verifications: 信箱驗證

### 測試策略

**Decision**: Laravel Feature Tests + Unit Tests
**Rationale**:

- Feature Tests 測試完整 HTTP 請求/回應流程
- Unit Tests 測試個別 Service 邏輯
- 使用真實資料庫 (SQLite in-memory for tests)
  **Test Types**:

1. Contract Tests: API endpoint 結構測試
2. Integration Tests: 完整使用者流程測試
3. Unit Tests: Service 類別邏輯測試

### 郵件配置

**Decision**: Laravel Mail with SMTP 設定於 .env
**Rationale**: 靈活性高，可輕易切換 SMTP 提供商
**Configuration**:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 權限驗證架構

**Decision**: Laravel Policies + Middleware
**Rationale**: Laravel 標準方式，可測試性高
**Components**:

- Middleware: 檢查認證狀態
- Policies: 檢查操作權限 (使用者只能修改自己資料，管理員可修改所有)
- Gates: 系統管理員權限檢查

## 開發環境設定

**Decision**: Laravel Sail (Docker)
**Rationale**:

- 標準化開發環境
- 包含 MySQL, Redis, MailHog
- 簡化部署流程
  **Services**:
- PHP 8.2
- MySQL 8.0
- Redis (for queues/cache)
- MailHog (email testing)

## 文件化策略

**Decision**: 中文 PHPDoc 註解 + OpenAPI 規格
**Rationale**: 符合使用者要求，提供完整的 API 文件
**Format**:

```php
/**
 * 建立新的使用者帳戶
 *
 * @param UserRegistrationRequest $request 使用者註冊資料
 * @return JsonResponse 註冊結果
 * @throws ValidationException 當資料驗證失敗時
 */
```

## 部署考量

**Decision**: Docker 容器化部署
**Rationale**:

- 與開發環境一致
- 可擴展性
- 簡化 CI/CD
  **Requirements**:
- Docker & Docker Compose
- SSL/TLS 憑證
- 反向代理 (Nginx)
