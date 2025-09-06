# 角色基礎註冊系統文檔

## 概述

本系統實現了分層的用戶註冊機制，支援普通用戶自主註冊和管理員協助註冊，確保角色權限的安全管控。

## 註冊端點

### 1. 普通用戶註冊 `POST /api/v1/auth/register`

**用途**: 允許任何人註冊為普通用戶

**特性**:

- 預設角色: `user`
- 不接受角色參數（安全考量）
- 需要信箱驗證
- 生成認證 token

**請求範例**:

```json
{
	"name": "張三",
	"username": "zhangsan",
	"email": "zhangsan@example.com",
	"phone": "0900123456",
	"password": "SecurePassword123!",
	"password_confirmation": "SecurePassword123!"
}
```

**回應範例**:

```json
{
	"status": "success",
	"message": "註冊成功",
	"data": {
		"user": {
			"id": 1,
			"username": "zhangsan",
			"email": "zhangsan@example.com",
			"name": "張三",
			"phone": "0900123456",
			"email_verified_at": null,
			"created_at": "2025-09-06T10:00:00.000000Z",
			"updated_at": "2025-09-06T10:00:00.000000Z"
		},
		"token": "1|ABC123..."
	}
}
```

### 2. 管理員註冊用戶 `POST /api/v1/admin/register`

**用途**: 允許管理員創建任何角色的用戶

**權限要求**:

- 需要管理員認證
- 當前用戶必須具有 `admin` 角色

**特性**:

- 可指定角色: `user` 或 `admin`
- 創建的用戶預設已驗證信箱
- 不生成 token（由管理員代為創建）
- 記錄創建者資訊

**請求範例**:

```json
{
	"name": "李四",
	"username": "lisi",
	"email": "lisi@example.com",
	"phone": "0900654321",
	"password": "AdminPassword123!",
	"password_confirmation": "AdminPassword123!",
	"role": "admin"
}
```

**回應範例**:

```json
{
	"status": "success",
	"message": "用戶註冊成功",
	"data": {
		"user": {
			"id": 2,
			"username": "lisi",
			"email": "lisi@example.com",
			"name": "李四",
			"phone": "0900654321",
			"role": "admin",
			"email_verified_at": "2025-09-06T10:00:00.000000Z",
			"created_at": "2025-09-06T10:00:00.000000Z",
			"updated_at": "2025-09-06T10:00:00.000000Z"
		},
		"created_by": {
			"id": 1,
			"username": "admin_user"
		}
	}
}
```

## 角色權限說明

### User 角色權限

- 存取個人資料
- 修改個人資料
- 變更密碼
- **無法存取**: 管理員功能、用戶管理、系統設定

### Admin 角色權限

- 所有 User 角色權限
- 查看所有用戶清單
- 編輯任何用戶資料
- 創建新用戶（任何角色）
- 刪除/停用用戶
- 重置用戶密碼
- 批量用戶操作

## 安全機制

### 1. 角色隔離

- 普通註冊端點忽略角色參數
- 管理員註冊需要現有管理員權限
- 角色提升需通過管理員操作

### 2. 權限驗證

- API 路由層級權限控制
- 中間件自動驗證用戶角色
- 所有敏感操作需要認證

### 3. 資料驗證

- 嚴格的輸入驗證規則
- 密碼強度要求
- 重複資料檢查

## 測試覆蓋

### 功能測試

- `RoleBasedRegistrationTest`: 角色註冊整合測試
- `AdminRegisterContractTest`: 管理員註冊 API 合約測試
- `RegisterContractTest`: 普通註冊 API 合約測試

### 測試案例

1. ✅ 普通用戶註冊預設角色
2. ✅ 普通註冊忽略角色參數
3. ✅ 管理員創建不同角色用戶
4. ✅ 權限不足時的拒絕訪問
5. ✅ 未認證用戶的拒絕訪問
6. ✅ 資料驗證錯誤處理
7. ✅ 註冊後角色權限驗證

## 使用建議

### 首次部署

1. 手動在資料庫創建第一個管理員用戶
2. 使用該管理員創建其他管理員
3. 開放普通用戶註冊

### 生產環境

- 考慮添加註冊審核機制
- 實施更嚴格的密碼政策
- 定期檢查管理員權限分配
- 監控異常註冊活動

## API 路由總覽

```
POST /api/v1/auth/register          # 普通用戶註冊
POST /api/v1/admin/register         # 管理員註冊用戶 (需認證)
```

## 錯誤代碼

| 錯誤代碼                  | HTTP 狀態 | 說明                         |
| ------------------------- | --------- | ---------------------------- |
| `INSUFFICIENT_PRIVILEGES` | 403       | 權限不足，無法執行管理員操作 |
| `VALIDATION_ERROR`        | 422       | 資料驗證失敗                 |
| `DUPLICATE_EMAIL`         | 422       | 信箱已被使用                 |
| `DUPLICATE_USERNAME`      | 422       | 用戶名已被使用               |
