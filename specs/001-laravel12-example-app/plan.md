# Implementation Plan: Laravel12 Example-App 後端 API 系統

**Branch**: `001-laravel12-example-app` | **Date**: 2025 年 9 月 5 日 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/Users/lazyjerry/Dropbox/個人project/個人用專案/laravel/JDemo/specs/001-laravel12-example-app/spec.md`

## Execution Flow (/plan command scope)

```
1. Load feature spec from Input path
   → If not found: ERROR "No feature spec at {path}"
2. Fill Technical Context (scan for NEEDS CLARIFICATION)
   → Detect Project Type from context (web=frontend+backend, mobile=app+api)
   → Set Structure Decision based on project type
3. Evaluate Constitution Check section below
   → If violations exist: Document in Complexity Tracking
   → If no justification possible: ERROR "Simplify approach first"
   → Update Progress Tracking: Initial Constitution Check
4. Execute Phase 0 → research.md
   → If NEEDS CLARIFICATION remain: ERROR "Resolve unknowns"
5. Execute Phase 1 → contracts, data-model.md, quickstart.md, agent-specific template file (e.g., `CLAUDE.md` for Claude Code, `.github/copilot-instructions.md` for GitHub Copilot, or `GEMINI.md` for Gemini CLI).
6. Re-evaluate Constitution Check section
   → If new violations: Refactor design, return to Phase 1
   → Update Progress Tracking: Post-Design Constitution Check
