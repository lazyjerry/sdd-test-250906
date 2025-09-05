# Tasks: Laravel12 Example-App 後端 API 系統

**Input**: Design documents from `/Users/lazyjerry/Dropbox/個人project/個人用專案/laravel/JDemo/specs/001-laravel12-example-app/`
**Prerequisites**: plan.md (完成), research.md (完成), data-model.md (完成), contracts/ (完成), quickstart.md (完成)

## Execution Flow (main)

```
1. Load plan.md from feature directory
   → ✅ Found: Laravel 12 API system with Sail, Sanctum, MySQL
   → Extracted: PHP 8.2+, Laravel 12, Docker/Sail, PHPUnit testing
2. Load optional design documents:
   → ✅ data-model.md: Users, SysUsers, EmailVerifications entities
   → ✅ contracts/api-spec.yaml: 12 API endpoints across 3 controllers
   → ✅ research.md: Technical decisions and clarifications
   → ✅ quickstart.md: Complete test scenarios and workflows
3. Generate tasks by category:
   → Setup: Laravel project init, Sail config, dependencies
   → Tests: 12 contract tests + 7 integration tests (TDD first)
   → Core: 5 models, 3 services, 3 controllers, 8 requests
   → Integration: migrations, middleware, policies, mail
   → Polish: unit tests, seeders, documentation
4. Apply task rules:
   → Different files = marked [P] for parallel execution
   → Same file = sequential (no [P] marker)
   → All tests before any implementation (TDD mandatory)
5. Number tasks sequentially (T001-T052)
6. Generate dependency graph and parallel execution examples
7. ✅ Validate task completeness: All endpoints + entities covered
8. Return: SUCCESS (52 tasks ready for execution)
```

## Format: `[ID] [P?] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- Include exact file paths in descriptions
- All file paths are absolute from project root

## Path Conventions

**Project Structure**: Single Laravel project at repository root

- **Models**: `app/Models/`
- **Controllers**: `app/Http/Controllers/`
- **Requests**: `app/Http/Requests/`
- **Services**: `app/Services/`
- **Tests**: `tests/Feature/`, `tests/Unit/`
- **Migrations**: `database/migrations/`
- **Seeders**: `database/seeders/`

## Phase 3.1: Setup (環境建置)

- [x] ✅ **T001 建立 Laravel 專案結構並初始化 example-app** (COMPLETED)
- [x] ✅ **T002 配置 Laravel Sail 環境 (MySQL, Redis, MailHog)** (COMPLETED)
- [x] ✅ **T003 [P] 安裝並配置 Laravel Sanctum for API 認證** (COMPLETED)
- [x] ✅ **T004 [P] 配置 .env 環境變數 (DB, Mail, Cache, Queue)** (COMPLETED)
- [x] ✅ **T005 [P] 設定 CORS 和 API 路由結構** (COMPLETED)

## Phase 3.2: Tests First (TDD) ⚠️ 必須在實作前完成

**關鍵提醒: 這些測試必須先寫好並且失敗，才能進行任何實作**

### Contract Tests (API 端點結構測試)

- [x] ✅ **T006 [P] 合約測試 POST /api/v1/auth/register** (COMPLETED - RED ⚠️)
- [x] ✅ **T007 [P] 合約測試 POST /api/v1/auth/login** (COMPLETED - RED ⚠️)
- [x] ✅ **T008 [P] 合約測試 POST /api/v1/auth/logout** (COMPLETED - RED ⚠️)
- [x] ✅ **T009 [P] 合約測試 POST /api/v1/auth/forgot-password** (COMPLETED - RED ⚠️)
- [x] ✅ **T010 [P] 合約測試 POST /api/v1/auth/reset-password** (COMPLETED - RED ⚠️)
- [x] ✅ **T011 [P] 合約測試 POST /api/v1/auth/verify-email** (COMPLETED - RED ⚠️)
- [x] ✅ **T012 [P] 合約測試 GET /api/v1/users/profile** (COMPLETED - RED ⚠️)
- [x] ✅ **T013 [P] 合約測試 PUT /api/v1/users/profile** (COMPLETED - RED ⚠️)
- [x] ✅ **T014 [P] 合約測試 PUT /api/v1/users/change-password** (COMPLETED - RED ⚠️)
- [x] ✅ **T015 [P] 合約測試 GET /api/v1/admin/users** (COMPLETED - RED ⚠️)
- [x] ✅ **T016 [P] 合約測試 GET /api/v1/admin/users/{id}** (COMPLETED - RED ⚠️)
- [x] ✅ **T017 [P] 合約測試 PUT /api/v1/admin/users/{id}** (COMPLETED - RED ⚠️)
- [x] ✅ **T018 [P] 合約測試 POST /api/v1/admin/users/{id}/reset-password** (COMPLETED - RED ⚠️)

