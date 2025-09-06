# Insomnia API Collection

這個資料夾包含了 Laravel JDemo 項目的 API 集合，用於 Insomnia REST 客戶端測試。

## 📁 檔案說明

### `laravel-api.yaml` ⭐ 整合版本

**完整 API 集合 (YAML 格式)** - 包含所有可用的 API 端點

- **🔐 Authentication** - 認證相關 API
  - 使用者註冊/登入/登出
  - 密碼重設和電子郵件驗證
- **👤 User Management** - 使用者個人管理
  - 查看/更新個人資料
  - 變更密碼
- **👨‍💼 Admin Management** - 管理員功能
  - 角色導向使用者建立
  - 使用者 CRUD 操作
  - 批量操作
  - 統計和監控

## 🚀 使用方法

### 1. 匯入 Insomnia

1. 開啟 Insomnia REST 客戶端
2. 點擊 `Application` → `Import` 或使用快捷鍵 `Cmd+Shift+I`
3. 選擇 `laravel-api.yaml` 檔案
4. Insomnia 會自動識別並匯入完整的 API 集合

### 2. 環境設定

每個集合都包含多個環境：

#### Development 環境

```yaml
base_url: http://localhost/api/v1
user_token: ""
admin_token: ""
```

#### Staging 環境

```yaml
base_url: https://staging.example.com/api/v1
user_token: ""
admin_token: ""
```

#### Production 環境

```yaml
base_url: https://api.example.com/api/v1
user_token: ""
admin_token: ""
```

### 3. Token 管理

**使用流程：**

1. **管理員登入**

   - 使用 `Admin Login` 請求
   - 複製回應中的 `token`
   - 將 token 貼到環境變數 `admin_token`

2. **一般使用者登入**

   - 使用 `User Login` 請求
   - 複製回應中的 `token`
   - 將 token 貼到環境變數 `user_token`

3. **使用 API**
   - 需要認證的請求會自動使用對應的 token
   - `{{ _.admin_token }}` 用於管理員功能
   - `{{ _.user_token }}` 用於使用者功能

## 🆕 YAML 格式優勢

- **可讀性更佳**: 更清晰的結構和格式
- **更易維護**: 支援註解和多行文字
- **版本控制友善**: Git diff 更清楚
- **現代化標準**: Insomnia 5.0+ 推薦格式
- **檔案大小**: 比 JSON 更緊湊

## 🔑 角色權限差異

### User 角色 (一般使用者)

- ✅ 公開註冊
- ✅ 查看/更新個人資料
- ✅ 變更個人密碼
- ❌ 無法存取管理員功能

### Admin 角色 (管理員)

- ✅ 所有 User 角色功能
- ✅ 建立使用者（指定角色）
- ✅ 查看所有使用者
- ✅ 更新任何使用者資料
- ✅ 刪除使用者
- ✅ 批量操作
- ✅ 統計和監控

## 📋 測試流程範例

### 完整角色測試流程

1. **公開註冊測試**

   ```
   POST /auth/register
   → 自動分配 user 角色
   ```

2. **管理員建立使用者測試**

   ```
   POST /admin/register (role: user)
   POST /admin/register (role: admin)
   → 管理員可指定角色
   ```

3. **權限驗證測試**
   ```
   User Token → GET /admin/users (應該失敗)
   Admin Token → GET /admin/users (應該成功)
   ```

### API 端點統計

- **認證相關**: 8 個端點
- **使用者管理**: 3 個端點
- **管理員註冊**: 2 個端點
- **管理員 CRUD**: 8 個端點
- **管理員批量操作**: 5 個端點
- **管理員統計監控**: 6 個端點
- **總計**: 32 個端點

## 📦 檔案格式比較

| 功能          | YAML       | JSON   |
| ------------- | ---------- | ------ |
| 可讀性        | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| 檔案大小      | 較小       | 較大   |
| 註解支援      | ✅         | ❌     |
| 多行文字      | ✅         | 需轉義 |
| Git diff      | 清晰       | 混亂   |
| Insomnia 版本 | 5.0+       | 4.0+   |

## 🛠️ 故障排除

### 常見問題

1. **401 Unauthorized**

   - 檢查 token 是否正確設定
   - 確認 token 未過期
   - 驗證環境變數名稱

2. **403 Forbidden**

   - 檢查使用者角色權限
   - 確認使用正確的 token (admin_token vs user_token)

3. **422 Validation Error**
   - 檢查請求格式和必填欄位
   - 確認密碼符合強度要求
   - 驗證電子郵件格式

### 測試建議

- **優先使用 YAML 格式**進行 API 測試
- **完整集合**適合全面功能測試
- **角色集合**適合權限驗證測試
- 定期更新環境變數中的 token
- 測試不同角色的權限邊界

## 📚 相關文件

- [角色導向註冊文件](../docs/role-based-registration.md)
- [API 規格文件](../docs/README.md)
- [測試指南](../test_scripts/README.md)
- [主要 README](../README.md)
