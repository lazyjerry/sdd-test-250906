# Constitution Update Checklist / 憲法更新檢查清單

修改憲法 (`/memory/constitution.md`) 時，確保所有相依文件都已更新以維持一致性。

## Templates to Update / 需要更新的範本

### When adding/modifying ANY article / 新增/修改任何條文時：

- [ ] `/templates/plan-template.md` - 更新憲法檢查章節
- [ ] `/templates/spec-template.md` - 若需求/範圍受影響則更新
- [ ] `/templates/tasks-template.md` - 若需要新任務類型則更新
- [ ] `/.claude/commands/plan.md` - 若規劃流程變更則更新
- [ ] `/.claude/commands/tasks.md` - 若任務生成受影響則更新
- [ ] `/CLAUDE.md` - 更新執行期開發指導方針

### Laravel-specific templates to update / Laravel 特定範本更新：

- [ ] 更新範本中的 Artisan 命令範例
- [ ] 新增 Laravel service container 綁定需求
- [ ] 包含 Eloquent model 慣例
- [ ] 更新 API 路由結構需求
- [ ] 新增 Laravel 測試指導方針 (PHPUnit/Feature/Unit)

### Article-specific updates / 條文特定更新：

#### Article I (Service-First for Laravel) / 條文 I (Laravel Service 優先原則)：

- [ ] 確保範本強調 Service 類別建立
- [ ] 更新 Artisan 命令範例而非通用 CLI
- [ ] 新增 PHPDoc 文件需求 (中文註解)
- [ ] 包含依賴注入模式

#### Article II (Artisan Command Interface) / 條文 II (Artisan 命令介面)：

- [ ] 更新範本中的 Artisan 命令旗標需求
- [ ] 新增 Laravel 命令 I/O 協議提醒
- [ ] 包含 --json 旗標支援
- [ ] 新增命令簽名和描述需求

#### Article III (Test-First with Laravel) / 條文 III (Laravel 測試優先原則)：

- [ ] 更新測試順序：Feature Tests → Unit Tests → Integration Tests
- [ ] 強調 Laravel TDD 需求 (PHPUnit, RefreshDatabase)
- [ ] 新增 Laravel 特定的測試核准關卡
- [ ] 包含資料庫種子和工廠需求

#### Article IV (Laravel Integration Testing) / 條文 IV (Laravel 整合測試)：

- [ ] 列出 API 端點整合測試觸發器
- [ ] 新增資料庫遷移測試需求
- [ ] 包含 Eloquent 關係測試
- [ ] 新增真實 MySQL 依賴需求 (非 SQLite 模擬)
- [ ] 包含 Mail/Queue/Cache 整合測試

#### Article V (Laravel Observability) / 條文 V (Laravel 可觀測性)：

- [ ] 新增 Laravel Log facade 需求到範本
- [ ] 包含結構化日誌與上下文
- [ ] 更新 API 回應效能監控 (<200ms)
- [ ] 新增資料庫查詢監控需求

#### Article VI (Laravel Versioning) / 條文 VI (Laravel 版本控制)：

- [ ] 新增 API 版本遞增提醒 (/api/v1, /api/v2)
- [ ] 包含 Laravel 遷移程序處理重大變更
- [ ] 更新資料庫 schema 版本控制需求
- [ ] 新增 Composer 套件版本管理

#### Article VII (Laravel Simplicity) / 條文 VII (Laravel 簡潔性)：

- [ ] 更新專案結構限制 (單一 Laravel 應用程式)
- [ ] 新增 Repository 模式禁用範例
- [ ] 包含 Laravel 功能的 YAGNI 提醒
- [ ] 避免不必要抽象的過度工程

## Validation Steps / 驗證步驟

1. **Before committing constitution changes / 提交憲法變更前：**

   - [ ] 所有範本都參考新的 Laravel 需求
   - [ ] 範例已更新以符合 Laravel 最佳實踐
   - [ ] Laravel 慣例與需求間無矛盾
   - [ ] API 路由結構遵循 Laravel 標準
   - [ ] 資料庫設計遵循 Eloquent 慣例

2. **After updating templates / 更新範本後：**

   - [ ] 執行範例 Laravel 實作計劃
   - [ ] 驗證所有憲法需求在 Laravel 環境中都已處理
   - [ ] 檢查範本在 Laravel Sail 環境中運作正常
   - [ ] 驗證 PHPUnit 測試結構和命名慣例
   - [ ] 確保 Artisan 命令遵循 Laravel 模式

3. **Laravel-specific validation / Laravel 特定驗證：**

   - [ ] API 版本控制策略一致 (/api/v1)
   - [ ] Service container 綁定遵循慣例
   - [ ] Eloquent models 遵循命名慣例
   - [ ] 資料庫遷移可逆轉
   - [ ] 中文 PHPDoc 註解格式正確

4. **Version tracking / 版本追蹤：**
   - [ ] 更新憲法版本號
   - [ ] 在範本頁尾註記版本
   - [ ] 新增修正案到憲法歷史
   - [ ] 更新 Laravel 框架版本需求

## Common Misses / 常見遺漏項目

注意 Laravel 專案中經常被遺忘的更新項目：

- 命令文件 (`/commands/*.md`) - 包含 Artisan 命令
- 範本中的檢查清單項目 - Laravel 特定驗證
- 範例程式碼/命令 - 使用 Laravel 語法和慣例
- 領域特定變化 (API vs web vs console 命令)
- Laravel 文件間的交叉參考
- 資料庫種子和工廠需求
- API 速率限制和認證中介軟體
- Eloquent 關係慣例
- Laravel service provider 註冊

## Laravel-Specific Common Misses / Laravel 特定常見遺漏項目

額外經常被遺忘的 Laravel 特定項目：

- Route model binding 慣例
- Form Request 驗證類別需求
- Event 和 Listener 註冊
- Job queue 實作標準
- Cache 標記和失效策略
- Mail 範本和通知標準
- 檔案儲存和資產管理
- 本地化和翻譯標準 (支援中文)

## Template Sync Status / 範本同步狀態

最後同步檢查：2025-09-05

- 憲法版本：1.0.0 (Laravel 12 專用)
- Laravel 框架版本：12.x
- PHP 版本需求：8.2+
- 範本對齊：✅ (初始 Laravel 特定實作)
- 資料庫：MySQL 8.0 with Laravel Sail
- 測試：PHPUnit with Laravel 測試工具

---

_此檢查清單確保憲法原則一致地應用於所有 Laravel 專案文件，並遵循 Laravel 社群標準。_
