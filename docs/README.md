# 文件目錄

本目錄包含專案的技術文件和相關資源。

## 📁 目錄結構

### 📋 **核心文件**

- [`api-usage.md`](api-usage.md) - API 使用指南
- [`features.md`](features.md) - 功能特色說明
- [`installation.md`](installation.md) - 安裝與啟動指南
- [`laravel-sanctum-guide.md`](laravel-sanctum-guide.md) - Laravel Sanctum 套件指南
- [`login-role-isolation.md`](login-role-isolation.md) - 登入角色隔離機制
- [`role-based-registration.md`](role-based-registration.md) - 角色基礎註冊系統
- [`system-architecture.md`](system-architecture.md) - 系統架構文件
- [`testing.md`](testing.md) - 測試指南
- [`troubleshooting.md`](troubleshooting.md) - 錯誤排除指南

### 📝 **變更記錄** (`changes/`)

存放所有專案變更日誌和重構記錄。

**內容**:

- 重構變更記錄
- 新功能實作記錄
- 修復和改進記錄
- 資料庫變更記錄

**查看方式**: 參考 [`changes/README.md`](changes/README.md) 了解詳細內容。

### 🗄️ **歷史歸檔** (`archive/`)

存放歷史文件和已棄用的實作方案。

**內容**:

- 過時的技術文件
- 已棄用的實作範例
- 歷史開發方案記錄

**查看方式**: 參考 [`archive/README.md`](archive/README.md) 了解詳細內容。

## 📚 快速導航

### 🚀 **新手指南**

1. [**系統架構**](system-architecture.md) - 了解整體架構
2. [**安裝指南**](installation.md) - 快速開始使用
3. [**API 使用**](api-usage.md) - API 端點和範例
4. [**測試指南**](testing.md) - 如何執行測試

### 🔧 **開發相關**

- [**Laravel Sanctum 指南**](laravel-sanctum-guide.md) - 認證系統詳解
- [**功能特色**](features.md) - 主要功能說明
- [**角色註冊系統**](role-based-registration.md) - 角色管理機制
- [**登入角色隔離**](login-role-isolation.md) - 安全機制

### 🔍 **問題排除**

- [**錯誤排除指南**](troubleshooting.md) - 常見問題解決
- [**變更記錄**](changes/) - 了解系統變更歷史

## 🗂️ 文件分類

### 系統文件

- **架構設計**: [`system-architecture.md`](system-architecture.md)
- **安全機制**: [`login-role-isolation.md`](login-role-isolation.md)
- **認證系統**: [`laravel-sanctum-guide.md`](laravel-sanctum-guide.md)

### 使用文件

- **安裝部署**: [`installation.md`](installation.md)
- **API 使用**: [`api-usage.md`](api-usage.md)
- **功能說明**: [`features.md`](features.md)

### 開發文件

- **測試指南**: [`testing.md`](testing.md)
- **變更記錄**: [`changes/`](changes/)
- **問題排除**: [`troubleshooting.md`](troubleshooting.md)

### 歷史記錄

- **變更日誌**: [`changes/`](changes/) - 專案變更歷史
- **歸檔文件**: [`archive/`](archive/) - 歷史文件歸檔

## 📝 文件編寫規範

新增文件時請遵循：

1. **命名規範**: 使用描述性的檔案名稱
2. **結構清晰**: 使用適當的標題和分段
3. **內容完整**: 包含必要的說明和範例
4. **更新及時**: 隨程式碼變更更新相關文件

## 🔄 維護流程

- **定期檢查**: 確保文件與實作同步
- **歸檔管理**: 及時將過時文件移至 `archive/`
- **版本控制**: 重要文件變更記錄在 Git 中
- **清理整理**: 移除不再需要的文件
