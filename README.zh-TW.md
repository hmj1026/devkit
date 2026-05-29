# hmj1026/devkit

[English](./README.md) | **繁體中文**

[![Tests](https://github.com/hmj1026/devkit/actions/workflows/tests.yml/badge.svg)](https://github.com/hmj1026/devkit/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/hmj1026/devkit.svg)](https://packagist.org/packages/hmj1026/devkit)
[![PHP Version](https://img.shields.io/packagist/dependency-v/hmj1026/devkit/php.svg)](https://packagist.org/packages/hmj1026/devkit)
[![License](https://img.shields.io/github/license/hmj1026/devkit.svg)](./LICENSE)

一套通用、框架無關的 PHP 工具組，並提供選用的 Laravel 整合。將後端服務常用的建構元件 —— HTTP gateway、Elasticsearch 工具組、SMS 發送、檔案上傳、稽核日誌、Google Chat 錯誤日誌、meta tags、麵包屑 —— 整合成單一 Composer 套件。

已發佈 **v1.0.0**（穩定版）。14 項能力全部實作完成，並在 19 格的 PHP × Laravel CI 矩陣上通過測試與文件化。發佈歷史見 [CHANGELOG](./CHANGELOG.md)，開發流程見 [CONTRIBUTING](./CONTRIBUTING.md)。

## 支援的執行環境

| PHP | Laravel | Monolog | 備註 |
|-----|---------|---------|------|
| 7.3 | 6.x / 7.x / 8.x | 2.9 | 最低相容下限（排除 PHP 7.2 —— `elasticsearch/elasticsearch ^7.17` 需要 PHP 7.3+） |
| 7.4 | 6.x / 7.x / 8.x | 2.9 | 最常見的 legacy 目標 |
| 8.0 | 6.x / 7.x / 8.x / 9.x | 2.9 | |
| 8.1 | 8.x / 9.x / 10.x | 2.9（L8/9）/ 3.x（L10） | |
| 8.2 | 9.x / 10.x / 11.x | 2.9（L9）/ 3.x（L10/11） | L11 另需 `butschster/meta-tags ^3.0` |
| 8.3 | 10.x / 11.x | 3.x | |
| 8.4 | 11.x | 3.x | 最新格；需要 PHPUnit `^11.0` |

不相容的組合（例如 PHP 7.3 + Laravel 9、PHP 8.1+ + Laravel 6/7、PHP 7.2 + 任何版本）會被 Composer 解析器與 [`.github/workflows/tests.yml`](./.github/workflows/tests.yml) 的 CI 矩陣排除。`php: ^7.3 || ^8.0` 約束允許的每一格（共 19 格）都會被實際執行。套件宣告 `monolog/monolog ^2.9 || ^3.0` 與 `butschster/meta-tags ^2.1 || ^3.0`；Laravel 10+ 會強制使用 monolog 3、Laravel 11 會強制使用 meta-tags 3，而 GoogleChat handler 與 Meta wrapper 會在 autoload 時偵測已安裝的主版本。

未來的 v2 會將 PHP 下限提升到 `^8.1`，把 Monolog 收斂到僅 3.x、Flysystem 僅 3，並評估改用 [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) 作為稽核日誌引擎。

## 安裝

```bash
composer require hmj1026/devkit
```

在 Laravel 專案中，根 `Devkit\Laravel\DevkitServiceProvider` 會透過 `extra.laravel.providers` 套件自動探索註冊。各模組的子 provider 預設啟用，可在 `config/devkit.php` 中逐一停用。

## 使用方式

### 框架無關（不需 Laravel）

```php
use Devkit\Http\Client\Gateway;
use GuzzleHttp\Client;

$gateway = new Gateway(new Client(['base_uri' => 'https://api.example.com']));
$response = $gateway->request('GET', '/health');
```

### Laravel

```php
use Devkit\Laravel\Http\Facades\HttpUri;

$assetUrl = HttpUri::url('/images/logo.png');
```

各模組的詳細用法見 [`docs/`](./docs/)，以及 [`openspec/specs/*/spec.md`](./openspec/specs/) 下的能力規格。

## 模組總覽（14 項能力）

框架無關核心（`Devkit\Core\*`、`Devkit\Database\*`、`Devkit\Http\*`、`Devkit\Storage\*`、`Devkit\Search\*`、`Devkit\Messaging\*`、`Devkit\Logging\*`、`Devkit\Ui\*`）：

- `devkit-enum` —— 以反射為基礎的 PHP enum 基底類別，相容 PHP 7.3+ 下限。
- `devkit-http-foundation` —— `AbstractHttpException` 加上 JSON/Web 信封，回傳 PSR-7 `ResponseInterface`。
- `devkit-http-gateway` —— 包裹 Guzzle 的單一類別 Gateway，內建重試/退避與 log observer。
- `devkit-asset-versioning` —— 以 PSR-16 快取的資產 URL 版本化。
- `devkit-file-uploader` —— 架在 Flysystem 1/2/3 之上的 director 模式，含跨版本可見性映射。
- `devkit-elasticsearch` —— ES 7.17 client，提供 Index/Alias 基底與原生陣列 DSL（無 Query Builder）。
- `devkit-sms-dispatch` —— Driver 介面 + Manager + NullDriver + `AbstractHttpSmsDriver`。
- `devkit-googlechat-logger` —— 對應 Google Chat webhook 的雙版本 Monolog 2.9 / 3.x handler（依 Laravel 格次選定版本）。
- `devkit-blade-helpers` —— Trail（麵包屑）加上對 butschster/meta-tags 2.x / 3.x 的雙版本包裝，依權重排序。
- `devkit-eloquent-helpers` —— `HasUuid` / `HasStatus` / `HasAuditLog` traits、Criteria 與 Casts。Laravel 6 使用者在套用 `EncryptedCast` / `HashedCast` 的 model 上須 `use UsesClassCastCompatibility`。
- `devkit-audit-logging` —— 以策略為基礎的實體變更日誌，支援 Eloquent 與 Elasticsearch 目標。
- `devkit-sqs-fifo-queue` —— 僅限 Laravel 的 SQS FIFO 佇列連接器。

Laravel 膠合層（`Devkit\Laravel\*`）：

- `devkit-laravel-integration` —— 根 `DevkitServiceProvider`、5 個選用的 Artisan 產生器（`devkit:make:service`、`:action`、`:enum`、`:audit-log-target`、`:http-client`）、可發佈的 stub 範本，以及 `devkit:install`。

## 本機開發

測試、lint 與靜態分析皆透過 Composer scripts 驅動：

```bash
composer test:core      # phpunit --testsuite=core（純 PHP，不需 Laravel）
composer test:laravel   # phpunit --testsuite=laravel（Orchestra Testbench）
composer test:unit      # 兩個 testsuite 一起
composer lint           # php-cs-fixer --dry-run --diff
composer lint:fix       # php-cs-fixer
composer stan           # phpstan（level 5；需要 PATH 上有 phpstan —— 見下方）
```

靜態分析在 PHP 8.2 上透過 PHPStan PHAR 執行（PHPStan 2.x 無法安裝在 PHP 7.3 格次），因此 `composer stan` 需要可用的 `phpstan` —— 請全域安裝，或直接使用 CI 的 `quality` job。baseline（`phpstan-baseline.neon`）凍結了跨版本的 polyfill 殘留誤判；新程式碼必須分析無誤。

在本機重現單一 CI 格次（事後以 `git checkout -- composer.json && composer install` 清理）：

```bash
composer matrix:list                          # 列出每一個 (php, laravel) 格次
composer matrix:test -- 8.2 11                # 安裝該格次的相依並執行兩個 testsuite
```

完整 CI 矩陣（PHP 7.3 → 8.4 × Laravel 6 → 11，共 19 格）加上 `quality` job（PHPStan + 覆蓋率）在 GitHub Actions 上執行；見 [`.github/workflows/tests.yml`](./.github/workflows/tests.yml)。

## 版本策略

本套件遵循[語意化版本（SemVer）](https://semver.org)。`Devkit\Core\*`、`Devkit\Http\*`、`Devkit\Storage\*`、`Devkit\Search\*`、`Devkit\Messaging\*`、`Devkit\Logging\*`、`Devkit\Ui\*` 下的公開合約，以及文件化的 Laravel facades，在 `1.x` 線內保持穩定。破壞性變更 —— 將 PHP 下限提升至 `^8.1`、移除 Monolog 2 / Flysystem 1 支援、或產生需要更新 PHP 語法的 scaffolding —— 保留到 `2.0`（見 [`docs/v2-roadmap.md`](./docs/v2-roadmap.md)）。發佈歷史：[CHANGELOG](./CHANGELOG.md)。

## 貢獻與安全

- [CONTRIBUTING.md](./CONTRIBUTING.md) —— 分支流程、OpenSpec 工作流程、polyfill 紀律、本機指令。
- [SECURITY.md](./SECURITY.md) —— 支援版本與私下回報漏洞的方式。

## OpenSpec 工作流程

本套件以 [OpenSpec](https://github.com/Fission-AI/OpenSpec) 管理。正規能力規格位於 [`openspec/specs/`](./openspec/specs/)；進行中的 change（每個含 proposal + design + spec delta）位於 [`openspec/changes/`](./openspec/changes/)，完成的 change 則歸檔於 `openspec/changes/archive/`。

## 授權

採用 [MIT License](./LICENSE) 發佈。Copyright (c) 2026 Paul.
