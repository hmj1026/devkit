## Why

某既有 PHP/Laravel 專案內部有一組 11 個分散維護的工具套件（涵蓋 framework、repository、HTTP URI、meta tags、file uploader、SMS、SQS FIFO queue、Elasticsearch、Elasticsearch logs、Google Chat log、breadcrumb trail 等領域）。它們已驗證業務價值，但分散在獨立 repo、版本散亂、出現 ~600 行 entity logger 重複實作，且 Google Chat log handler 存在 Monolog 2/3 簽章半遷移 bug。

我們需要把這些零件的「概念」融合成一個**對外可發佈、跨 PHP 7.3 → 8.2、Laravel 6 → 11 廣相容**的開發加速工具箱，做為新專案與外部專案的共同基底。**目標是快速開發、不是企業級照抄**。經過架構審查，本 change 已刪除原架構中過度設計（Repository pattern、ES Query Builder + Grammar、HTTP Gateway 三層抽象、12 個 Artisan generator）並合併過碎的 capability，從 17 個減到 14 個。

## What Changes

### 套件基底
- **新增 mono-package `hmj1026/devkit`**（root namespace `Devkit\`，PSR-4 `src/`），單一 composer 套件 + 子命名空間結構
- **核心類別 framework-agnostic**：僅依賴 PSR 標準（3/7/15/16/17/18）與獨立中立套件（Monolog、Guzzle、Flysystem、elasticsearch-php、butschster/meta-tags、jenssegers/agent）
- **Laravel 黏合層**：歸入 `Devkit\Laravel\*`（**不再用 `Bridge\` 中介名**，Laravel namespace 就是主實作）
- **PHP / Laravel 廣相容**：v1 PHP `^7.3 || ^8.0`、Laravel `^6.0 || ^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0`；v2 預計 bump 至 PHP `^8.1`（解鎖 native enum / Monolog 3 / spatie 系列）

### 架構審查後的剪裁
- **刪除 `devkit-eloquent-repository` capability**：Repository pattern 是 cargo-cult，Laravel 社群已普遍棄用。改用 Eloquent + Service / Action class + Scope 模式
- **刪除 ES Query Builder + Grammar 系統 53 檔**：保留 `Index` 基底 + `ElasticsearchManager`，搜尋直接用 `elasticsearch-php` 原生陣列 DSL
- **扁平化 HTTP Gateway**：刪掉 `AbstractRequest` / `AbstractResponse` 兩層，只留單一 `Gateway` class，subclass 即可整合第三方 API
- **Artisan generator 12 → 5**：保留 `make:service` / `make:action` / `make:enum` / `make:audit-log-target` / `make:http-client`；刪除 Repository / RequestContract / Cache / Format / LogEntity 等 generator
- **Capability 合併**：`http-exception` + `response-envelope` → `http-foundation`；`breadcrumb-trail` + `meta-tags` → `blade-helpers`；`entity-traits` → `eloquent-helpers`（含 Criteria + Casts）
- **Entity traits 從 7+ 精簡為 3**：`HasUuid` / `HasStatus` / `HasAuditLog`（其餘改用 Eloquent 內建或 accessor）

### 既存決策（從原方案延續）
- **SMS 抽象為 driver pattern**：`SmsDriverContract` + `SmsManager`，只提供 `NullSmsDriver` + `AbstractHttpSmsDriver` 抽象基底，concrete provider driver 由消費端自行實作
- **Audit logging 策略型抽象**：`AbstractEntityChangeLogger` + `LogTargetContract` + `EloquentLogTarget` / `ElasticsearchLogTarget`（v2 可改為包 `spatie/laravel-activitylog`）
- **採 Flysystem ^2.0 || ^3.0** 於 file-uploader（取代 Laravel Storage facade 直接依賴）
- **採 elasticsearch-php ^7.17**（避開 8.x 的 Elastic License v2 與 PHP 7.4 floor）
- **採 Monolog ^2.9** 並修正 Google Chat log handler 半遷移 bug
- **SqsFifo 完全 Laravel-only**，放 `Devkit\Laravel\Queue\SqsFifo\`
- **根 `DevkitServiceProvider` 條件式 register 各 module SP**：透過 `config('devkit.modules.<name>.enabled')` 開關
- **5 個 Artisan generator 改為 opt-in**：預設關閉、stub 可 publish 覆寫

## Capabilities

### New Capabilities

1. `devkit-bootstrap`: composer 結構、PSR-4 autoload、CI matrix、PHPUnit 雙 testsuite（core / laravel）
2. `devkit-enum`: 反射型 `AbstractEnum`，純 PHP（v2 改為包 native PHP 8.1 enum + helper trait）
3. `devkit-http-foundation`: `AbstractHttpException` + `JsonEnvelope`（`{code,message,data}`）+ `WebEnvelope`，回 PSR-7
4. `devkit-http-gateway`: 單一 `Gateway` class 包 Guzzle，retry decider + exponential backoff + log observer
5. `devkit-asset-versioning`: `HttpUri` cache-busting URL，PSR-16 cache 注入
6. `devkit-file-uploader`: Director pattern（File / Image），驗證 MIME / size，多 disk，底層 Flysystem 2/3 `FilesystemOperator`
7. `devkit-elasticsearch`: `ElasticsearchManager` + `Index` + `Alias` + `AwsSignedHandler`（**無 Query Builder / Grammar**，查詢用原生 ES DSL）
8. `devkit-sms-dispatch`: `SmsManager` + `SmsDriverContract` + `NullSmsDriver` + `AbstractHttpSmsDriver` 抽象基底
9. `devkit-sqs-fifo-queue`: Laravel SQS FIFO queue driver，4 個 Deduplicator (Unique/Content/Sqs/Callback)、`SqsFifoQueueable` trait
10. `devkit-googlechat-logger`: Monolog ^2.9 `AbstractProcessingHandler`，color-coded severity + per-level mention map
11. `devkit-blade-helpers`: 麵包屑 `Trail` + Meta tag 管理（含 weight-based 排序的 Title / Script / Style）
12. `devkit-eloquent-helpers`: 3 個高價值 trait（`HasUuid` / `HasStatus` / `HasAuditLog`）+ 選配 `Criteria` 查詢 helper + `EncryptedCast` / `HashedCast`
13. `devkit-audit-logging`: 策略型 `AbstractEntityChangeLogger` + `LogTargetContract` + `EloquentLogTarget` / `ElasticsearchLogTarget`
14. `devkit-laravel-integration`: 根 ServiceProvider、Facade 索引、**5 個** Artisan generator（opt-in）、`devkit:install` 命令

### Modified Capabilities

無。所有 capability 皆為新增。

## Impact

- **新建 packagist 套件 `hmj1026/devkit`**，採 MIT License，git repo 在 `/Users/paul/Project/devkit/`
- **14 個新 OpenSpec capability spec** 寫入 `openspec/specs/`（透過本 change archive；較原方案少 3 個）
- **第三方依賴**：
  - 核心：`monolog/monolog ^2.9`、`guzzlehttp/guzzle ^7.0`、`league/flysystem ^1.1 || ^2.0 || ^3.0`（v1/v2/v3 三代並存以涵蓋 Laravel 6→11 矩陣）、`elasticsearch/elasticsearch ^7.17`、`butschster/meta-tags ^2.1`、`jenssegers/agent ^2.0`、PSR-3/7/16/17/18
  - Laravel adapter 額外：`illuminate/support ^6.0 → ^11.0`、`illuminate/database`、`illuminate/queue`、`illuminate/notifications`、`aws/aws-sdk-php ^3.0`、`ramsey/uuid ^4.0`
  - require-dev：`orchestra/testbench`、`phpunit/phpunit`、`mockery/mockery`、`league/flysystem-memory`
- **CI**：GitHub Actions matrix（PHP 7.3 / 7.4 / 8.0 / 8.1 / 8.2 × Laravel 6 → 11，依相容組合過濾）
- **既有專案遷移路徑**：後續單獨 change `add-legacy-shim` 提供 `hmj1026/legacy-shim` 子套件（通用 class_alias 載入器），讓既有專案可依自家命名空間設定漸進切換
- **不影響任何既有專案程式碼**（直到該專案端決定引入 shim 才啟動）
