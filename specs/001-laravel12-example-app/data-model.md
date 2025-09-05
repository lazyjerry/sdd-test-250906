# 資料模型設計

## 核心實體

### Users (一般使用者)

**用途**: 儲存一般使用者帳戶資料
**Table**: `users`

#### 欄位結構

| 欄位名            | 類型         | 限制               | 描述              |
| ----------------- | ------------ | ------------------ | ----------------- |
| id                | BIGINT       | PK, AUTO_INCREMENT | 使用者唯一識別碼  |
| username          | VARCHAR(50)  | UNIQUE, NOT NULL   | 使用者名稱        |
| email             | VARCHAR(255) | UNIQUE, NOT NULL   | 電子郵件地址      |
| password          | VARCHAR(255) | NOT NULL           | 加密密碼 (bcrypt) |
| name              | VARCHAR(100) | NULLABLE           | 真實姓名          |
| phone             | VARCHAR(20)  | NULLABLE           | 電話號碼          |
| email_verified_at | TIMESTAMP    | NULLABLE           | 信箱驗證時間      |
| created_at        | TIMESTAMP    | NOT NULL           | 建立時間          |
| updated_at        | TIMESTAMP    | NOT NULL           | 更新時間          |

#### 驗證規則

- username: 必填, 3-50 字符, 英數字及底線, 唯一
- email: 必填, 有效 email 格式, 唯一
- password: 必填, 最少 8 字符, 包含大小寫字母及數字
- name: 可選, 最多 100 字符
- phone: 可選, 有效電話格式

#### 狀態管理

- **未驗證**: email_verified_at = NULL
- **已驗證**: email_verified_at = 驗證時間戳

### SysUsers (系統管理員)

**用途**: 儲存系統管理員帳戶資料
**Table**: `sys_users`

#### 欄位結構

| 欄位名            | 類型         | 限制               | 描述              |
| ----------------- | ------------ | ------------------ | ----------------- |
| id                | BIGINT       | PK, AUTO_INCREMENT | 管理員唯一識別碼  |
| username          | VARCHAR(50)  | UNIQUE, NOT NULL   | 管理員使用者名稱  |
| email             | VARCHAR(255) | UNIQUE, NOT NULL   | 電子郵件地址      |
| password          | VARCHAR(255) | NOT NULL           | 加密密碼 (bcrypt) |
| name              | VARCHAR(100) | NULLABLE           | 真實姓名          |
| phone             | VARCHAR(20)  | NULLABLE           | 電話號碼          |
| email_verified_at | TIMESTAMP    | NULLABLE           | 信箱驗證時間      |
| is_super_admin    | BOOLEAN      | DEFAULT FALSE      | 超級管理員標記    |
| created_at        | TIMESTAMP    | NOT NULL           | 建立時間          |
| updated_at        | TIMESTAMP    | NOT NULL           | 更新時間          |

#### 權限層級

- **一般管理員**: 可管理 users 表資料
- **超級管理員**: 可管理 users 及 sys_users 表資料

## 支援實體

### PersonalAccessTokens (API 認證令牌)

**用途**: Laravel Sanctum Token 儲存
**Table**: `personal_access_tokens` (Laravel Sanctum 內建)

#### 欄位結構

| 欄位名         | 類型         | 限制               | 描述                    |
| -------------- | ------------ | ------------------ | ----------------------- |
| id             | BIGINT       | PK, AUTO_INCREMENT | Token ID                |
| tokenable_type | VARCHAR(255) | NOT NULL           | 模型類型 (User/SysUser) |
| tokenable_id   | BIGINT       | NOT NULL           | 使用者 ID               |
| name           | VARCHAR(255) | NOT NULL           | Token 名稱              |
| token          | VARCHAR(64)  | UNIQUE, NOT NULL   | 加密 token              |
| abilities      | TEXT         | NULLABLE           | 權限列表 (JSON)         |
| last_used_at   | TIMESTAMP    | NULLABLE           | 最後使用時間            |
| expires_at     | TIMESTAMP    | NULLABLE           | 過期時間                |
| created_at     | TIMESTAMP    | NOT NULL           | 建立時間                |
| updated_at     | TIMESTAMP    | NOT NULL           | 更新時間                |

