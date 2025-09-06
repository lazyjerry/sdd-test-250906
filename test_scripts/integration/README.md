# 整合測試

本目錄包含跨模組、跨功能的整合測試腳本。

## 🔗 測試範圍

本目錄的測試專注於不同功能模組間的整合驗證：

- 🔄 端到端工作流程測試
- 🌐 API 整合測試
- 📊 資料一致性測試
- 🔗 第三方服務整合
- 📱 前後端整合驗證

## 📋 測試腳本

目前無測試腳本，建議新增以下測試：

### 建議新增的測試

#### `test_complete_user_journey.sh`

**功能**: 完整用戶旅程測試  
**測試內容**:

- ✅ 用戶註冊 → 驗證 → 登入 → 使用功能 → 登出
- ✅ 資料流完整性檢查
- ✅ 狀態管理驗證
- ✅ 錯誤恢復測試

#### `test_auth_integration.sh`

**功能**: 認證系統整合測試  
**測試內容**:

- ✅ 認證與權限整合
- ✅ Session 管理
- ✅ Token 生命週期
- ✅ 多重認證流程

#### `test_api_workflow.sh`

**功能**: API 工作流程測試  
**測試內容**:

- ✅ API 調用序列
- ✅ 資料依賴關係
- ✅ 錯誤傳播
- ✅ 回滾機制

#### `test_cross_module.sh`

**功能**: 跨模組功能測試  
**測試內容**:

- ✅ 模組間通信
- ✅ 資料同步
- ✅ 事件處理
- ✅ 快取一致性

## 🚀 使用範例

```bash
# 建立完整用戶旅程測試
cat > test_complete_user_journey.sh << 'EOF'
#!/bin/bash

echo "🧪 開始完整用戶旅程測試..."
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

# 產生隨機用戶資料
RANDOM_ID=$(date +%s)
TEST_USERNAME="testuser$RANDOM_ID"
TEST_EMAIL="test$RANDOM_ID@example.com"
TEST_PASSWORD="testpassword123"

echo ""
echo "👤 測試用戶資料:"
echo "Username: $TEST_USERNAME"
echo "Email: $TEST_EMAIL"
echo "Password: $TEST_PASSWORD"

# 第一階段：用戶註冊
echo ""
echo "📝 階段 1: 用戶註冊..."
REGISTER_RESPONSE=$(curl -s -X POST "$API_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"username\": \"$TEST_USERNAME\",
    \"email\": \"$TEST_EMAIL\",
    \"password\": \"$TEST_PASSWORD\",
    \"password_confirmation\": \"$TEST_PASSWORD\"
  }")

echo "註冊回應: $REGISTER_RESPONSE"

# 檢查註冊是否成功
if echo "$REGISTER_RESPONSE" | grep -q "success"; then
    echo "✅ 用戶註冊成功"
else
    echo "❌ 用戶註冊失敗"
    exit 1
fi

# 第二階段：用戶登入（假設無需email驗證）
echo ""
echo "🔐 階段 2: 用戶登入..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"username\": \"$TEST_USERNAME\",
    \"password\": \"$TEST_PASSWORD\"
  }")

echo "登入回應: $LOGIN_RESPONSE"

# 提取 token
TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "❌ 登入失敗，無法獲取 token"
    exit 1
fi
echo "✅ 用戶登入成功，token: ${TOKEN:0:20}..."

# 第三階段：獲取用戶資料
echo ""
echo "👤 階段 3: 獲取用戶資料..."
PROFILE_RESPONSE=$(curl -s -X GET "$API_URL/auth/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "用戶資料回應: $PROFILE_RESPONSE"

# 檢查用戶資料是否正確
if echo "$PROFILE_RESPONSE" | grep -q "$TEST_USERNAME"; then
    echo "✅ 用戶資料獲取成功"
else
    echo "❌ 用戶資料獲取失敗"
fi

# 第四階段：更新用戶資料
echo ""
echo "✏️ 階段 4: 更新用戶資料..."
UPDATE_RESPONSE=$(curl -s -X PUT "$API_URL/auth/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"name\": \"Updated $TEST_USERNAME\"
  }")

echo "更新回應: $UPDATE_RESPONSE"

# 第五階段：用戶登出
echo ""
echo "🚪 階段 5: 用戶登出..."
LOGOUT_RESPONSE=$(curl -s -X POST "$API_URL/auth/logout" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "登出回應: $LOGOUT_RESPONSE"

# 第六階段：驗證 token 失效
echo ""
echo "🔒 階段 6: 驗證 token 失效..."
INVALID_RESPONSE=$(curl -s -X GET "$API_URL/auth/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "失效驗證回應: $INVALID_RESPONSE"

if echo "$INVALID_RESPONSE" | grep -q "Unauthenticated"; then
    echo "✅ Token 正確失效"
else
    echo "⚠️ Token 可能未正確失效"
fi

echo ""
echo "📊 完整用戶旅程測試完成"
echo "======================================"
echo "測試總結："
echo "1. ✅ 用戶註冊"
echo "2. ✅ 用戶登入"
echo "3. ✅ 獲取用戶資料"
echo "4. ✅ 更新用戶資料"
echo "5. ✅ 用戶登出"
echo "6. ✅ Token 失效驗證"
EOF

chmod +x test_complete_user_journey.sh
```

