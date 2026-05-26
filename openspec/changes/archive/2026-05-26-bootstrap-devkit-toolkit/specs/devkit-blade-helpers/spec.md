## ADDED Requirements

### Requirement: Breadcrumb Trail Manager
`Devkit\Ui\Trail\Trail` SHALL allow appending and prepending `TrailTag` items (each holding `text` and `href`), and `Devkit\Ui\Trail\TrailManager::register(string $namespace = 'default')` SHALL return a singleton `Trail` per namespace.

#### Scenario: Append items and read order
- **WHEN** code calls `$trail->appendItem('Home', '/')->appendItem('Products', '/products')`
- **THEN** `$trail->breadcrumb()->all()` returns two `TrailTag` instances in that order

#### Scenario: Title composition
- **WHEN** the trail has `['Home', 'Products']` and `$trail->setSeparator(' > ')` was called
- **THEN** `$trail->title()` returns `'Home > Products'`

#### Scenario: Same instance per namespace
- **WHEN** code calls `TrailManager::register('main')` twice
- **THEN** both calls return the same `Trail` instance

### Requirement: ArrayAccess on TrailTag
`Devkit\Ui\Trail\TrailTag` SHALL implement `ArrayAccess` so `$tag['text']` and `$tag['href']` work alongside object accessors.

#### Scenario: Array access syntax
- **WHEN** code creates `new TrailTag(['text' => 'Home', 'href' => '/'])` and reads `$tag['text']`
- **THEN** the result equals `'Home'`

### Requirement: Meta Tag Manager with Weight Ordering
`Devkit\Ui\MetaTag\Meta` SHALL wrap `butschster/meta-tags ^2.1 || ^3.0` (the package range required by `composer.json`), adapting at autoload time to whichever major version Composer resolved, and adding `addStyle()`, `addScript()`, and `addTag()` methods that accept an integer `weight` argument and render tags sorted ascending by weight. The weight-sorting behaviour SHALL be identical under both v2 and v3 of the underlying package; subclass adapters or a runtime-detection helper SHALL absorb the v2 → v3 API differences (e.g. class renames, return-type widening).

#### Scenario: Lower weight renders first
- **WHEN** code calls `addScript('analytics', 'https://.../a.js', [], 'head', 100)` and `addScript('polyfill', 'https://.../p.js', [], 'head', 10)`
- **THEN** the rendered head section places `polyfill` before `analytics`

#### Scenario: Equal weight preserves insertion order
- **WHEN** two scripts share weight `50` and are added in order A then B
- **THEN** A renders before B

#### Scenario: Works under butschster/meta-tags v3
- **WHEN** the package is installed on PHP 8.2 + Laravel 11, which forces `butschster/meta-tags ^3.0`
- **THEN** `addScript()` / `addStyle()` / `addTag()` / `appendTitle()` / `makeTitle()` / `getOpenGraphPackage()` all behave identically to the v2 path — weight ordering, title composition, and OG package lazy-creation are observable in the rendered HTML

### Requirement: Title Manipulation
`Meta::appendTitle(?string $text)` SHALL append text to the existing title using a configurable separator; `makeTitle()` SHALL return the composed string. Null appends SHALL be no-ops.

#### Scenario: Append two segments
- **WHEN** code calls `$meta->getTitle()->setSeparator(' - ')` then `appendTitle('Home')` and `appendTitle('Site')`
- **THEN** `$meta->makeTitle()` returns `'Home - Site'`

### Requirement: OpenGraph Lazy Package Access
`Meta::getOpenGraphPackage(string $name)` SHALL return an existing `OpenGraphPackage` or create one if absent.

#### Scenario: Lazy create on first access
- **WHEN** no OG package named `'og:product'` exists and code calls `$meta->getOpenGraphPackage('og:product')`
- **THEN** a new `OpenGraphPackage` is created, registered, and returned

### Requirement: Laravel Adapter — Facades, Helpers, Blade
A Laravel adapter SHALL register the `Trail` and `MetaTags` facades, the `trail()` helper, and `@meta_tags` Blade directive.

#### Scenario: Helper in Blade template
- **WHEN** a Blade view contains `<title>{{ trail()->title() }}</title>` after `trail()->appendItem(...)` calls
- **THEN** the rendered HTML reflects the appended items

#### Scenario: Meta directive renders weight-sorted tags
- **WHEN** a Blade template contains `@meta_tags('head')`
- **THEN** only tags with placement `'head'` are rendered, in ascending weight order

### Requirement: Framework Independence for Trail and Meta Cores
`Trail`, `TrailManager`, `TrailTag`, and core `Meta` SHALL NOT depend on `Illuminate\*`. Adapter glue lives in the Laravel sub-namespace only.

#### Scenario: Pure-PHP usage
- **WHEN** the classes are used in a Slim or CLI script
- **THEN** they operate without booting Laravel
