# 認證功能測試

本目錄包含認證相關功能的手動測試腳本。

## 📋 測試腳本

### `test_email_verification.sh`

**功能**: 郵箱驗證功能完整測試  
**描述**: 測試用戶註冊、郵箱驗證、登入流程和錯誤處理

**使用方法**:

```bash
# 從專案根目錄執行
./test_scripts/auth/test_email_verification.sh

# 或者進入目錄執行
cd test_scripts/auth && ./test_email_verification.sh
```

**測試內容**:

- ✅ 用戶註冊流程
- ✅ 未驗證用戶登入限制
- ✅ POST API 郵箱驗證
- ✅ GET 路由郵箱驗證
- ✅ 錯誤情況處理
- ✅ 參數驗證

**詳細指南**: 參考 `EMAIL_VERIFICATION_TESTING_GUIDE.md`

## 🚀 快速開始

```bash
# 確保服務運行
cd ../../example-app && ./vendor/bin/sail up -d

# 執行郵箱驗證測試
cd ../test_scripts/auth
./test_email_verification.sh
```

## 📝 新增測試腳本

### 命名規範

- 使用 `test_` 前綴
- 描述性檔案名稱
- 使用底線分隔單字

### 腳本結構

```bash
#!/bin/bash

# 功能描述
# 使用方法: ./test_script_name.sh

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

# 測試邏輯...
```

## 🎯 待新增的測試

以下是建議新增的認證功能測試：

### 登入功能測試 (`test_login.sh`)

- 正常登入流程
- 錯誤憑證處理
- 速率限制測試

### 密碼重設測試 (`test_password_reset.sh`)

- 忘記密碼流程
- 重設密碼驗證
- Token 過期處理

### Token 管理測試 (`test_token_management.sh`)

- Token 生成和驗證
- Token 過期處理
- Token 撤銷功能

### 註冊功能測試 (`test_registration.sh`)

- 用戶註冊流程
- 資料驗證測試
- 重複註冊處理

## 🔧 維護指南

### 更新腳本

- 隨 API 變更更新測試腳本
- 保持錯誤訊息同步
- 更新期望的回應格式

### 測試環境

- 確保測試資料隔離
- 使用動態測試資料
- 避免硬編碼值

### 錯誤處理

- 提供清晰的錯誤訊息
- 包含故障排除建議
- 記錄測試結果
