# Laravel12 Example-App 專案憲法

<!-- Laravel12 使用者管理 API 系統開發規範與核心原則 -->

## Core Principles

### I. Service 優先原則

每個功能都從獨立的 Laravel Service 類別開始。Service 必須是自包含的、可獨立測試的、有完整 PHPDoc 中文文件記錄的。每個 Service 需要明確的業務目的 - 不允許僅為組織程式碼而存在的 Service。

**核心 Service 類別**：

- **AuthService**: 處理使用者認證、登入、登出、密碼重設
- **UserService**: 管理使用者資料 CRUD、個人資料更新
- **MailService**: 處理郵件發送、信箱驗證、密碼重設連結
- **AdminService**: 系統管理員功能、使用者管理

### II. Artisan 命令介面

每個 Service 都透過 Laravel Artisan 命令公開核心功能。遵循 Laravel 命令協議：參數輸入 → 標準輸出，錯誤 → 錯誤輸出。支援 `--format=json` 和人類可讀格式回應。

**必要命令**：

- `php artisan user:create {username} {email} --password=`
- `php artisan user:verify {email} --send-email`
- `php artisan admin:manage-user {user_id} --action=reset-password`
- `php artisan mail:test --to={email}`

### III. 測試優先原則 (不可協商)

TDD 強制執行：撰寫測試 → 確認測試失敗 (RED) → 實作功能 → 測試通過 (GREEN) → 重構 (REFACTOR)。嚴格執行紅-綠-重構循環。使用 Laravel PHPUnit 與真實資料庫進行測試。

**測試順序強制**：

1. **Contract Tests**: API 端點結構驗證
2. **Integration Tests**: 完整使用者流程測試
3. **Feature Tests**: HTTP 請求/回應測試
4. **Unit Tests**: Service 類別邏輯測試

### IV. 整合測試原則

以下領域必須有整合測試：新 API 端點合約測試、Laravel Eloquent Model 關聯變更、Service 間通訊測試、Sanctum 認證流程、郵件服務整合、資料庫遷移驗證。

**重點測試場景**：

- 使用者註冊 → 信箱驗證 → 登入完整流程
- 管理員操作一般使用者資料權限驗證
- 密碼重設郵件發送與驗證流程
- API 權限驗證與 Token 管理

### V. 可觀測性與中文文件

Laravel Log 確保可除錯性，需要結構化日誌記錄。所有 PHP 類別都必須有完整的中文 PHPDoc 註解。API 端點必須有 OpenAPI 3.0 規格文件。

**文件要求**：

- 所有 Service 方法都要有中文 PHPDoc
- Controller 動作說明用途與參數
- Model 屬性與關聯關係註解
- API 錯誤回應格式標準化

## 技術棧要求與安全標準

**核心技術棧**：

- **PHP 8.2+** 與 **Laravel 12 LTS**
- **Laravel Sail** 開發環境 (Docker + MySQL 8.0 + Redis + MailHog)
- **Laravel Sanctum** API 認證與 Token 管理
- **Laravel Mail** SMTP 郵件服務配置
- **PHPUnit** 測試框架與 Laravel Testing

**安全要求**：

- 密碼最小 8 字符，包含大小寫字母與數字
- Laravel Rate Limiting: 5 次登入失敗後鎖定 5 分鐘
- Sanctum Token 24 小時過期機制
- bcrypt 密碼雜湊，安全 API Token 生成
- SMTP 郵件驗證防止偽造帳戶

**API 設計規範**：

- 純 RESTful API 結構：GET / POST / PUT / DELETE
- 標準 HTTP 狀態碼與 JSON 回應格式
- OpenAPI 3.0 規格文件維護
- API 版本控制策略 (v1 prefix)
- Laravel API Resources 統一回應格式

## Laravel 開發工作流程與品質關卡

**程式碼審查要求**：

- 所有 PHP 類別都必須有中文 PHPDoc 註解
- Laravel Eloquent Model 關聯與驗證規則完整性
- Controller 方法遵循單一職責原則
- Service 類別必須可獨立測試與依賴注入

**測試覆蓋率關卡**：

- Feature Tests: 所有 API 端點 100% 覆蓋
- Unit Tests: Service 類別邏輯 80% 以上覆蓋
- Integration Tests: 使用者流程與權限驗證
- Contract Tests: API 結構驗證必須先失敗

**部署與版本控制**：

- 遵循語義化版本 MAJOR.MINOR.PATCH
- Laravel Migration 與 Seeder 版本追蹤
- .env 環境變數完整配置範例
- Docker Compose 生產環境設定

**Laravel 最佳實踐**：

- Eloquent ORM 優於原生 SQL 查詢
- Laravel Form Request 統一資料驗證
- Laravel Policy 處理授權邏輯
- Laravel Queue 處理非同步郵件發送

## Governance

**憲法權威性**：此憲法優先於所有其他開發實踐與個人偏好。修正案需要完整文件記錄、團隊核准、遷移計劃。Laravel 社群標準與 PSR 規範必須遵循。

**合規驗證**：

- 所有 Pull Request 都必須驗證憲法合規性
- 複雜設計決策必須有充分理由與替代方案分析
- 使用 `CLAUDE.md` 檔案指引 AI 輔助開發
- 遵循 Laravel 編碼標準與 PhpStorm/VS Code 設定

**專案治理**：

- TDD 原則不可妥協，必須先寫測試
- Service 類別設計必須經過 Architecture Review
- API 端點變更需要向下相容性評估
- 資料庫 Schema 變更需要 Migration 策略

**Version**: 1.0.0 | **Ratified**: 2025-09-05 | **Last Amended**: 2025-09-05
