# Tasks: Admin 2 - 預設管理員與增強功能 (使用統一 User Table)

**Input**: 用戶需求: "請幫我實作：1. 資料表建立時，添加一個預設的 admin 用戶。2. admin 添加 admin 用戶的 api 3. admin 用戶不需要 email 也能登入 4. .env 添加變數，設定是否需要驗證 email ，如果不需要驗證則登入後不發且"
**Prerequisites**: 基於 001-laravel12-example-app 的現有 Laravel 專案，使用統一的 User Table 管理所有用戶

## Execution Flow (main)

```
1. Load 基礎專案結構 from 001-laravel12-example-app
   → ✅ Found: Laravel 12 API with Sanctum, existing User model with role field
   → Extracted: 現有的認證系統、用戶模型、API 結構
   → 決策: 使用統一 User Table，移除 SysUser 相關邏輯
2. 分析用戶需求:
   → 預設 admin 用戶創建 (Database Seeder for User with admin role)
   → 新增管理員用戶的 API 端點 (Create Admin User API)
   → 管理員無需 email 登入功能 (Auth Logic with username/password)
   → Email 驗證開關環境變數 (.env Configuration)
3. Generate tasks by category:
   → Setup: .env 配置，User table migration 更新
   → Tests: API 合約測試，功能測試 (TDD first)
   → Core: Seeder, API Controller methods, Auth logic
   → Integration: 登入邏輯修改，郵件服務條件判斷
   → Polish: 文檔更新，測試完善
4. Apply task rules:
   → Different files = marked [P] for parallel execution
   → Same file = sequential (no [P] marker)
   → All tests before any implementation (TDD mandatory)
5. Number tasks sequentially (T001-T018)
6. Generate dependency graph
7. ✅ Validate task completeness: All user requirements covered
8. Return: SUCCESS (18 tasks ready for execution)
```

## Format: `[ID] [P?] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- Include exact file paths in descriptions
- All file paths are absolute from example-app/ directory

## Path Conventions

**Project Structure**: Laravel project in example-app/ directory

- **Models**: `example-app/app/Models/`
- **Controllers**: `example-app/app/Http/Controllers/`
- **Requests**: `example-app/app/Http/Requests/`
- **Services**: `example-app/app/Services/`
- **Tests**: `example-app/tests/Feature/`, `example-app/tests/Unit/`
- **Seeders**: `example-app/database/seeders/`
- **Config**: `example-app/.env`

## Phase 3.1: Setup (環境配置與資料庫調整)

- [ ] T001 [P] 在 .env 中添加 REQUIRE_EMAIL_VERIFICATION 環境變數 in `example-app/.env`
- [ ] T002 [P] 在 config/auth.php 中添加郵件驗證配置讀取 in `example-app/config/auth.php`
- [ ] T003 [P] 確保 User table 支援 username 欄位（如需要則建立 migration） in `example-app/database/migrations/`

## Phase 3.2: Tests First (TDD) ⚠️ 必須在實作前完成

**關鍵提醒: 這些測試必須先寫好並且失敗，才能進行任何實作**

### Contract Tests (API 端點結構測試)

- [ ] T004 [P] 合約測試 POST /api/v1/admin/users (創建管理員用戶) in `example-app/tests/Feature/Contract/AdminCreateUserContractTest.php`
- [ ] T005 [P] 合約測試 POST /api/v1/auth/admin-login (管理員專用登入) in `example-app/tests/Feature/Contract/AdminLoginContractTest.php`

### Integration Tests (完整功能流程測試)

- [ ] T006 [P] 整合測試 預設 admin 用戶創建功能 in `example-app/tests/Feature/Integration/DefaultAdminCreationTest.php`
- [ ] T007 [P] 整合測試 管理員創建新管理員流程 in `example-app/tests/Feature/Integration/AdminCreateAdminTest.php`
- [ ] T008 [P] 整合測試 管理員無需 email 登入流程 in `example-app/tests/Feature/Integration/AdminNoEmailLoginTest.php`
- [ ] T009 [P] 整合測試 EMAIL_VERIFICATION 環境變數控制 in `example-app/tests/Feature/Integration/EmailVerificationToggleTest.php`

## Phase 3.3: Database & Seeders (資料層實作)

**僅在測試失敗後執行**

### Database Seeders

- [ ] T010 建立預設管理員用戶 Seeder (User with admin role) in `example-app/database/seeders/DefaultAdminSeeder.php`
- [ ] T011 更新 DatabaseSeeder 以包含預設管理員 in `example-app/database/seeders/DatabaseSeeder.php`

## Phase 3.4: Core Implementation (核心功能實作)

**僅在測試失敗後執行**

### API Endpoints (Admin Management)

