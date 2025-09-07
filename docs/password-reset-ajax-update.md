# 密碼重設功能更新 - 前端 AJAX 調用

## 🔄 架構變更

### 原始架構

```
前端表單 → 後端控制器 → API 調用 → 重定向響應
```

### 新架構

```
前端表單 → JavaScript AJAX → 後端控制器 → API 調用 → JSON 響應
```

## ✨ 主要改進

### 🚀 **用戶體驗提升**

- ✅ 無頁面刷新的 AJAX 提交
- ✅ 即時錯誤提示與處理
- ✅ 流暢的載入狀態顯示
- ✅ 動態成功訊息展示

### 🔧 **技術改進**

- ✅ 前端主導的 API 調用
- ✅ JSON 格式的響應處理
- ✅ 更好的錯誤回饋機制
- ✅ 響應式的前端互動

## 📝 程式碼變更

### 1. 控制器變更 (`PasswordResetController.php`)

#### 變更前：重定向響應

```php
public function reset(Request $request)
{
    // ... 驗證邏輯 ...

    if ($validator->fails()) {
        return back()->withErrors($validator);
    }

    // ... API 調用 ...

    if ($response->successful()) {
        return redirect()->route('password.reset.success');
    }

    return back()->withErrors(['api' => $error['message']]);
}
```

#### 變更後：JSON 響應

```php
public function reset(Request $request)
{
    // ... 驗證邏輯 ...

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => '表單驗證失敗',
            'errors' => $validator->errors()
        ], 422);
    }

    // ... API 調用 ...

    if ($response->successful()) {
        return response()->json([
            'success' => true,
            'message' => $data['message'] ?? '密碼重設成功！',
            'redirect_url' => route('password.reset.success')
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => $error['message'] ?? '密碼重設失敗，請稍後再試'
    ], $response->status());
}
```

### 2. 前端變更 (`password-reset.blade.php`)

#### 移除 Blade 錯誤處理

```php
<!-- 變更前 -->
@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </div>
@endif

<!-- 變更後 -->
<div id="alertContainer"></div> <!-- 動態顯示錯誤 -->
```

#### 表單方法更新

```html
<!-- 變更前 -->
<form
	method="POST"
	action="{{ route('password.reset.submit') }}"
	id="resetForm"
>
	<!-- 變更後 -->
	<form id="resetForm"><!-- 移除 method 和 action，使用 AJAX --></form>
</form>
```

#### JavaScript AJAX 實作

```javascript
// 新增：AJAX 表單提交
document.getElementById("resetForm").addEventListener("submit", function (e) {
	e.preventDefault(); // 阻止默認提交

	// 收集表單資料
	const formData = new FormData(this);
	const data = {
		token: formData.get("token"),
		email: formData.get("email"),
		password: formData.get("password"),
		password_confirmation: formData.get("password_confirmation"),
		_token: formData.get("_token"),
	};

	// 發送 AJAX 請求
	fetch('{{ route("password.reset.submit") }}', {
		method: "POST",
		headers: {
			"Content-Type": "application/json",
			Accept: "application/json",
			"X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
		},
		body: JSON.stringify(data),
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				showAlert("success", data.message);
				setTimeout(() => {
					window.location.href = data.redirect_url;
				}, 1500);
			} else {
				showAlert("danger", data.message);
				if (data.errors) {
					showFieldErrors(data.errors);
				}
			}
		})
		.catch((error) => {
			showAlert("danger", "系統錯誤，請稍後再試");
		});
});
```

## 🎯 功能特色

### 💡 **動態錯誤處理**

