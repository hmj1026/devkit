## 1. Wave 0 — Bootstrap

- [x] 1.1 在 `/Users/paul/Project/devkit/` 建立 `composer.json`:name `hmj1026/devkit`、PSR-4 `Devkit\` → `src/`、PHP `^7.3 || ^8.0`、require psr/log + psr/simple-cache + psr/http-message + psr/http-client + psr/http-factory + monolog/monolog ^2.9 + guzzlehttp/guzzle ^7.0 + league/flysystem `^1.1 || ^2.0 || ^3.0` (含 v1 以涵蓋 Laravel 6/7/8) + elasticsearch/elasticsearch ^7.17 + butschster/meta-tags ^2.1 + jenssegers/agent ^2.0
- [x] 1.2 補 require-dev：orchestra/testbench、phpunit/phpunit、mockery/mockery、league/flysystem-memory、laravel/framework（覆蓋 6 → 11 矩陣）
- [x] 1.3 撰寫 `phpunit.xml`：兩個 testsuite `core`（純 PHP）與 `laravel`（Orchestra Testbench）
- [x] 1.4 撰寫 `.github/workflows/tests.yml`：matrix PHP 7.3/7.4/8.0/8.1/8.2 × Laravel 6/7/8/9/10/11，過濾不相容組合（PHP 7.2 已排除：elasticsearch/elasticsearch ^7.17 要求 PHP 7.3+）；audit block 關閉；monolog 與 butschster/meta-tags per-cell pin
- [x] 1.5 撰寫 README skeleton（含模組地圖、安裝步驟、Laravel 與非 Laravel 用法、v2 roadmap 段落）+ LICENSE (MIT)
- [x] 1.6 撰寫 `.gitignore`（vendor、composer.lock 對 library 該 ignore、phpunit cache、IDE 設定）
- [x] 1.7 撰寫 `src/` 與 `tests/` 目錄結構 placeholder（`.gitkeep`）
- [x] 1.8 補 openspec `config.yaml` 的 `context:` 區塊

## 2. Wave 1 — Pure leaves

- [x] 2.1 Port `Devkit\Core\Enum\AbstractEnum`：反射 + memoised constants + `toArray() / values() / keys() / mapping() / getByAlias()`
- [x] 2.2 撰寫 `AbstractEnumTest`（純 PHP）
- [x] 2.3 Port `Devkit\Core\Exception\AbstractHttpException` + `ReportExceptionContract`（implements Symfony `HttpExceptionInterface`）
- [x] 2.4 撰寫 `AbstractHttpExceptionTest`
- [x] 2.5 Port `Devkit\Core\Support\helpers.php`：純 PHP helpers (`isJson` 等)
- [x] 2.6 Port `Devkit\Ui\Trail\Trail` + `TrailManager` + `TrailTag`（去除 Laravel facade 依賴）
- [x] 2.7 撰寫 `TrailTest`（純 PHP）
- [x] 2.8 Port `Devkit\Ui\MetaTag\Meta` 等類別（包 butschster/meta-tags v2/v3，weight 排序）
- [x] 2.9 撰寫 `MetaTest`（純 PHP）

## 3. Wave 2 — Contracts

- [x] 3.1 `Devkit\Database\Contract\Entity\HasUuidContract` + `HasStatusContract` + `HasAuditLogContract`（純 PHP）
- [x] 3.2 `Devkit\Storage\Contract\FileContract` + `DirectorContract`
- [x] 3.3 `Devkit\Messaging\Sms\Contract\SmsDriverContract` + `SmsMessageContract` + `SmsResultContract`
- [x] 3.4 `Devkit\Search\Contract\ConnectionContract` + `IndexContract`
- [x] 3.5 `Devkit\Logging\Contract\LogTargetContract`
- [x] 3.6 `Devkit\Http\Client\Contract\LogObserverContract`
- [x] 3.7 為每個 contract 寫 `interface_exists` smoke test

## 4. Wave 3 — Core 實作

- [x] 4.1 Port `Devkit\Http\Client\Gateway`：**單一 class**，包 Guzzle，retry decider + exponential backoff + log observer
- [x] 4.2 撰寫 `GatewayTest`：用 Guzzle MockHandler 驗證 retry/backoff
- [x] 4.3 Port `Devkit\Http\Asset\HttpUri`：type-hint `Psr\SimpleCache\CacheInterface`
- [x] 4.4 撰寫 `HttpUriTest`：用 in-memory PSR-16
- [x] 4.5 Port `Devkit\Core\Response\JsonEnvelope` + `WebEnvelope`：返回 PSR-7 `ResponseInterface`
- [x] 4.6 撰寫 envelope 測試
- [x] 4.7 Port `Devkit\Logging\GoogleChat\GoogleChatLogHandler`：dual Monolog 2/3 dispatcher stub + Internal/M2 + Internal/M3 + 共享 trait，color-coded severity、per-level mention（升級規格至 dual-version 支援）
- [x] 4.8 Port `Devkit\Logging\GoogleChat\GoogleChatLogLineFormatter` + `GoogleChatLogHandlerFactory`
- [x] 4.9 撰寫 `GoogleChatLogHandlerTest`：RecordingHttpClient (PSR-18) 驗證 webhook body shape、color、mention

## 5. Wave 4 — Domain 模組

- [x] 5.1 Port `Devkit\Storage\Foundation\File` + `Image`
- [x] 5.2 Port `Devkit\Storage\Enum\*`：DriverEnum、DiskEnum、PathMethodEnum、VisibilityEnum（含 Flysystem 2/3 visibility 雙向映射）
- [x] 5.3 Port `Devkit\Storage\Uploader\AbstractDirector` + `FileDirector` + `ImageDirector`：type-hint `League\Flysystem\FilesystemOperator`
- [x] 5.4 撰寫 `AbstractDirectorTest`：用 `InMemoryFilesystemAdapter`
- [x] 5.5 Port `Devkit\Messaging\Sms\SmsManager`：driver registry，不依賴 `Illuminate\Foundation\Application`
- [x] 5.6 Port `Devkit\Messaging\Sms\Driver\NullSmsDriver`
- [x] 5.7 撰寫 `Devkit\Messaging\Sms\Driver\AbstractHttpSmsDriver` 抽象基底：繼承 `Devkit\Http\Client\Gateway`，留 `endpointFor()` / `payloadFor()` / `parseResponse()` 三個抽象方法
- [x] 5.8 撰寫 SmsManager 測試 + AbstractHttpSmsDriver 子類化測試（用測試專用 fake driver）
- [x] 5.9 Port `Devkit\Search\Client\ElasticsearchManager`
- [x] 5.10 Port `Devkit\Search\Index\Index` + `Alias` 基底（Eloquent-like）
- [x] 5.11 Port `Devkit\Search\Foundation\AwsSignedHandler`（選配）
- [x] 5.12 撰寫 Search 模組測試（mock Guzzle，斷言 ES request payload）

## 6. Wave 5 — Laravel 子命名空間（無 Bridge 前綴）

- [x] 6.1 Port `Devkit\Laravel\Database\Entity\HasUuid`（trait） + 對應的 contract 實作
- [x] 6.2 Port `Devkit\Laravel\Database\Entity\HasStatus`（trait） + 對應的 contract 實作
- [x] 6.3 Port `Devkit\Laravel\Database\Entity\HasAuditLog`（trait） + 連動 `devkit-audit-logging`
- [x] 6.4 撰寫 Entity traits 測試（Orchestra Testbench + SQLite in-memory）
- [x] 6.5 撰寫 `Devkit\Laravel\Database\Criteria`（**選配查詢 helper**，非 Repository）
- [x] 6.6 撰寫 `Devkit\Laravel\Database\Cast\EncryptedCast` + `HashedCast`（implements `CastsAttributes`）
- [x] 6.7 撰寫 Casts 測試
- [x] 6.8 Port `Devkit\Laravel\Storage\StorageAdapter`：給 disk name 回 `FilesystemOperator`
- [x] 6.9 Port `Devkit\Laravel\Messaging\Sms\SmsChannel`（Notification channel） + `SendSmsJob`（queueable）
- [x] 6.10 撰寫 SmsChannel + SendSmsJob 測試
- [x] 6.11 Port `Devkit\Laravel\Http\Asset\HttpUriCacheAdapter`：wire `Illuminate\Cache\Repository`（PSR-16）到核心 HttpUri
- [x] 6.12 Port `Devkit\Laravel\Http\Support\*`：`getClientTruthIp()`、`getUserClientIdCookie()`、`AbstractCookie`、`UserClientIdCookie`、`AccessLogMiddleware`、`AccessLogJob`、`ResponseLogJob`
- [x] 6.13 Port `Devkit\Laravel\Logging\GoogleChat\GoogleChatLogServiceProvider`（`Log::extend('googlechat', ...)`）
- [x] 6.14 Port `Devkit\Laravel\Ui\Trail\TrailServiceProvider` + Facade + `trail()` helper
- [x] 6.15 Port `Devkit\Laravel\Ui\MetaTag\MetaTagServiceProvider` + Facade + `@meta_tags` Blade directive
- [x] 6.16 新增 `Devkit\Laravel\Audit\AbstractEntityChangeLogger` trait + `EloquentLogTarget` + `ElasticsearchLogTarget`（含 jenssegers/agent 解析的 login event 支援）
- [x] 6.17 撰寫 audit logging 測試（mock model event，斷言 log target payload）
- [x] 6.18 Port `Devkit\Laravel\Queue\SqsFifo\SqsFifoQueue` + `SqsFifoConnector` + 4 個 Deduplicator + `SqsFifoQueueable` trait + ServiceProvider
- [x] 6.19 連同來源 sqs-fifo-queue 套件的 tests/* 一起 port 並 namespace 替換
- [x] 6.20 Port `Devkit\Laravel\Validation\Rules\TaiwanCellPhone` + `WithoutSpecialCharacters`，純 regex 邏輯抽到 `Devkit\Core\Validation\` 純 PHP 函式

## 7. Wave 6 — DX / ServiceProvider 索引 / Generator

- [x] 7.1 撰寫 `Devkit\Laravel\DevkitServiceProvider`：條件式 register 各 module SP（透過 `config('devkit.modules.<name>.enabled')`）
- [x] 7.2 撰寫 `config/devkit.php`：modules、disks、SMS drivers、HTTP gateway retry、ES connections、GoogleChat webhook、commands 開關
- [x] 7.3 撰寫 **5 個** Artisan generator：
    - `devkit:make:service`
    - `devkit:make:action`
    - `devkit:make:enum`
    - `devkit:make:audit-log-target`
    - `devkit:make:http-client`
- [x] 7.4 撰寫 `Devkit\Laravel\Command\InstallCommand` (`devkit:install`)：publish config + stub
- [x] 7.5 撰寫每模組 docs/ 一份 md：使用情境、Laravel 設定範例、純 PHP 用法
- [x] 7.6 撰寫頂層 docs/architecture.md：layer 圖、core 與 Laravel 子命名空間邊界、module 依賴圖
- [x] 7.7 撰寫 docs/v2-roadmap.md：列出 v2 (PHP 8.1+) 預計引入的現代套件（Spatie 系列、Monolog 3、Flysystem 3 only 等）

## 8. Wave 7 — 收尾與驗收

- [x] 8.1 跑 PHPUnit core + laravel testsuite，全綠
- [x] 8.2 跑 GitHub Actions CI matrix，全綠
- [x] 8.3 跑 Laravel Pint（或 PHP-CS-Fixer），全綠
- [x] 8.4 `openspec validate bootstrap-devkit-toolkit` 通過
- [x] 8.5 `openspec show bootstrap-devkit-toolkit` 顯示完整 artifact
- [x] 8.6 從來源套件對照表逐項 checklist（11 套 → 14 capability → 已實作模組）
- [x] 8.7 與用戶 review、依回饋微調
- [x] 8.8 在既有專案做一次 dry-run import：composer require hmj1026/devkit + 改一個 class 引用為 Devkit\*，確認介面相容
- [x] 8.9 標記 `bootstrap-devkit-toolkit` 為 ready-to-archive
- [x] 8.10 後續開新 change `add-legacy-shim`：提供 `hmj1026/legacy-shim` 子套件 + 通用 `ClassAliasLoader`，讓既有專案漸進切換