- [ ] T012 在 AdminController 中添加 createUser 方法 (創建管理員用戶) in `example-app/app/Http/Controllers/AdminController.php`
- [ ] T013 [P] 建立 CreateAdminUserRequest 驗證請求 in `example-app/app/Http/Requests/CreateAdminUserRequest.php`

### Authentication Logic

- [ ] T014 在 AuthController 中添加 adminLogin 方法 in `example-app/app/Http/Controllers/AuthController.php`
- [ ] T015 [P] 建立 AdminLoginRequest 驗證請求 in `example-app/app/Http/Requests/AdminLoginRequest.php`

### Authentication Logic

- [ ] T014 在 AuthController 中添加 adminLogin 方法 in `example-app/app/Http/Controllers/AuthController.php`
- [ ] T015 [P] 建立 AdminLoginRequest 驗證請求 in `example-app/app/Http/Requests/AdminLoginRequest.php`

### Email Verification Control

- [ ] T016 修改郵件驗證服務支援環境變數控制 in `example-app/app/Services/MailService.php`
- [ ] T017 更新註冊流程以條件性發送驗證郵件 in `example-app/app/Http/Controllers/AuthController.php`

## Phase 3.5: Integration (系統整合)

- [ ] T018 更新 API 路由以包含新端點 in `example-app/routes/api.php`

## Dependencies

- Tests (T004-T009) before implementation (T010-T018)
- T010 blocks T011 (DatabaseSeeder depends on DefaultAdminSeeder)
- T012 blocks T013 (Controller method before Request class)
- T014 blocks T015 (AuthController before AdminLoginRequest)
- T016, T017 can run in parallel (different aspects of email logic)
- T018 after all controller changes (T012, T014, T017)

## Parallel Execution Examples

````bash
```bash
# Phase 3.2 - Launch all contract tests together:
Task: "合約測試 POST /api/v1/admin/users in example-app/tests/Feature/Contract/AdminCreateUserContractTest.php"
Task: "合約測試 POST /api/v1/auth/admin-login in example-app/tests/Feature/Contract/AdminLoginContractTest.php"

# Phase 3.2 - Launch all integration tests together:
Task: "整合測試 預設 admin 用戶創建功能 in example-app/tests/Feature/Integration/DefaultAdminCreationTest.php"
Task: "整合測試 管理員創建新管理員流程 in example-app/tests/Feature/Integration/AdminCreateAdminTest.php"
Task: "整合測試 管理員無需 email 登入流程 in example-app/tests/Feature/Integration/AdminNoEmailLoginTest.php"
Task: "整合測試 EMAIL_VERIFICATION 環境變數控制 in example-app/tests/Feature/Integration/EmailVerificationToggleTest.php"

# Phase 3.4 - Launch parallel request validations:
Task: "建立 CreateAdminUserRequest 驗證請求 in example-app/app/Http/Requests/CreateAdminUserRequest.php"
Task: "建立 AdminLoginRequest 驗證請求 in example-app/app/Http/Requests/AdminLoginRequest.php"
````

```

## Task Generation Rules

_Applied during main() execution_

1. **From User Requirements**:

   - 預設 admin 用戶 → seeder creation task for User with admin role
   - admin 添加 admin 用戶 API → controller method + request validation for User
   - admin 無需 email 登入 → auth logic modification for username login
   - .env 變數控制 email 驗證 → config + service updates

2. **From Existing Structure**:

   - 基於現有 User model → 使用 role 欄位區分管理員
   - 基於現有 AdminController → extend with new User management methods
   - 基於現有 AuthController → add admin-specific login for User
   - 基於現有 AuthService → modify login logic for username
   - 基於現有 MailService → add conditional logic

3. **Testing Strategy**:

   - Each new API endpoint → contract test [P]
   - Each user requirement → integration test [P]
   - All tests before implementation (TDD)

4. **Ordering**:
   - Setup (.env + migration) → Tests → Seeders → Core → Integration
   - Dependencies block parallel execution
   - Same file modifications are sequential

## Validation Checklist

_GATE: Checked by main() before returning_

- [x] All user requirements have corresponding tasks
- [x] All new API endpoints have contract tests
- [x] All major features have integration tests
- [x] All tests come before implementation
- [x] Parallel tasks truly independent
- [x] Each task specifies exact file path
- [x] No task modifies same file as another [P] task
- [x] Dependencies properly mapped
- [x] TDD workflow enforced (RED before GREEN)
- [x] 改用統一 User Table，移除 SysUser 相關內容

## Notes

- [P] tasks = different files, no dependencies
- Verify tests fail before implementing
- Commit after each task
- Avoid: vague tasks, same file conflicts
- 基於現有 001 feature 的 Laravel 專案結構，使用統一 User Table
- 重複使用現有的 User Model, Services, Controllers 架構
- 專注於增強功能而非重建系統
- 管理員和一般用戶都使用 User model，透過 role 欄位區分
```