7. Plan Phase 2 → Describe task generation approach (DO NOT create tasks.md)
8. STOP - Ready for /tasks command
```

**IMPORTANT**: The /plan command STOPS at step 7. Phases 2-4 are executed by other commands:

- Phase 2: /tasks command creates tasks.md ✅ **COMPLETED**
- Phase 3-4: Implementation execution (manual or via tools)

## Summary

建立 Laravel12 專案 "example-app"，實現前後端分離的 RESTful API 後端系統。核心功能包括：使用者註冊/登入/密碼重設、信箱驗證、個人資料管理，以及系統管理員功能（管理一般使用者）。使用 Laravel Sail 建立測試環境，實施完整的 PHPUnit 測試覆蓋，並確保 API 權限驗證。

## Technical Context

**Language/Version**: PHP 8.2+ with Laravel 12  
**Primary Dependencies**: Laravel 12, Laravel Sail, Laravel Sanctum (API 認證), Laravel Mail (SMTP)  
**Storage**: MySQL 8.0 (透過 Sail)  
**Testing**: PHPUnit (Laravel 內建), Feature Tests, Unit Tests  
**Target Platform**: Docker 容器 (Laravel Sail), Linux server  
**Project Type**: Web (API-only backend)  
**Performance Goals**: 標準 REST API 響應時間 <200ms  
**Constraints**: 純 API 結構 (GET/POST/PUT/DELETE), SMTP 預設配置, .env 環境變數配置  
**Scale/Scope**: 中小型使用者管理系統, 雙重使用者類型 (users + sys_users)

### 來自使用者的額外技術要求：

1. ✅ 使用 PHP Laravel12 建立專案
2. ✅ 單純 API 結構，使用 GET / POST / PUT / DELETE 呼叫
3. ✅ 使用 Sail 建立測試環境
4. ✅ 充分使用 Laravel PHPUnit 測試
5. ✅ 確保呼叫 API 的權限驗證問題
6. ✅ SMTP 請用預設資料並且包含 DB 設定，於 .env 中實現
7. ✅ 專案請確保中文的 PHPDoc 註解，並且流程說明

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

**Simplicity**:

- Projects: 1 (Laravel API project - within max 3)
- Using framework directly? ✅ **是** - 直接使用 Laravel，無不必要的包裝類別)
- Single data model? ✅ **是** - 使用 Laravel Eloquent ORM 直接操作，無額外 DTO 層)
- Avoiding patterns? ✅ **是** - 使用 Laravel 標準架構，避免過度設計的 Repository/UoW 模式)

**Architecture**:

- EVERY feature as library? ✅ **部分** - 使用 Laravel Service Classes 組織業務邏輯)
- Libraries listed:
  - ✅ **UserService** - 使用者管理業務邏輯
  - ✅ **AuthService** - 認證相關邏輯
  - ✅ **MailService** - 郵件發送邏輯
- CLI per library: ✅ **使用 Laravel Artisan 命令**
  - php artisan user:create --help
  - php artisan auth:verify --help
  - php artisan mail:test --help
- Library docs: ✅ **是** - 將生成中文 PHPDoc 註解

**Testing (NON-NEGOTIABLE)**:

- RED-GREEN-Refactor cycle enforced? ✅ **是** - 嚴格遵循 TDD)
- Git commits show tests before implementation? ✅ **是** - 先寫測試後實作)
- Order: Contract→Integration→E2E→Unit strictly followed? ✅ **是** - 按順序執行)
- Real dependencies used? ✅ **是** - 使用實際 MySQL 資料庫進行測試)
- Integration tests for: new libraries, contract changes, shared schemas? ✅ **是**
- FORBIDDEN: Implementation before test, skipping RED phase ⚠️ **嚴格禁止**

**Observability**:

- Structured logging included? ✅ **是** - 使用 Laravel Log)
- Frontend logs → backend? ❌ **N/A** - 純後端 API)
- Error context sufficient? ✅ **是** - 詳細的錯誤回應和日誌)

**Versioning**:

- Version number assigned? ✅ **1.0.0** - MAJOR.MINOR.BUILD)
- BUILD increments on every change? ✅ **是**
- Breaking changes handled? ✅ **是** - API 版本控制)

**Structure Decision**: Option 1 (Single Laravel API project) - 因為是純後端 API 系統

## Project Structure

### Documentation (this feature)

```
specs/[###-feature]/
├── plan.md              # This file (/plan command output)
├── research.md          # Phase 0 output (/plan command)
├── data-model.md        # Phase 1 output (/plan command)
├── quickstart.md        # Phase 1 output (/plan command)
├── contracts/           # Phase 1 output (/plan command)
└── tasks.md             # Phase 2 output (/tasks command - NOT created by /plan)
```

### Source Code (repository root)

```
# Option 1: Single project (DEFAULT)
src/
├── models/
├── services/
├── cli/
└── lib/

tests/
├── contract/
├── integration/
└── unit/

# Option 2: Web application (when "frontend" + "backend" detected)
backend/
├── src/
│   ├── models/
│   ├── services/
│   └── api/
└── tests/

frontend/
├── src/
│   ├── components/
│   ├── pages/
│   └── services/
└── tests/

# Option 3: Mobile + API (when "iOS/Android" detected)
api/
└── [same as backend above]

ios/ or android/
└── [platform-specific structure]
```

**Structure Decision**: Option 1 (Single Laravel API project) - 因為是純後端 API 系統

## Phase 0: Outline & Research ✅ **COMPLETED**

所有 NEEDS CLARIFICATION 項目已在 `research.md` 中解決：

1. ✅ **密碼複雜度要求**: 最小 8 字符，包含大小寫字母及數字
2. ✅ **會話管理**: Laravel Sanctum Token-based，24 小時過期
3. ✅ **暴力破解防護**: Rate Limiting - 5 次失敗後鎖定 5 分鐘
4. ✅ **安全事件記錄**: 記錄登入失敗、密碼重設等事件，保存 30 天

**輸出**: research.md 包含所有技術決策和最佳實踐建議

## Phase 1: Design & Contracts ✅ **COMPLETED**

_Prerequisites: research.md complete ✅_

**已完成的設計文件**:

1. ✅ **資料模型設計** (`data-model.md`):

   - Users 實體: username, email, password, name, phone, email_verified_at
   - SysUsers 實體: 管理員帳戶，擁有管理使用者權限
   - 驗證規則和狀態管理定義完整

2. ✅ **API 合約** (`contracts/api-spec.yaml`):

   - 12 個 REST API 端點規格
   - OpenAPI 3.0 格式
   - 包含請求/回應結構、錯誤處理

3. ✅ **合約測試**:

   - API 結構驗證測試已規劃
   - 每個端點對應測試檔案
   - 測試必須先失敗（RED phase）

4. ✅ **整合測試場景** (`quickstart.md`):

   - 使用者註冊/登入流程測試
   - 管理員功能測試
   - 系統驗證步驟

5. ✅ **Agent 檔案更新** (`CLAUDE.md`):
   - Laravel 12 技術堆疊
   - 中文註解和開發指引
   - 專案特定配置

**輸出**: data-model.md, contracts/api-spec.yaml, quickstart.md, CLAUDE.md 全部完成

## Phase 2: Task Planning Approach ✅ **COMPLETED**

_這個階段已由 /tasks 命令執行完成_

**任務生成策略已實施**:
基於 Laravel 12 架構和 TDD 原則，58 個具體任務已生成在 `tasks.md` 中：

**已完成的任務分類**:

1. ✅ **環境設定任務** (Phase 3) - 5 個任務:

   - Laravel 專案初始化
   - Sail 環境配置
   - 資料庫配置和遷移檔案

2. ✅ **合約測試任務** (Phase 3) - 12 個任務:

   - 從 OpenAPI 規格生成 API 合約測試
   - 每個端點一個測試檔案
   - 驗證請求/回應結構

3. ✅ **資料模型任務** (Phase 3) - 8 個任務:

   - Eloquent Model 建立 (User, SysUser)
   - Migration 檔案建立
   - Model Factory 和 Seeder

4. ✅ **認證服務任務** (Phase 3) - 12 個任務:

   - Sanctum 配置
   - 認證 Controller 和 Service
   - 中介軟體設定

5. ✅ **業務邏輯任務** (Phase 3) - 11 個任務:

   - 使用者管理服務
   - 郵件服務 (驗證、密碼重設)
   - 管理員功能實作

6. ✅ **整合測試任務** (Phase 3) - 10 個任務:
   - 使用者註冊/登入流程測試
   - 管理員功能測試
   - 權限驗證測試

**任務排序策略**:

- ✅ **TDD 優先**: 所有實作前必須先有失敗的測試
- ✅ **依賴順序**: 環境 → 資料模型 → 認證 → 業務邏輯 → 整合
- ✅ **並行標記**: 獨立檔案標記 [P] 可並行開發

**最終任務數量**: 58 個有序任務，準備執行

**IMPORTANT**: 此階段已由 /tasks 命令完成，現在可以開始實施階段

## Phase 3+: Future Implementation

_當前專案已準備進入實施階段_

**Phase 3**: ⏳ **準備開始** - 任務執行 (tasks.md 包含 58 個具體任務)  
**Phase 4**: ⏸️ **待執行** - 實施 (按照 tasks.md 執行憲法原則)  
**Phase 5**: ⏸️ **待執行** - 驗證 (執行測試、quickstart.md、效能驗證)

**下一步行動**:

1. 開始執行 `tasks.md` 中的第一個任務: "T001: 建立 Laravel 專案結構並初始化 example-app"
2. 遵循 TDD 原則 - 先寫測試，確認失敗，再實作
3. 按照任務依賴順序執行，注意標記為 [P] 的可並行任務

## Complexity Tracking

_憲法檢查結果 - 無違規項目_

| Violation     | Why Needed           | Simpler Alternative Rejected Because |
| ------------- | -------------------- | ------------------------------------ |
| ✅ **無違規** | 專案完全符合憲法要求 | 使用 Laravel 標準架構，避免過度設計  |

**憲法合規性驗證**:

- ✅ 簡化原則: 使用 Laravel 框架直接實作，無不必要抽象層
- ✅ 架構原則: Service Classes 組織業務邏輯，遵循 Laravel 慣例
- ✅ 測試原則: 嚴格 TDD，真實依賴，完整測試覆蓋
- ✅ 可觀測性: Laravel Log 結構化日誌，詳細錯誤追蹤
- ✅ 版本控制: 語義化版本，API 版本管理

## Progress Tracking

_專案執行進度 - 規劃階段已完成_

**Phase Status**:

- [x] ✅ **Phase 0**: Research complete (/plan command) - 所有技術決策已確定
- [x] ✅ **Phase 1**: Design complete (/plan command) - 設計文件全部完成
- [x] ✅ **Phase 2**: Task planning complete (/plan command) - 58 個任務已生成
- [x] ✅ **Phase 3**: Tasks generated (/tasks command) - tasks.md 已建立
- [ ] ⏳ **Phase 4**: Implementation ready - 準備開始執行任務
- [ ] ⏸️ **Phase 5**: Validation pending - 等待實施完成後驗證

**Gate Status**:

- [x] ✅ **Initial Constitution Check**: PASS - 無違規項目
- [x] ✅ **Post-Design Constitution Check**: PASS - 設計符合憲法
- [x] ✅ **All NEEDS CLARIFICATION resolved** - research.md 已解決
- [x] ✅ **Complexity deviations documented** - 無複雜度偏差

**Generated Artifacts**:

- [x] ✅ **research.md** - 技術決策和需求澄清 (163 lines)
- [x] ✅ **data-model.md** - 資料庫設計和實體定義 (208 lines)
- [x] ✅ **contracts/api-spec.yaml** - OpenAPI 規格 (12 endpoints)
- [x] ✅ **quickstart.md** - 快速開始指南和測試流程
- [x] ✅ **CLAUDE.md** - Claude 開發指引 (Laravel 12 specific)
- [x] ✅ **tasks.md** - 58 個實施任務，TDD 順序排列

**當前狀態**: 🚀 **準備開始實施** - 所有規劃文件完整，可以開始執行第一個任務

---

_Based on Constitution v2.1.1 - See `/memory/constitution.md`_
