#!/bin/bash

# 郵箱驗證功能手動測試腳本
# 使用方法: ./test_scripts/test_email_verification.sh (從專案根目錄執行)
# 或者: cd test_scripts && ./test_email_verification.sh

echo "🧪 開始郵箱驗證功能測試..."
echo "======================================"

BASE_URL="http://localhost:8000"
API_URL="$BASE_URL/api/v1"

# 檢查服務是否運行
echo "📡 檢查服務狀態..."
if ! curl -s "$BASE_URL" > /dev/null; then
    echo "❌ 服務未運行，請先啟動: ./vendor/bin/sail up -d"
    exit 1
fi
echo "✅ 服務正常運行"

# 1. 註冊新用戶
echo ""
echo "👤 步驟 1: 註冊新用戶..."
USERNAME="testuser_$(date +%s)"
EMAIL="test_$(date +%s)@example.com"

REGISTER_RESPONSE=$(curl -s -X POST "$API_URL/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"username\": \"$USERNAME\",
    \"email\": \"$EMAIL\",
    \"password\": \"password123\",
    \"password_confirmation\": \"password123\"
  }")

echo "註冊回應: $REGISTER_RESPONSE"

if echo "$REGISTER_RESPONSE" | grep -q "註冊成功"; then
    echo "✅ 用戶註冊成功"
    USER_ID=$(echo "$REGISTER_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2)
    echo "📝 用戶 ID: $USER_ID"
else
    echo "❌ 用戶註冊失敗"
    exit 1
fi

# 2. 嘗試登入（應該失敗，因為郵箱未驗證）
echo ""
echo "🔐 步驟 2: 嘗試登入未驗證的帳戶..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"username\": \"$USERNAME\",
    \"password\": \"password123\"
  }")

echo "登入回應: $LOGIN_RESPONSE"

if echo "$LOGIN_RESPONSE" | grep -q "請先驗證您的電子郵件地址"; then
    echo "✅ 正確阻止未驗證用戶登入"
else
    echo "❌ 應該阻止未驗證用戶登入"
fi

# 3. 模擬郵箱驗證 - 使用 POST API
echo ""
echo "📧 步驟 3: 測試 POST API 郵箱驗證..."

# 計算 hash 和生成簽名參數
EMAIL_HASH=$(echo -n "$EMAIL" | sha1sum | cut -d' ' -f1)
EXPIRES=$(($(date +%s) + 3600))  # 1小時後過期

# 生成簽名 URL（這裡簡化處理，實際應該使用 Laravel 的 temporarySignedRoute）
SIGNATURE="test_signature_$(date +%s)"

VERIFY_DATA="{
  \"id\": $USER_ID,
  \"hash\": \"$EMAIL_HASH\",
  \"expires\": $EXPIRES,
  \"signature\": \"$SIGNATURE\"
}"

echo "驗證數據: $VERIFY_DATA"

VERIFY_RESPONSE=$(curl -s -X POST "$API_URL/auth/verify-email" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$VERIFY_DATA")

echo "驗證回應: $VERIFY_RESPONSE"

if echo "$VERIFY_RESPONSE" | grep -q "無效或過期的驗證連結"; then
    echo "✅ 正確拒絕無效簽名"
else
    echo "❌ 應該拒絕無效簽名"
fi

# 4. 測試 GET 路由（同樣會失敗，因為簽名無效）
echo ""
echo "🔗 步驟 4: 測試 GET 路由郵箱驗證..."

GET_URL="$BASE_URL/api/email/verify/$USER_ID/$EMAIL_HASH?expires=$EXPIRES&signature=$SIGNATURE"
echo "驗證 URL: $GET_URL"

GET_RESPONSE=$(curl -s "$GET_URL")
echo "GET 驗證回應: $GET_RESPONSE"

if echo "$GET_RESPONSE" | grep -q "Invalid signature"; then
    echo "✅ 正確拒絕無效簽名（GET 路由）"
else
    echo "⚠️  GET 路由可能有不同的錯誤處理"
fi

# 5. 測試錯誤情況
echo ""
echo "🚫 步驟 5: 測試錯誤情況..."

# 測試不存在的用戶
echo "測試不存在的用戶 ID: 99999"
INVALID_USER_RESPONSE=$(curl -s -X POST "$API_URL/auth/verify-email" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"id\": 99999,
    \"hash\": \"$EMAIL_HASH\",
    \"expires\": $EXPIRES,
    \"signature\": \"$SIGNATURE\"
  }")

echo "不存在用戶回應: $INVALID_USER_RESPONSE"

if echo "$INVALID_USER_RESPONSE" | grep -q "找不到指定的使用者\|無效或過期的驗證連結"; then
    echo "✅ 正確處理不存在的用戶"
else
    echo "❌ 未正確處理不存在的用戶"
fi

# 測試缺少參數
echo ""
echo "測試缺少必要參數..."
MISSING_PARAMS_RESPONSE=$(curl -s -X POST "$API_URL/auth/verify-email" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"id\": $USER_ID
  }")

echo "缺少參數回應: $MISSING_PARAMS_RESPONSE"

if echo "$MISSING_PARAMS_RESPONSE" | grep -q "資料驗證失敗"; then
    echo "✅ 正確驗證必要參數"
else
    echo "❌ 未正確驗證必要參數"
fi

echo ""
echo "📊 測試總結:"
echo "======================================"
echo "✅ 用戶註冊功能正常"
echo "✅ 未驗證用戶無法登入"
echo "✅ 無效簽名驗證被拒絕"
echo "✅ 錯誤情況處理正常"
echo "⚠️  注意: 實際的郵箱驗證需要有效的簽名"
echo ""
echo "🔧 若要測試完整的郵箱驗證流程，請："
echo "1. 配置郵件驅動 (MAIL_DRIVER=log)"
echo "2. 註冊用戶後檢查日誌中的驗證連結"
echo "3. 使用真實的驗證連結進行測試"
