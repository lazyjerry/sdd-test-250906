# 用戶驗證規則重構說明

## 概述

已將分散在各個 Controller 中的用戶驗證規則統一整理到專門的 Request 類中，並創建了兩個共用的 trait 來管理驗證規則和 API 響應格式。

## 新增的文件結構

### 1. 核心 Trait

-   `app/Http/Requests/Traits/UserValidationRules.php` - 統一管理所有用戶相關的驗證規則
-   `app/Http/Requests/Traits/ApiResponseFormat.php` - 統一管理 API 響應格式

### 2. Request 類

-   `app/Http/Requests/CreateAdminUserRequest.php` - 管理員創建用戶（已重構）
-   `app/Http/Requests/UserRegistrationRequest.php` - 用戶註冊
-   `app/Http/Requests/AdminRegisterUserRequest.php` - 管理員註冊其他用戶
-   `app/Http/Requests/UserUpdateRequest.php` - 用戶資料更新
-   `app/Http/Requests/UserLoginRequest.php` - 用戶登入
-   `app/Http/Requests/AdminLoginRequest.php` - 管理員登入（已存在）
-   `app/Http/Requests/ForgotPasswordRequest.php` - 忘記密碼
-   `app/Http/Requests/PasswordResetRequest.php` - 密碼重設
-   `app/Http/Requests/EmailVerificationRequest.php` - Email 驗證

## UserValidationRules Trait 功能

### 方法說明

1. **基本驗證規則方法**

    - `getNameRules($required = false)` - 姓名驗證規則
    - `getUsernameRules($ignoreUserId = null)` - 用戶名驗證規則
    - `getEmailRules($required = true, $ignoreUserId = null)` - Email 驗證規則
    - `getPhoneRules()` - 手機號碼驗證規則
    - `getPasswordRules($confirmed = false, $useStrongRules = false)` - 密碼驗證規則
    - `getRoleRules($allowedRoles = ['user', 'admin'])` - 角色驗證規則

2. **預組合驗證規則方法**

    - `getUserRegistrationRules()` - 用戶註冊驗證規則
    - `getAdminCreateUserRules()` - 管理員創建用戶驗證規則
    - `getAdminRegisterUserRules()` - 管理員註冊其他用戶驗證規則
    - `getUserUpdateRules($userId)` - 用戶更新驗證規則

3. **輔助方法**
    - `getDefaultUserRoles()` - 取得預設用戶角色
    - `getAdminRoles()` - 取得管理員角色
    - `getCommonValidationMessages()` - 取得中文驗證訊息
    - `getEnglishValidationMessages()` - 取得英文驗證訊息

## ApiResponseFormat Trait 功能

### 方法說明

1. **驗證失敗響應**

    - `failedValidation($validator)` - 標準驗證失敗響應
    - `failedValidationWithDetails($validator)` - 包含詳細錯誤結構的驗證失敗響應

2. **授權失敗響應**
    - `failedAuthorizationForAdmin()` - 管理員權限不足響應
    - `failedAuthorizationForInsufficientPrivileges()` - 一般權限不足響應

### 密碼驗證規則說明

支援兩種密碼驗證模式：

1. **強密碼規則** (`$useStrongRules = true`)

    - 最少 8 字符
    - 至少包含一個大寫字母、一個小寫字母和一個數字
    - 使用正則表達式驗證

2. **Laravel 預設規則** (`$useStrongRules = false`)
    - 使用 `Rules\Password::defaults()`
    - 支援密碼確認 (`confirmed` 規則)

## Controller 更新

### AuthController

已更新以下方法使用新的 Request 類：

-   `register()` - 使用 `UserRegistrationRequest`
-   `registerAdmin()` - 使用 `AdminRegisterUserRequest`
-   `login()` - 使用 `UserLoginRequest`
-   `forgotPassword()` - 使用 `ForgotPasswordRequest`
-   `resetPassword()` - 使用 `PasswordResetRequest`
-   `verifyEmail()` - 手動驗證（支持多種調用方式）

### AdminController

已更新以下方法：

-   `updateUser()` - 使用 `UserUpdateRequest`
-   `createUser()` - 使用 `CreateAdminUserRequest`（已存在，已重構）

## 優勢

1. **統一性** - 所有用戶相關的驗證規則都在同一個地方管理
2. **可重用性** - 驗證規則可以在不同場景中重複使用
3. **可維護性** - 規則修改只需要在一個地方進行
4. **可讀性** - 代碼更加清晰，職責分離明確
5. **一致性** - 確保相同的驗證邏輯在不同地方都保持一致
6. **國際化支援** - 支援中英文錯誤訊息
7. **統一 API 響應格式** - 所有驗證失敗和授權失敗都有一致的響應格式

## 使用方式

### 在 Request 類中使用

```php
use App\Http\Requests\Traits\UserValidationRules;
use App\Http\Requests\Traits\ApiResponseFormat;

class YourRequest extends FormRequest
{
    use UserValidationRules, ApiResponseFormat;

    public function rules(): array
    {
        return $this->getUserRegistrationRules();
    }

    public function messages(): array
    {
        return $this->getCommonValidationMessages();
    }

    protected function failedAuthorization()
    {
        $this->failedAuthorizationForAdmin();
    }
}
```

### 在 Controller 中使用

```php
public function store(UserRegistrationRequest $request)
{
    // 驗證已經自動完成
    // 直接使用 $request->validated() 取得驗證過的資料
}
```

## 測試結果

重構後所有相關測試都通過，包括：

-   ✅ 管理員功能測試 (41 個測試全部通過)
-   ✅ Email 驗證測試 (8 個測試全部通過)
-   ✅ 用戶更新測試
-   ✅ 管理員註冊測試

## 向後兼容性

所有現有的 API 端點保持不變，只是內部驗證邏輯更加統一和標準化。響應格式也保持一致，確保前端應用不受影響。