## 📊 測試策略

### 端到端測試

整合測試應該模擬真實用戶行為：

1. **完整工作流程**

   - 從開始到結束的完整操作
   - 包含錯誤處理和恢復
   - 驗證資料一致性

2. **跨模組驗證**

   - 認證與業務邏輯整合
   - 資料庫與 API 整合
   - 前端與後端整合

3. **效能測試**
   - 併發操作測試
   - 資源使用監控
   - 回應時間驗證

### 資料完整性

確保整合過程中資料保持完整：

```bash
# 資料一致性檢查範例
echo "🔍 檢查資料一致性..."

# 獲取用戶資料
USER_DATA=$(curl -s -X GET "$API_URL/auth/profile" \
  -H "Authorization: Bearer $TOKEN")

# 解析並驗證關鍵欄位
USERNAME_MATCH=$(echo "$USER_DATA" | grep -o "\"username\":\"[^\"]*\"")
EMAIL_MATCH=$(echo "$USER_DATA" | grep -o "\"email\":\"[^\"]*\"")

echo "用戶名檢查: $USERNAME_MATCH"
echo "郵箱檢查: $EMAIL_MATCH"
```

## 🔧 測試工具

### 測試輔助函數

建立共用的測試函數：

```bash
# 建立測試工具庫
cat > ../common_integration_utils.sh << 'EOF'
#!/bin/bash

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 測試結果追蹤
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# 記錄測試開始
start_test() {
    local test_name="$1"
    echo -e "${BLUE}🧪 開始測試: $test_name${NC}"
    ((TOTAL_TESTS++))
}

# 記錄測試成功
pass_test() {
    local message="$1"
    echo -e "${GREEN}✅ $message${NC}"
    ((PASSED_TESTS++))
}

# 記錄測試失敗
fail_test() {
    local message="$1"
    echo -e "${RED}❌ $message${NC}"
    ((FAILED_TESTS++))
}

# 顯示測試總結
show_test_summary() {
    echo ""
    echo "======================================"
    echo -e "${BLUE}📊 測試總結${NC}"
    echo "總測試數: $TOTAL_TESTS"
    echo -e "通過: ${GREEN}$PASSED_TESTS${NC}"
    echo -e "失敗: ${RED}$FAILED_TESTS${NC}"

    if [ $FAILED_TESTS -eq 0 ]; then
        echo -e "${GREEN}🎉 所有測試通過！${NC}"
        return 0
    else
        echo -e "${RED}💥 有測試失敗${NC}"
        return 1
    fi
}

# API 測試輔助函數
test_api_endpoint() {
    local method="$1"
    local endpoint="$2"
    local token="$3"
    local data="$4"

    local response
    if [ "$method" = "GET" ]; then
        response=$(curl -s -X GET "$endpoint" \
          -H "Authorization: Bearer $token" \
          -H "Accept: application/json")
    elif [ "$method" = "POST" ]; then
        response=$(curl -s -X POST "$endpoint" \
          -H "Authorization: Bearer $token" \
          -H "Content-Type: application/json" \
          -d "$data")
    fi

    echo "$response"
}
EOF
```

## 📝 測試文檔

### 測試計劃

每個整合測試都應該包含：

1. **測試目標**: 明確的測試目標
2. **前置條件**: 測試執行前的準備工作
3. **測試步驟**: 詳細的操作步驟
4. **預期結果**: 每個步驟的預期輸出
5. **清理作業**: 測試後的清理工作

### 報告格式

測試報告應該包含：

```bash
# 測試報告範例
cat > test_report_template.md << 'EOF'
# 整合測試報告

## 測試資訊
- **測試日期**: $(date)
- **測試環境**: Development
- **測試版本**: Laravel 12

## 測試結果
| 測試項目 | 狀態 | 執行時間 | 備註 |
|---------|------|----------|------|
| 用戶註冊流程 | ✅ | 2.3s | 正常 |
| 認證整合 | ✅ | 1.8s | 正常 |
| 資料一致性 | ❌ | 0.5s | 發現問題 |

## 問題記錄
1. **資料一致性問題**:
   - 描述: 用戶更新後資料不同步
   - 嚴重性: 中等
   - 解決方案: 需要檢查快取機制

## 建議
1. 增加快取清除機制
2. 改善錯誤處理
3. 增強日誌記錄
EOF
```

## ⚠️ 注意事項

### 測試環境

- 使用獨立的測試環境
- 避免影響開發或生產資料
- 確保測試資料的清理

### 效能考慮

- 整合測試可能較慢
- 考慮測試的執行順序
- 平行化執行適當的測試

### 維護性

- 保持測試腳本的可讀性
- 使用共用的測試工具
- 定期更新測試用例

## 🔗 相關測試

- **認證測試**: [`../auth/`](../auth/) - 基礎認證功能
- **用戶測試**: [`../user/`](../user/) - 用戶相關功能
- **管理員測試**: [`../admin/`](../admin/) - 管理員功能
- **主要測試目錄**: [`../`](../) - 測試框架總覽