### Integration Tests (完整功能流程測試)

- [ ] T019 [P] 整合測試 使用者註冊流程 in `tests/Feature/Integration/UserRegistrationTest.php`
- [ ] T020 [P] 整合測試 信箱驗證流程 in `tests/Feature/Integration/EmailVerificationTest.php`
- [ ] T021 [P] 整合測試 使用者登入流程 in `tests/Feature/Integration/UserAuthenticationTest.php`
- [ ] T022 [P] 整合測試 個人資料管理流程 in `tests/Feature/Integration/ProfileManagementTest.php`
- [ ] T023 [P] 整合測試 忘記密碼流程 in `tests/Feature/Integration/PasswordResetTest.php`
- [ ] T024 [P] 整合測試 管理員功能流程 in `tests/Feature/Integration/AdminFunctionsTest.php`
- [ ] T025 [P] 整合測試 API 權限驗證 in `tests/Feature/Integration/ApiAuthorizationTest.php`

## Phase 3.3: Database & Models (資料層實作)

**僅在測試失敗後執行**

### Database Migrations

- [ ] T026 [P] 建立 users 資料表遷移 in `database/migrations/2025_09_05_000001_create_users_table.php`
- [ ] T027 [P] 建立 sys_users 資料表遷移 in `database/migrations/2025_09_05_000002_create_sys_users_table.php`
- [ ] T028 [P] 建立 email_verifications 資料表遷移 in `database/migrations/2025_09_05_000003_create_email_verifications_table.php`
- [ ] T029 [P] 修改 personal_access_tokens 遷移 (Sanctum 擴展) in `database/migrations/2025_09_05_000004_modify_personal_access_tokens_table.php`

### Eloquent Models

- [ ] T030 [P] User 模型 in `app/Models/User.php`
- [ ] T031 [P] SysUser 模型 in `app/Models/SysUser.php`
- [ ] T032 [P] EmailVerification 模型 in `app/Models/EmailVerification.php`

### Model Factories & Seeders

- [ ] T033 [P] UserFactory in `database/factories/UserFactory.php`
- [ ] T034 [P] SysUserFactory in `database/factories/SysUserFactory.php`
- [ ] T035 [P] DatabaseSeeder 設定 in `database/seeders/DatabaseSeeder.php`

## Phase 3.4: Core Implementation (核心功能實作)

**按照依賴順序執行，相同檔案不可並行**

### Form Request Validation

- [ ] T036 [P] UserRegistrationRequest in `app/Http/Requests/Auth/UserRegistrationRequest.php`
- [ ] T037 [P] LoginRequest in `app/Http/Requests/Auth/LoginRequest.php`
- [ ] T038 [P] ForgotPasswordRequest in `app/Http/Requests/Auth/ForgotPasswordRequest.php`
- [ ] T039 [P] ResetPasswordRequest in `app/Http/Requests/Auth/ResetPasswordRequest.php`
- [ ] T040 [P] UpdateProfileRequest in `app/Http/Requests/User/UpdateProfileRequest.php`
- [ ] T041 [P] ChangePasswordRequest in `app/Http/Requests/User/ChangePasswordRequest.php`
- [ ] T042 [P] AdminUpdateUserRequest in `app/Http/Requests/Admin/AdminUpdateUserRequest.php`
- [ ] T043 [P] AdminResetPasswordRequest in `app/Http/Requests/Admin/AdminResetPasswordRequest.php`

### Service Classes

- [ ] T044 AuthService 使用者認證服務 in `app/Services/AuthService.php`
- [ ] T045 UserService 使用者管理服務 in `app/Services/UserService.php`
- [ ] T046 MailService 郵件發送服務 in `app/Services/MailService.php`

### Controllers (依賴 Services)

- [ ] T047 AuthController 認證控制器 in `app/Http/Controllers/Api/V1/AuthController.php`
- [ ] T048 UserController 使用者控制器 in `app/Http/Controllers/Api/V1/UserController.php`
- [ ] T049 AdminController 管理員控制器 in `app/Http/Controllers/Api/V1/AdminController.php`

