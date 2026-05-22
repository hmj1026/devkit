## Context

某既有 PHP/Laravel 專案內部有一組 11 個分散維護的內部工具套件（framework 約 113 檔、repository 約 15 檔、elasticsearch 約 53 檔、其餘各 4–10 檔），覆蓋 enum / exception / response / HTTP client / file upload / SMS / ES / SQS FIFO / log / breadcrumb / meta-tag 等開發加速零件。它們各自獨立 repo、版本散亂、有 ~600 行 entity logger 重複（兩份 trait：一份寫 DB log 表、一份寫 ES index），並有一個 Google Chat log handler 同時 `use Monolog\LogRecord;` 又宣告 `protected function write(array $record): void` 的半遷移 bug。

新工具箱 `hmj1026/devkit` 以 OpenSpec spec-driven 流程定義 14 個 capability（**已從原計畫 17 個剪裁**），輸出單一 mono-package 含 framework-agnostic 核心 + Laravel 子命名空間，作為新專案與外部專案的共同基底，並提供 legacy-shim 給既有專案漸進切換。

**架構審查後的核心思路調整**：目標是「快速開發工具箱」而非「企業級照抄」。已刪除原架構中過度設計的 Repository pattern / ES Query Builder / HTTP Gateway 三層抽象 / 12 個 generator，並合併過碎的 capability。

## Goals / Non-Goals

