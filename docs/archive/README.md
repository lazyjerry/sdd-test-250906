# Archive 目錄

本目錄包含專案開發過程中產生的歷史文件和已棄用的實作方案。

## 📁 檔案說明

### 郵箱驗證相關

#### `EMAIL_VERIFICATION_SETUP.md`

**建立日期**: 2025-09-06  
**狀態**: 已棄用  
**說明**: 郵箱驗證重定向實作的技術文件，記錄了將 GET 路由重定向到前端頁面的方案。

**棄用原因**:

- 實作方式已改變為直接在後端執行驗證
- 增加了前端複雜性而沒有明顯優勢
- 當前的直接驗證方案更簡單有效

#### `email-verify.html`

**建立日期**: 2025-09-06  
**狀態**: 已棄用  
**說明**: 純 HTML/JavaScript 的郵箱驗證頁面，用於重定向方案。

**內容**: 包含完整的驗證邏輯、錯誤處理和使用者介面。

#### `EmailVerification.jsx`

**建立日期**: 2025-09-06  
**狀態**: 已棄用  
**說明**: React 版本的郵箱驗證組件，適用於 React 應用的重定向方案。

**功能**: 使用 React Router、包含倒數計時和自動跳轉功能。

#### `EmailVerification.vue`

**建立日期**: 2025-09-06  
**狀態**: 已棄用  
**說明**: Vue.js 版本的郵箱驗證組件，適用於 Vue 應用的重定向方案。

**功能**: 使用 Vue Router、響應式狀態管理。

## 🗂️ 目錄結構

```
docs/archive/
├── README.md                        # 本說明文件
├── EMAIL_VERIFICATION_SETUP.md      # 重定向實作技術文件
├── email-verify.html               # 純 HTML 驗證頁面
├── EmailVerification.jsx           # React 驗證組件
└── EmailVerification.vue           # Vue 驗證組件
```

## 📋 當前實作

目前採用的郵箱驗證實作：

- **後端**: `AuthController@verifyEmailByLink` 方法處理 GET 路由
- **API**: `POST /api/v1/auth/verify-email` 提供 RESTful 介面
- **測試**: 完整的自動化測試套件 (`tests/Feature/Auth/EmailVerificationTest.php`)
- **文件**: 測試指南 (`test_scripts/EMAIL_VERIFICATION_TESTING_GUIDE.md`)

## ⚠️ 注意事項

- 本目錄中的檔案僅供參考，不應用於生產環境
- 如需了解當前實作，請參考主要文件和測試指南
- 這些檔案可能包含過時的配置和實作方式

## 🔍 查找歷史

如果需要了解特定功能的開發歷程，可以：

1. 查看 Git 提交歷史
2. 參考本目錄中的相關文件
3. 對比當前實作與歷史方案的差異

## 🧹 維護

定期檢查本目錄，移除過於陳舊或不再有參考價值的檔案。