## Phase 3.5: Integration (整合功能)

- [ ] T050 設定 API 路由 in `routes/api.php`
- [ ] T051 建立 Authorization Policies in `app/Policies/UserPolicy.php` and `app/Policies/SysUserPolicy.php`
- [ ] T052 配置認證中介軟體和權限檢查

## Phase 3.6: Polish (完善功能)

- [ ] T053 [P] Unit Tests for AuthService in `tests/Unit/Services/AuthServiceTest.php`
- [ ] T054 [P] Unit Tests for UserService in `tests/Unit/Services/UserServiceTest.php`
- [ ] T055 [P] Unit Tests for MailService in `tests/Unit/Services/MailServiceTest.php`
- [ ] T056 [P] API 效能測試 (<200ms 回應時間)
- [ ] T057 [P] 更新 API 文件和範例
- [ ] T058 執行完整測試套件驗證

## Dependencies (依賴關係)

```
Setup Phase (T001-T005) → Tests Phase (T006-T025) → Implementation Phase (T026+)

Critical TDD Order:
- T006-T025 (All Tests) MUST complete before T026+ (Any Implementation)
- T026-T032 (DB/Models) → T044-T046 (Services) → T047-T049 (Controllers)
- T050-T052 (Integration) → T053+ (Polish)

Blocking Dependencies:
- T030,T031,T032 (Models) block T044,T045,T046 (Services)
- T044,T045,T046 (Services) block T047,T048,T049 (Controllers)
- T047,T048,T049 (Controllers) block T050 (Routes)
```

## Parallel Execution Examples

### Phase 3.2A: Contract Tests (可同時執行)

```
Task: "合約測試 POST /api/v1/auth/register in tests/Feature/Auth/RegisterContractTest.php"
Task: "合約測試 POST /api/v1/auth/login in tests/Feature/Auth/LoginContractTest.php"
Task: "合約測試 POST /api/v1/auth/logout in tests/Feature/Auth/LogoutContractTest.php"
Task: "合約測試 POST /api/v1/auth/forgot-password in tests/Feature/Auth/ForgotPasswordContractTest.php"
```

### Phase 3.2B: Integration Tests (可同時執行)

```
Task: "整合測試 使用者註冊流程 in tests/Feature/Integration/UserRegistrationTest.php"
Task: "整合測試 信箱驗證流程 in tests/Feature/Integration/EmailVerificationTest.php"
Task: "整合測試 使用者登入流程 in tests/Feature/Integration/UserAuthenticationTest.php"
Task: "整合測試 個人資料管理流程 in tests/Feature/Integration/ProfileManagementTest.php"
```

### Phase 3.3: Models & Migrations (可同時執行)

```
Task: "建立 users 資料表遷移 in database/migrations/2025_09_05_000001_create_users_table.php"
Task: "建立 sys_users 資料表遷移 in database/migrations/2025_09_05_000002_create_sys_users_table.php"
Task: "User 模型 in app/Models/User.php"
Task: "SysUser 模型 in app/Models/SysUser.php"
```

## Notes

- **[P] 標記**: 不同檔案，無依賴，可並行執行
- **TDD 嚴格執行**: 所有測試必須先失敗，才能開始實作
- **提交策略**: 每個任務完成後提交一次
- **中文註解**: 所有 PHP 檔案使用中文 PHPDoc 註解
- **API 版本**: 所有端點使用 `/api/v1/` 前綴

## Task Generation Rules Applied

✅ **每個合約檔案** → 合約測試任務 [P]  
✅ **每個實體** → 模型建立任務 [P]  
✅ **每個端點** → 實作任務 (相同檔案不並行)  
✅ **每個使用者故事** → 整合測試 [P]  
✅ **不同檔案** = 可並行 [P]  
✅ **相同檔案** = 循序 (無 [P])

## Validation Checklist

- [x] 所有 12 個 API 端點都有合約測試
- [x] 所有 5 個實體都有模型任務
- [x] 所有 7 個使用者故事都有整合測試
- [x] TDD 順序: 測試 → 實作
- [x] 依賴順序: 模型 → 服務 → 控制器
- [x] 並行標記: 不同檔案標記 [P]
- [x] 檔案路徑: 所有路徑都明確指定
- [x] 總任務數: 58 個任務，涵蓋完整開發週期
