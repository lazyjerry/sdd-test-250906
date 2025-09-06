# 用戶管理功能測試

本目錄包含用戶個人資料管理相關功能的手動測試腳本。

## 🎯 測試範圍

本目錄的測試專注於已認證用戶的個人資料管理功能：

- 👤 個人資料查看
- ✏️ 個人資料更新
- 🔐 密碼修改
- 📧 郵箱變更
- 🔄 資料驗證

## 📋 測試腳本

目前無測試腳本，建議新增以下測試：

### 建議新增的測試

#### `test_profile_management.sh`

**功能**: 個人資料管理完整測試  
**測試內容**:

- ✅ 獲取個人資料
- ✅ 更新個人資料
- ✅ 資料驗證規則
- ✅ 無效資料處理

#### `test_password_change.sh`

**功能**: 密碼修改功能測試  
**測試內容**:

- ✅ 密碼修改流程
- ✅ 原密碼驗證
- ✅ 新密碼強度驗證
- ✅ 密碼確認檢查

#### `test_user_data_validation.sh`

**功能**: 用戶資料驗證測試  
**測試內容**:

- ✅ 必填欄位驗證
- ✅ 資料格式驗證
- ✅ 長度限制測試
- ✅ 特殊字符處理

## 🚀 使用範例

```bash
# 建立測試腳本範例
cat > test_profile_management.sh << 'EOF'
#!/bin/bash

echo "🧪 開始個人資料管理測試..."
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

# 1. 登入獲取 Token
echo ""
echo "🔐 步驟 1: 用戶登入..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "password123"
  }')

TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "❌ 登入失敗，請先註冊用戶"
    exit 1
fi
echo "✅ 登入成功，Token: ${TOKEN:0:20}..."

# 2. 獲取個人資料
echo ""
echo "👤 步驟 2: 獲取個人資料..."
PROFILE_RESPONSE=$(curl -s -X GET "$API_URL/users/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "個人資料回應: $PROFILE_RESPONSE"

# 3. 更新個人資料
echo ""
echo "✏️ 步驟 3: 更新個人資料..."
UPDATE_RESPONSE=$(curl -s -X PUT "$API_URL/users/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Name",
    "phone": "+886987654321"
  }')

echo "更新回應: $UPDATE_RESPONSE"

echo ""
echo "📊 測試完成"
EOF

chmod +x test_profile_management.sh
```

## 📝 開發指南

### API 端點

用戶管理相關的 API 端點：

- `GET /api/v1/users/profile` - 獲取個人資料
- `PUT /api/v1/users/profile` - 更新個人資料
- `PUT /api/v1/users/change-password` - 修改密碼

### 認證要求

所有用戶管理 API 都需要：

- Bearer Token 認證
- 有效的用戶 session
- 已驗證的郵箱地址

### 測試資料

建議使用動態測試資料：

```bash
TIMESTAMP=$(date +%s)
TEST_NAME="testuser_$TIMESTAMP"
TEST_EMAIL="test_$TIMESTAMP@example.com"
```

## 🔗 相關測試

- **認證測試**: [`../auth/`](../auth/) - 登入、註冊等認證功能
- **管理員測試**: [`../admin/`](../admin/) - 管理員對用戶的操作
- **整合測試**: [`../integration/`](../integration/) - 跨功能整合測試
