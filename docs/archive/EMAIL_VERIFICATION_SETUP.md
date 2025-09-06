# 郵箱驗證重定向實作說明

## 實作內容

### 1. 後端路由修改

已修改 `routes/api.php` 中的郵箱驗證路由：

```php
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    // 構建前端驗證頁面的 URL，並帶上所有參數
    $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/email-verify';

    $queryParams = http_build_query([
        'id' => $id,
        'hash' => $hash,
        'expires' => $request->query('expires'),
        'signature' => $request->query('signature')
    ]);

    return redirect()->to($frontendUrl . '?' . $queryParams);
})->name('verification.verify');
```

### 2. 環境變數配置

在 `.env` 文件中添加：

```env
FRONTEND_URL=http://localhost:3000
```

### 3. 前端實作選項

#### A. 純 HTML/JavaScript 版本 (`email-verify.html`)

- 適用於簡單的靜態頁面部署
- 包含完整的驗證邏輯和錯誤處理
- 可直接部署在任何靜態文件伺服器

#### B. React 組件版本 (`EmailVerification.jsx`)

- 適用於 React 應用
- 使用 React Router 進行路由管理
- 包含倒數計時和自動跳轉功能

#### C. Vue.js 組件版本 (`EmailVerification.vue`)

- 適用於 Vue.js 應用
- 使用 Vue Router 進行路由管理
- 包含響應式狀態管理

## 工作流程

1. **用戶註冊** → 系統發送驗證郵件
2. **郵件包含連結** → `http://localhost:8000/api/email/verify/{id}/{hash}?expires=...&signature=...`
3. **用戶點擊連結** → 後端路由接收請求
4. **後端重定向** → `http://localhost:3000/email-verify?id=...&hash=...&expires=...&signature=...`
5. **前端頁面載入** → 自動提取 URL 參數
6. **前端調用 API** → `POST /api/v1/auth/verify-email`
7. **顯示結果** → 成功或失敗訊息

## 測試方式

### 使用 Insomnia 測試

1. **註冊用戶**：

```json
POST http://localhost:8000/api/v1/auth/register
{
  "username": "testuser",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

2. **模擬郵箱驗證連結**：

```
GET http://localhost:8000/api/email/verify/1/abc123?expires=1725616800&signature=test_signature
```

3. **直接調用驗證 API**：

```json
POST http://localhost:8000/api/v1/auth/verify-email
{
  "id": 1,
  "hash": "abc123",
  "expires": 1725616800,
  "signature": "test_signature"
}
```

## 部署注意事項

1. **生產環境配置**：

   - 設置正確的 `FRONTEND_URL`
   - 確保前端應用有對應的路由 `/email-verify`

2. **跨域設置**：

   - 確保 CORS 設置允許前端域名

3. **HTTPS 支援**：

   - 生產環境建議使用 HTTPS

4. **前端路由配置**：
   - React: 添加 `/email-verify` 路由到 `App.js`
   - Vue: 添加路由到 `router/index.js`

## 自定義選項

可以根據需求調整：

- 驗證成功後的跳轉頁面
- 錯誤處理邏輯
- UI/UX 設計
- 倒數計時時間
- 客服聯絡方式