### PasswordResetTokens (密碼重設令牌)

**用途**: 儲存密碼重設請求
**Table**: `password_reset_tokens` (Laravel 內建)

#### 欄位結構

| 欄位名     | 類型         | 限制     | 描述         |
| ---------- | ------------ | -------- | ------------ |
| email      | VARCHAR(255) | PK       | 使用者 email |
| token      | VARCHAR(255) | NOT NULL | 重設 token   |
| created_at | TIMESTAMP    | NOT NULL | 建立時間     |

### EmailVerifications (信箱驗證)

**用途**: 儲存信箱驗證令牌
**Table**: `email_verifications` (自定義)

#### 欄位結構

| 欄位名      | 類型         | 限制               | 描述                       |
| ----------- | ------------ | ------------------ | -------------------------- |
| id          | BIGINT       | PK, AUTO_INCREMENT | 驗證 ID                    |
| user_type   | VARCHAR(50)  | NOT NULL           | 使用者類型 (user/sys_user) |
| user_id     | BIGINT       | NOT NULL           | 使用者 ID                  |
| email       | VARCHAR(255) | NOT NULL           | 待驗證 email               |
| token       | VARCHAR(255) | UNIQUE, NOT NULL   | 驗證 token                 |
| expires_at  | TIMESTAMP    | NOT NULL           | 過期時間                   |
| verified_at | TIMESTAMP    | NULLABLE           | 驗證時間                   |
| created_at  | TIMESTAMP    | NOT NULL           | 建立時間                   |

## 關聯關係

### PersonalAccessTokens 關聯

- **Polymorphic 關聯**:
  - tokenable_type = 'App\\Models\\User', tokenable_id → users.id
  - tokenable_type = 'App\\Models\\SysUser', tokenable_id → sys_users.id

### EmailVerifications 關聯

- **Polymorphic 關聯**:
  - user_type = 'user', user_id → users.id
  - user_type = 'sys_user', user_id → sys_users.id

## 索引策略

### Users Table

- PRIMARY KEY (id)
- UNIQUE INDEX (username)
- UNIQUE INDEX (email)
- INDEX (email_verified_at) - 用於查詢已驗證使用者

### SysUsers Table

- PRIMARY KEY (id)
- UNIQUE INDEX (username)
- UNIQUE INDEX (email)
- INDEX (is_super_admin) - 用於管理員權限查詢

### PersonalAccessTokens Table

- PRIMARY KEY (id)
- UNIQUE INDEX (token)
- INDEX (tokenable_type, tokenable_id)
- INDEX (expires_at) - 用於清理過期 token

### EmailVerifications Table

- PRIMARY KEY (id)
- UNIQUE INDEX (token)
- INDEX (user_type, user_id)
- INDEX (expires_at) - 用於清理過期驗證

## 資料完整性約束

### 外鍵約束

- 無硬性外鍵約束 (使用 Polymorphic 關聯)
- 應用層級確保資料一致性

### 業務邏輯約束

1. **唯一性檢查**: username, email 在各自表中唯一
2. **密碼安全**: 必須經過 bcrypt 加密
3. **Token 過期**: 所有 token 必須設定適當過期時間
4. **信箱驗證**: 新註冊帳戶預設未驗證狀態

## 資料遷移策略

### Migration 檔案順序

1. `create_users_table.php`
2. `create_sys_users_table.php`
3. `create_personal_access_tokens_table.php` (Sanctum)
4. `create_password_reset_tokens_table.php` (Laravel)
5. `create_email_verifications_table.php`

### Seeder 資料

1. **預設管理員帳戶**: 系統初始化時建立
2. **測試使用者**: 開發環境用於測試
3. **權限設定**: 預設權限配置

## 效能考量

### 查詢最佳化

- 適當的索引設計
- 避免 N+1 查詢問題
- 使用 Eager Loading

### 快取策略

- 使用者權限快取
- Token 驗證快取
- 頻繁查詢結果快取

### 資料庫連線

- 連線池管理
- 讀寫分離 (如需要)
- 查詢監控
