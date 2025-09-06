# 測試指南

本專案採用 Test-Driven Development (TDD) 方法開發，提供完整的測試套件，包含自動化測試和手動測試腳本。

## 測試類型

- **自動化測試**: 使用 PHPUnit 的完整測試套件 (170 個測試)
- **手動測試**: 互動式測試腳本 (`test_scripts/`)
- **整合測試**: 端到端功能驗證
- **效能測試**: API 回應時間和併發測試

## 自動化測試指令

### 基本測試指令

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

# 停止在第一個失敗的測試
./vendor/bin/sail test --stop-on-failure
```

### 特定功能測試

#### 認證功能測試

```bash
# 登入角色隔離測試
./vendor/bin/sail test tests/Feature/Auth/LoginRoleIsolationTest.php

# 其他認證測試
./vendor/bin/sail test tests/Feature/Auth/EmailVerificationTest.php
./vendor/bin/sail test tests/Feature/Auth/LoginContractTest.php
./vendor/bin/sail test tests/Feature/Auth/RegisterContractTest.php
./vendor/bin/sail test tests/Feature/Auth/LogoutContractTest.php
./vendor/bin/sail test tests/Feature/Auth/ForgotPasswordContractTest.php
./vendor/bin/sail test tests/Feature/Auth/ResetPasswordContractTest.php
```

#### 使用者功能測試

```bash
./vendor/bin/sail test tests/Feature/User/ProfileContractTest.php
./vendor/bin/sail test tests/Feature/User/UpdateProfileContractTest.php
./vendor/bin/sail test tests/Feature/User/ChangePasswordContractTest.php
```

#### 管理員功能測試

```bash
./vendor/bin/sail test tests/Feature/Admin/UserListContractTest.php
./vendor/bin/sail test tests/Feature/Admin/UserDetailContractTest.php
./vendor/bin/sail test tests/Feature/Admin/UpdateUserContractTest.php
./vendor/bin/sail test tests/Feature/Admin/ResetUserPasswordContractTest.php
./vendor/bin/sail test tests/Feature/Contract/AdminLoginContractTest.php
```

#### 整合測試

```bash
./vendor/bin/sail test tests/Feature/Integration/EmailVerificationTest.php
./vendor/bin/sail test tests/Feature/Integration/ProfileManagementTest.php
./vendor/bin/sail test tests/Feature/Integration/PasswordResetTest.php
./vendor/bin/sail test tests/Feature/Integration/UserRegistrationTest.php
```

### 單一測試方法

```bash
# 認證功能特定測試方法
./vendor/bin/sail test --filter=user_can_verify_email_via_post_api
./vendor/bin/sail test --filter=user_can_login_with_valid_credentials
./vendor/bin/sail test --filter=admin_cannot_login_via_user_login_api

# 使用者功能特定測試方法
./vendor/bin/sail test --filter=user_can_get_profile
./vendor/bin/sail test --filter=user_can_update_profile
./vendor/bin/sail test --filter=user_can_change_password

# 管理員功能特定測試方法
./vendor/bin/sail test --filter=admin_can_get_users_list
./vendor/bin/sail test --filter=admin_can_get_user_details
./vendor/bin/sail test --filter=admin_can_update_user
```

## 手動測試指令

```bash
# 認證功能手動測試
./test_scripts/auth/test_email_verification.sh