```javascript
// 顯示全局錯誤訊息
function showAlert(type, message) {
	const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show">
            <i class="bi bi-${type === "success" ? "check-circle" : "exclamation-triangle"}"></i>
            <strong>${type === "success" ? "成功！" : "錯誤："}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
	document.getElementById("alertContainer").innerHTML = alertHtml;
}

// 顯示欄位特定錯誤
function showFieldErrors(errors) {
	Object.keys(errors).forEach((field) => {
		const input = document.querySelector(`[name="${field}"]`);
		if (input) {
			input.classList.add("is-invalid");
			const errorDiv = document.createElement("div");
			errorDiv.className = "invalid-feedback";
			errorDiv.textContent = errors[field][0];
			input.parentNode.appendChild(errorDiv);
		}
	});
}
```

### 🔄 **狀態管理**

```javascript
// 提交狀態管理
function showLoadingState() {
	submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>處理中...';
	submitBtn.disabled = true;
}

function resetSubmitButton() {
	submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> 重設密碼';
	submitBtn.disabled = false;
}

// 清除錯誤狀態
function clearAlerts() {
	document.getElementById("alertContainer").innerHTML = "";
	document.querySelectorAll(".is-invalid").forEach((input) => {
		input.classList.remove("is-invalid");
	});
	document.querySelectorAll(".invalid-feedback").forEach((error) => {
		error.remove();
	});
}
```

## 🧪 測試方式

### 1. 正常流程測試

1. 訪問 `http://localhost/password/reset/sample_token_123456?email=test@example.com`
2. 填寫新密碼表單
3. 提交後觀察 AJAX 響應
4. 成功後自動跳轉到成功頁面

### 2. 錯誤處理測試

```javascript
// 測試密碼驗證錯誤
{
    "password": "123", // 太短
    "password_confirmation": "456" // 不匹配
}

// 預期響應
{
    "success": false,
    "message": "表單驗證失敗",
    "errors": {
        "password": ["密碼至少需要8個字符"],
        "password_confirmation": ["密碼確認不匹配"]
    }
}
```

### 3. 網路錯誤測試

- 斷開網路連線
- 觀察錯誤處理機制
- 確認用戶友善的錯誤訊息

## 📊 響應格式

### 成功響應

```json
{
	"success": true,
	"message": "密碼重設成功！",
	"redirect_url": "/password/reset-success"
}
```

### 驗證錯誤響應

```json
{
	"success": false,
	"message": "表單驗證失敗",
	"errors": {
		"password": ["密碼必須包含至少一個大寫字母、一個小寫字母、一個數字和一個特殊字符"],
		"password_confirmation": ["密碼確認不匹配"]
	}
}
```

### API 錯誤響應

```json
{
	"success": false,
	"message": "密碼重設失敗，請稍後再試",
	"errors": []
}
```

### 系統錯誤響應

```json
{
	"success": false,
	"message": "系統錯誤，請稍後再試",
	"error": "具體錯誤訊息"
}
```

## 🎨 用戶體驗流程

### 1. 表單載入

- ✅ 顯示密碼重設表單
- ✅ 預填 email 欄位（只讀）
- ✅ 即時密碼強度檢查

### 2. 表單填寫

- ✅ 密碼強度即時提示
- ✅ 密碼匹配即時驗證
- ✅ 密碼顯示/隱藏切換

### 3. 表單提交

- ✅ 顯示載入狀態
- ✅ 阻止重複提交
- ✅ AJAX 非同步處理

### 4. 結果處理

- ✅ 成功：顯示成功訊息 + 1.5 秒後跳轉
- ✅ 失敗：顯示錯誤訊息 + 重置表單狀態
- ✅ 網路錯誤：顯示系統錯誤訊息

## 🔗 相關連結

- **測試頁面**: http://localhost/password-reset-test
- **重設表單**: http://localhost/password/reset/sample_token_123456?email=test@example.com
- **成功頁面**: http://localhost/password/reset-success
- **API 文檔**: http://localhost/swagger-ui/

## ✅ 改進完成

🎉 **密碼重設功能已成功轉換為前端主導的 AJAX 調用方式！**

**主要優勢：**

- 🚀 更流暢的用戶體驗
- 🔧 更好的錯誤處理
- 💡 即時的狀態回饋
- 🎨 現代化的互動方式