**Goals:**
- 單一 composer 套件 `hmj1026/devkit`，root namespace `Devkit\`
- 廣 PHP / Laravel 相容：PHP `^7.2.5 || ^8.0`、Laravel `^6.0 || ^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0`
- 核心類別不 `use Illuminate\*`，僅依賴 PSR 與中立第三方
- Laravel 黏合層歸入 `Devkit\Laravel\*`（**無 `Bridge\` 中介層**）
- 對外可發佈（MIT），不洩漏業務專屬整合
- 消除來源 codebase 11 套間重複（特別是 ~600 行 entity logger）
- 修正既存 bug（Google Chat handler Monolog 2/3 簽章混雜）
- 提供既有專案漸進切換路徑（legacy-shim 子套件，獨立 change）
- **快速開發優先**：低樣板、低 boilerplate、不強迫 Repository pattern

**Non-Goals:**
- 不支援 PHP < 7.2.5
- 不採 elasticsearch-php 8.x（Elastic License v2 風險 + PHP 7.4 floor）
- 不採 Monolog 3.x（PHP 8.1 floor 衝突）
- 不重寫 SqsFifo 為 PSR queue（無對應規範）
- 不內建任何 concrete SMS provider driver
- 不採 PHP 8.1 native enum（v1 受 7.2 floor 限制；v2 計畫換）
- **不提供 Repository pattern**（消費端用 Eloquent + Service / Action）
- **不提供 ES Query Builder / Grammar 層**（消費端用 ES 原生 DSL）
- **不提供 4 層 HTTP Client 抽象**（單一 Gateway class 即可）
- **不提供 12 個 Artisan generator**（縮為 5 個真正通用的）
- 不在本 change 內處理 legacy-shim（屬獨立 change `add-legacy-shim`）

## Decisions

### D1. Framework-agnostic 邊界
**選擇**：核心子命名空間 `Devkit\Core\* / Database\Contract\* / Http\* / Storage\* / Search\* / Messaging\Sms\* / Logging\* / Ui\*` 一律不 `use Illuminate\*`；Laravel 黏合一律歸入 `Devkit\Laravel\*`（直接放，無 `Bridge\` 子層）。

**理由**：
- 核心採 PSR 介面 → 消費端可選擇 Laravel、Symfony、Slim 或純 PHP
- 「Bridge」一詞暗示「橋接到另一實作」，但 Laravel 黏合就是 Laravel 的主實作，命名應該誠實

**Trade-off**：Laravel adapter 需重新 wire 部分功能，但帶來 in-memory adapter 測試紅利。

### D2. ES 不採 8.x、不寫 Query Builder
**選擇**：`elasticsearch/elasticsearch ^7.17`，capability 只含 `Index` / `Alias` / `ElasticsearchManager`，**不寫 Query Builder / Grammar**。

**理由**：
- 8.x：Elastic License v2 + PHP 7.4 floor + API typed response 大幅改寫
- 原 53 檔的 Query Builder + 7 個 Grammar 是過度設計；消費端最後還是會寫 raw array DSL（複雜 query 從 Builder 漏出來）
- elasticsearch-php 7.x 已提供陣列 DSL，包一層只是增加學習成本
- 「快速開發」目標下，少一層抽象 = 少一份維護

**Trade-off**：複雜查詢消費端要寫 array DSL；換得 -53 檔 + 學習曲線消失。

### D3. Monolog 鎖 ^2.9 + 修正 Google Chat handler bug
**選擇**：`monolog/monolog ^2.9`

**理由**：
- Monolog 3.0+ 需 PHP 8.1+；保留 v1 PHP 7.2 floor 必須鎖 2.x
- 修正原 google-chat-log handler `use Monolog\LogRecord;` + `write(array $record)` 同時存在的 bug

**Trade-off**：失去 Monolog 3 的 LogRecord readonly。v2 (PHP 8.1+) 可升級。

### D4. Flysystem 2/3 並存
**選擇**：`league/flysystem ^2.0 || ^3.0`，FileDirector type-hint `League\Flysystem\FilesystemOperator`（v2/v3 共通介面）

**理由**：
- Flysystem v3 需 PHP 7.4+；v1 PHP 7.2 floor 環境用 v2
- `FilesystemOperator` 介面在 v2 引入，是 v3 也用的契約
- composer 依消費端 PHP 版本 auto resolve

**Trade-off**：visibility 常數 v2 為字串、v3 為列舉，需做雙向映射層。

### D5. Audit logging 策略型抽象，v2 可改包 Spatie
**選擇**：v1 自建 `AbstractEntityChangeLogger` + `LogTargetContract` + `EloquentLogTarget` / `ElasticsearchLogTarget`；v2 (PHP 8.1+) 可改為包 `spatie/laravel-activitylog ^4.0` 作為底層 change-capture engine。

**理由**：
- 原 codebase 兩份 trait（DB / ES）~600 行 ~70% 重複，必須統一
- v1 PHP 7.2 floor 無法用 Spatie 4.x（需 PHP 8.0+）
- 策略型抽象（LogTargetContract）保留替換空間

**Trade-off**：v1 自建多寫程式；v2 升級時介面不變，消費端無感。

### D6. SqsFifo 完全 Laravel-only
**選擇**：整套放 `Devkit\Laravel\Queue\SqsFifo\`，不假裝 framework-agnostic

**理由**：擴展 `Illuminate\Queue\SqsQueue`；PSR 標準無 FIFO 語意

**Trade-off**：非 Laravel 消費端用不到。

### D7. ServiceProvider 結構
**選擇**：根 `DevkitServiceProvider` 條件式 `$this->app->register()` 各 module SP，透過 `config('devkit.modules.<name>.enabled')` 開關（預設 true）

**理由**：
- composer `extra.laravel.providers` 只註冊一個根 SP
- 模組可在 config 關掉，啟動成本只剩 N 次 config 檢查

**Trade-off**：根 SP 需維護 module → SP 映射表。

### D8. Artisan generator 12 → 5 (opt-in)
**選擇**：縮為 `make:service` / `make:action` / `make:enum` / `make:audit-log-target` / `make:http-client` 五個；預設關閉（`config('devkit.commands.generators.enabled') = false`）

**理由**：
- 原 12 個有一半綁定我們已刪除的 Repository pattern
- 強迫消費端用 devkit 的命名與目錄結構反而減少彈性
- 快速開發目標：generator 應該幫忙最常用的場景（單職責 service、單動作 action、enum、audit log target、http client subclass）

**Trade-off**：消費端若需要更多 generator 要自己寫，但 stub 可 publish 覆寫。

### D9. 刪除 Repository pattern
**選擇**：完全不提供 `AbstractRepository` / `EloquentRepository`；只在 `Devkit\Laravel\Database\Criteria` 留一個選配查詢 helper

**理由**：
- Laravel 社群 2020 後已普遍棄用 Repository pattern
- 主流：Eloquent + Service / Action class + Query Scopes
- 對快速開發是阻礙：每個查詢都要包一層 contract + criteria
- 50+ 方法的 `AbstractRepository` 是 Java 企業級遺毒

**Trade-off**：原 codebase 大量使用 Repository pattern，遷移要重寫所有 repository 使用點；換得新專案不會背 Repository 負債。

### D10. SMS 套件不含任何 concrete provider driver
**選擇**：只提供 `SmsDriverContract` + `SmsManager` + `NullSmsDriver` + `AbstractHttpSmsDriver` 抽象基底；concrete driver 一律由消費端自行實作

**理由**：
- 任何特定供應商 driver 的 API endpoint / payload 結構都可能屬於商業機密或第三方智財
- `AbstractHttpSmsDriver` 包 `Devkit\Http\Client\Gateway` 提供 retry / backoff，30 分鐘可寫完自家 driver
- `NullSmsDriver` 提供開發/測試 no-op

**Trade-off**：消費端首次接入需自寫 concrete driver；換得零智財風險。

### D11. HTTP Gateway 扁平化
**選擇**：刪除 `AbstractRequest` / `AbstractResponse` 兩層，只留單一 `Gateway` class

**理由**：
- 原 4 層（Client + AbstractGateway + AbstractRequest + AbstractResponse）對每個 API endpoint 都要 4 個 class
- 快速開發目標：subclass `Gateway` 並覆寫 `baseUri` / `defaultHeaders` 即可整合第三方
- 想要 typed request/response 的消費端自己寫 wrapper class

**Trade-off**：失去契約強制；換得開發速度。

### D12. Cookie/Session 工具留在 Laravel 子命名空間
**選擇**：`AbstractCookie`、`UserClientIdCookie`、`getClientTruthIp()`、`getUserClientIdCookie()` 全部歸入 `Devkit\Laravel\Http\Support\`

**理由**：高度依賴 `Illuminate\Http\Request`；PSR-7 化引入過多 abstraction

**Trade-off**：非 Laravel 消費端無 IP 偵測；可改自行注入 PSR-7 ServerRequest。

### D13. AbstractEnum 不升 native enum（v1）
**選擇**：v1 沿用 reflection-based `AbstractEnum`，v2 (PHP 8.1+) 改為包 native `enum`

**理由**：受 PHP 7.2 floor 限制

**Trade-off**：v1 拿不到 type safety；v2 平滑升級時 contract 不變。

### D14. PHP / Laravel 廣相容
**選擇**：PHP `^7.2.5 || ^8.0`、Laravel `^6.0 || ^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0`

**理由**：
- 用戶希望「v1 PHP 7.2 / v2 8.1」保留選擇性
- Laravel 9+ 需要 PHP 8.0+，所以 PHP 7.2 環境只能配 Laravel 6/7/8
- composer 依消費端環境自動 resolve

**Trade-off**：依賴版本範圍要拉寬，CI matrix 較複雜（exclude 不相容組合）。

## Risks / Trade-offs

- **依賴老化**：堅持 PHP 7.2 floor 等於拒絕 Monolog 3 / ES 8 / spatie/laravel-activitylog 4 / spatie/laravel-package-tools 1 等現代套件。對應策略：v2 明確規畫升至 PHP 8.1+ floor，採時序漸進；介面契約不變。
- **重構 audit logging 風險**：來源 codebase 仰賴既有 trait 行為，重構為策略型抽象需完整迴歸測試。對應策略：在 legacy-shim change 中提供「舊行為 wrapper」。
- **Flysystem 2/3 雙版本支援**：visibility 常數差異需做雙向映射。
- **Laravel 6/7/8 為 EOL**：v1 仍支援這些是為了相容於 PHP 7.2 floor 的消費端；若消費端都在 Laravel 9+，可直接升 v2。
- **SMS driver 整合曲線**：消費端首次接入需自寫 concrete driver。對應策略：詳細「自寫 driver 教學」+ fake provider 範例 + `AbstractHttpSmsDriver` 預先處理 retry / backoff / log observer。
- **Repository pattern 遷移痛**：原 codebase 大量 Repository 呼叫，遷移要改寫；換得新專案不背技術債。
- **ES Query Builder 移除**：來源 codebase 若大量使用既有 ES Builder 鏈式 API，遷移要改寫成 array DSL。對應策略：legacy-shim 可提供 `Builder` shim（包 raw client + 老 API）給漸進期。

## v2 Roadmap (參考)

當 PHP floor 升至 ^8.1：
- AbstractEnum → 改為包 native `enum` + `EnumHelpers` trait
- Monolog ^2.9 → ^3.x（LogRecord readonly）
- Flysystem ^2.0 || ^3.0 → ^3.0 only
- 自建 audit logging → 包 `spatie/laravel-activitylog ^4.0` 為底層引擎
- ServiceProvider boilerplate → 改用 `spatie/laravel-package-tools ^1.0`
- 加入 native enum migration generator