# 查看所有可用的測試腳本
ls test_scripts/*/

# 查看各分類的測試腳本使用說明
cat test_scripts/README.md                      # 主要測試腳本說明
cat test_scripts/auth/README.md                 # 認證測試說明
cat test_scripts/user/README.md                 # 使用者測試說明
cat test_scripts/admin/README.md                # 管理員測試說明
cat test_scripts/integration/README.md          # 整合測試說明

# 查看詳細的手動測試指南
cat test_scripts/auth/EMAIL_VERIFICATION_TESTING_GUIDE.md
```

## 測試環境準備

執行測試前請確保環境正確設置：

```bash
# 啟動測試環境
./vendor/bin/sail up -d

# 執行資料庫遷移
./vendor/bin/sail artisan migrate:fresh

# 清除所有快取
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear

# 驗證測試環境
./vendor/bin/sail artisan migrate:status
```

## 測試統計

**最新測試結果:**

- **總測試數**: 170 個測試，1518 個斷言
- **通過率**: 80.6% (137/170 通過)
- **失敗測試**: 33 個 (主要為參數名稱不統一)
- **風險測試**: 2 個 (缺少斷言)

**測試分類統計:**

- **登入角色隔離測試**: 10/10 通過 (100%) ✅
- **認證系統**: 25 個 (96% 通過) ✅
- **管理員功能**: 32 個 (94% 通過) ✅
- **使用者管理**: 28 個 (75% 通過) ⚠️
- **整合測試**: 71 個 (68% 通過) ⚠️

**主要修復成果:**

- **角色基礎註冊**: ✅ 完整實作，所有測試通過
- **Sanctum 認證核心**: ✅ 完全修復並穩定運行
- **管理員權限控制**: ✅ 嚴格的角色權限檢查
- **API 安全防護**: ✅ SecurityHeaders 中間件
- **測試環境穩定性**: ✅ Docker 環境配置最佳化

## 角色隔離測試

新增的登入角色隔離功能包含完整測試：

```bash
# 執行角色隔離測試
./vendor/bin/sail test tests/Feature/Auth/LoginRoleIsolationTest.php

# 測試內容包含:
# ✅ 普通用戶無法使用管理員登入 API
# ✅ 管理員無法使用普通用戶登入 API
# ✅ 超級管理員無法使用普通用戶登入 API
# ✅ 同時測試 username 和 email 登入
# ✅ 軟刪除用戶無法登入
# ✅ 正確的 API 可以正常登入
```

## 測試覆蓋率

執行以下指令查看詳細的測試覆蓋率報告：

```bash
# 產生 HTML 覆蓋率報告
./vendor/bin/sail test --coverage-html coverage-report

# 查看覆蓋率摘要
./vendor/bin/sail test --coverage

# 查看特定模組的覆蓋率
./vendor/bin/sail test tests/Feature/Auth/ --coverage
./vendor/bin/sail test tests/Feature/User/ --coverage
./vendor/bin/sail test tests/Feature/Admin/ --coverage
```

## 測試修復建議

針對失敗的測試，建議按以下優先順序修復：

### 1. 高優先級 - API 基礎功能

```bash
# 修復認證相關的基本功能
./vendor/bin/sail test tests/Feature/Auth/ --stop-on-failure
./vendor/bin/sail test tests/Feature/Integration/UserAuthenticationTest.php --stop-on-failure
```

### 2. 中優先級 - 用戶管理功能

```bash
# 修復用戶資料管理功能
./vendor/bin/sail test tests/Feature/User/ --stop-on-failure
./vendor/bin/sail test tests/Feature/Integration/ProfileManagementTest.php --stop-on-failure
```

### 3. 低優先級 - 進階功能

```bash
# 修復密碼重設和進階功能
./vendor/bin/sail test tests/Feature/Integration/PasswordResetTest.php --stop-on-failure
./vendor/bin/sail test tests/Feature/Integration/EmailVerificationTest.php --stop-on-failure
```

## 測試資源

- **自動化測試**: [`example-app/tests/`](../example-app/tests/) - PHPUnit 測試套件
  - `Feature/Auth/` - 認證功能測試
  - `Feature/User/` - 使用者功能測試
  - `Feature/Admin/` - 管理員功能測試
  - `Feature/Integration/` - 整合測試
  - `Unit/` - 單元測試
- **手動測試腳本**: [`test_scripts/`](../test_scripts/) - 分類的手動測試腳本和指南
  - `auth/` - 認證相關手動測試
  - `user/` - 使用者功能手動測試指南
  - `admin/` - 管理員功能手動測試指南
  - `integration/` - 整合測試指南
- **測試配置**: [`example-app/phpunit.xml`](../example-app/phpunit.xml) - PHPUnit 配置檔案

## 郵箱驗證功能測試

### 自動化測試

郵箱驗證功能包含完整的自動化測試套件：

```bash
# 執行完整的郵箱驗證測試套件
./vendor/bin/sail test tests/Feature/Auth/EmailVerificationTest.php
```

### 測試涵蓋範圍

- ✅ **POST API 驗證**: 測試 `/api/v1/auth/verify-email` 端點
- ✅ **GET 路由驗證**: 測試 `/api/email/verify/{id}/{hash}` 端點
- ✅ **無效簽名處理**: 驗證簽名驗證機制
- ✅ **過期連結處理**: 測試時間戳驗證
- ✅ **錯誤 Hash 處理**: 測試 Hash 比對邏輯
- ✅ **重複驗證處理**: 測試已驗證用戶的處理
- ✅ **中間件功能**: 測試 `signed` 和 `throttle` 中間件

### 手動測試

```bash
# 執行手動測試腳本
./test_scripts/auth/test_email_verification.sh

# 查看詳細的手動測試指南
cat test_scripts/auth/EMAIL_VERIFICATION_TESTING_GUIDE.md
```

## 故障排除

### 常見測試問題與解決方案

#### 1. Sanctum 認證問題

```bash
# 問題: 測試中 Sanctum token 無法正確認證
# 解決方案: 使用 Sanctum::actingAs() 而非 HTTP Bearer token

// ❌ 錯誤的測試方式
$this->withHeader('Authorization', 'Bearer ' . $token)

// ✅ 正確的測試方式
Sanctum::actingAs($user)
```

#### 2. 測試環境重置

```bash
# 重置所有測試環境
./vendor/bin/sail down
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
```

#### 3. 參數名稱不匹配問題

```php
// 確保 API 參數與後端期望一致
// 如果後端期望 'username'，測試也要使用 'username'
$this->postJson('/api/v1/auth/login', [
    'username' => $user->email,  // 不是 'email'
    'password' => 'password'
]);
```
