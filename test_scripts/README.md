# 測試腳本目錄

本目錄包含各種手動測試和整合測試腳本，用於驗證系統功能的正確性。

## 📁 目錄結構

測試腳本已按功能分類組織：

```
test_scripts/
├── auth/                          # 認證相關測試
│   ├── test_email_verification.sh    # 信箱驗證測試
│   ├── EMAIL_VERIFICATION_TESTING_GUIDE.md  # 驗證測試指南
│   └── README.md                      # 認證測試說明
├── user/                          # 用戶功能測試
│   └── README.md                      # 用戶測試說明與範例
├── admin/                         # 管理員功能測試
│   └── README.md                      # 管理員測試說明與範例
├── integration/                   # 整合測試
│   └── README.md                      # 整合測試說明與範例
└── README.md                      # 本說明文件
```

## 🔐 認證相關測試

### `auth/test_email_verification.sh`

郵箱驗證功能的完整手動測試腳本。

**功能範圍:**

- 用戶註冊流程測試
- 未驗證用戶登入限制驗證
- POST API 郵箱驗證測試
- GET 路由郵箱驗證測試
- 錯誤情況處理測試
- 參數驗證測試

**使用方法:**

```bash
# 確保服務正在運行
cd example-app && ./vendor/bin/sail up -d

# 執行認證測試
./test_scripts/auth/test_email_verification.sh
```

**詳細指南**: 參考 `auth/EMAIL_VERIFICATION_TESTING_GUIDE.md` 了解完整的測試流程。

## 👤 用戶功能測試

查看 `user/README.md` 了解用戶相關功能的測試指南和範例腳本。

## 🛡️ 管理員功能測試

查看 `admin/README.md` 了解管理員權限相關功能的測試指南和範例腳本。

## 🔗 整合測試

查看 `integration/README.md` 了解跨模組整合測試的指南和範例腳本。

**前置條件:**

- Laravel Sail 服務運行中
- 資料庫連接正常
- 郵件配置正確 (建議使用 `MAIL_DRIVER=log`)

## 🚀 使用指南

### 快速開始

1. **確保開發環境正常運行:**

```bash
cd example-app
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
```

2. **選擇並執行測試腳本:**

```bash
# 執行認證測試
./test_scripts/auth/test_email_verification.sh

# 執行其他類別的測試
./test_scripts/user/test_user_operations.sh      # 用戶功能測試 (待建立)
./test_scripts/admin/test_user_management.sh     # 管理員測試 (待建立)
./test_scripts/integration/test_complete_user_journey.sh  # 整合測試 (待建立)
```

3. **查看測試結果:**
   腳本會輸出詳細的測試步驟和結果，包括：

- ✅ 成功的測試項目
- ❌ 失敗的測試項目
- ⚠️ 需要注意的警告

### 自訂測試

您可以根據需要修改測試腳本：

1. **修改測試資料:**

   - 編輯腳本中的 `USERNAME`、`EMAIL` 等變數
   - 調整 API 端點 URL

2. **新增測試案例:**

   - 複製現有的測試模式
   - 添加新的 curl 請求和驗證邏輯

3. **調整驗證條件:**
   - 修改 `grep` 條件來匹配不同的回應內容
   - 調整成功/失敗的判斷邏輯

## ⚠️ 注意事項

### 測試環境

- **資料隔離**: 測試腳本會建立新的測試資料，不會影響現有資料
- **網路依賴**: 確保 `localhost:8000` 可以正常存取
- **權限要求**: 腳本需要執行權限 (`chmod +x`)

### 已知限制

- **簽名驗證**: 手動測試腳本中的簽名是模擬的，實際驗證會失敗
- **時間敏感**: 某些測試可能受到 throttle 限制影響
- **環境依賴**: 依賴特定的 Laravel 配置和環境變數

### 錯誤排除

如果測試失敗，請檢查：

1. **服務狀態**: `./vendor/bin/sail ps`
2. **資料庫連接**: `./vendor/bin/sail artisan migrate:status`
3. **日誌檔案**: `./vendor/bin/sail logs`
4. **環境配置**: 檢查 `.env` 檔案設定

## 🤝 貢獻指南

歡迎添加新的測試腳本！請遵循以下規範：

1. **檔案命名**: 使用描述性的名稱，如 `test_功能名稱.sh`
2. **檔案位置**: 放置在對應的分類目錄中 (`auth/`, `user/`, `admin/`, `integration/`)
3. **腳本結構**: 包含清晰的步驟說明和結果驗證
4. **錯誤處理**: 提供適當的錯誤訊息和排除建議
5. **文件更新**: 在對應目錄的 README.md 中添加新腳本的說明

### 腳本模板

```bash
#!/bin/bash

# 功能測試腳本
# 描述: 測試特定功能的完整流程

echo "🧪 開始 [功能名稱] 測試..."
echo "======================================"

BASE_URL="http://localhost:8000"
API_URL="$BASE_URL/api/v1"

# 檢查服務狀態
echo "📡 檢查服務狀態..."
if ! curl -s "$BASE_URL" > /dev/null; then
    echo "❌ 服務未運行，請先啟動: ./vendor/bin/sail up -d"
    exit 1
fi
echo "✅ 服務正常運行"

# 測試步驟 1
echo ""
echo "📝 步驟 1: [步驟描述]..."
# 測試邏輯

# 更多測試步驟...

echo ""
echo "📊 測試總結:"
echo "======================================"
# 總結結果
```
