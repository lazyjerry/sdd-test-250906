# 管理員功能測試

本目錄包含管理員專用功能的手動測試腳本。

## 🔑 測試範圍

本目錄的測試專注於管理員權限相關的功能：

- 👥 用戶列表管理
- 🔍 用戶資料查看
- ✏️ 用戶資料修改
- 🔐 用戶密碼重設
- 🛡️ 權限控制驗證

## 📋 測試腳本

目前無測試腳本，建議新增以下測試：

### 建議新增的測試

#### `test_user_management.sh`

**功能**: 管理員用戶管理功能測試  
**測試內容**:

- ✅ 獲取用戶列表
- ✅ 查看特定用戶資料
- ✅ 更新用戶資料
- ✅ 重設用戶密碼
- ✅ 權限驗證

#### `test_admin_permissions.sh`

**功能**: 管理員權限控制測試  
**測試內容**:

- ✅ 管理員身份驗證
- ✅ 非管理員訪問拒絕
- ✅ 權限邊界測試
- ✅ 角色權限檢查

#### `test_user_operations.sh`

**功能**: 用戶操作功能測試  
**測試內容**:

- ✅ 用戶狀態管理
- ✅ 批量操作測試
- ✅ 資料完整性檢查
- ✅ 操作日誌記錄

## 🚀 使用範例

```bash
# 建立管理員測試腳本範例
cat > test_user_management.sh << 'EOF'
#!/bin/bash

echo "🧪 開始管理員用戶管理測試..."
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

# 1. 管理員登入
echo ""
echo "🔐 步驟 1: 管理員登入..."
ADMIN_LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123"
  }')

ADMIN_TOKEN=$(echo "$ADMIN_LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$ADMIN_TOKEN" ]; then
    echo "❌ 管理員登入失敗，請確認管理員帳戶存在"
    exit 1
fi
echo "✅ 管理員登入成功"

# 2. 獲取用戶列表
echo ""
echo "👥 步驟 2: 獲取用戶列表..."
USERS_RESPONSE=$(curl -s -X GET "$API_URL/admin/users" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json")

echo "用戶列表回應: $USERS_RESPONSE"

# 3. 獲取特定用戶
echo ""
echo "🔍 步驟 3: 獲取特定用戶資料..."
USER_RESPONSE=$(curl -s -X GET "$API_URL/admin/users/1" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json")

echo "用戶資料回應: $USER_RESPONSE"

# 4. 更新用戶資料
echo ""
echo "✏️ 步驟 4: 更新用戶資料..."
UPDATE_RESPONSE=$(curl -s -X PUT "$API_URL/admin/users/1" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin Updated Name",
    "email": "admin_updated@example.com"
  }')

echo "更新回應: $UPDATE_RESPONSE"

# 5. 重設用戶密碼
echo ""
echo "🔐 步驟 5: 重設用戶密碼..."
RESET_RESPONSE=$(curl -s -X POST "$API_URL/admin/users/1/reset-password" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
  }')

echo "密碼重設回應: $RESET_RESPONSE"

echo ""
echo "📊 管理員測試完成"
EOF

chmod +x test_user_management.sh
```

## 📝 開發指南

### API 端點

管理員相關的 API 端點：

- `GET /api/v1/admin/users` - 獲取用戶列表
- `GET /api/v1/admin/users/{id}` - 獲取特定用戶
- `PUT /api/v1/admin/users/{id}` - 更新用戶資料
- `POST /api/v1/admin/users/{id}/reset-password` - 重設用戶密碼

### 權限要求

所有管理員 API 都需要：

- Bearer Token 認證
- 管理員角色權限 (`role: 'admin'`)
- 有效的 session

### 測試準備

執行管理員測試前需要：

1. 建立管理員帳戶
2. 建立測試用戶
3. 確認權限設置正確

```bash
# 建立管理員帳戶（在 Laravel tinker 中執行）
./vendor/bin/sail artisan tinker

# 創建管理員用戶
User::create([
    'username' => 'admin',
    'email' => 'admin@example.com',
    'password' => Hash::make('admin123'),
    'role' => 'admin',
    'email_verified_at' => now()
]);
```

## ⚠️ 安全注意事項

### 測試隔離

- 使用測試環境
- 避免在生產數據上測試
- 測試後清理資料

### 權限測試

- 驗證非管理員無法訪問
- 測試權限邊界
- 檢查敏感操作保護

### 資料保護

- 避免硬編碼敏感資料
- 使用環境變數
- 測試資料加密

## 🔗 相關測試

- **認證測試**: [`../auth/`](../auth/) - 管理員登入認證
- **用戶測試**: [`../user/`](../user/) - 普通用戶功能對比
- **整合測試**: [`../integration/`](../integration/) - 管理員與用戶互動測試
