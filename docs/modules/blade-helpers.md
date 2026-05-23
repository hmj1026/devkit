# Devkit Blade Helpers

## Use Case

Use `Trail` for breadcrumb/title composition and `Meta` for weight-ordered scripts, styles, title, and OpenGraph tags.

## Laravel Configuration

```php
'modules' => array(
    'ui' => array('enabled' => true),
),
```

Blade examples:

```blade
{{ trail()->appendItem('Home', '/')->title() }}
@meta_tags('head')
```

Facades are available through package discovery aliases: `Trail` and `MetaTags`.

## Pure PHP Usage

```php
$manager = new TrailManager();
$trail = $manager->register('main');
$trail->appendItem('Home', '/');

$title = $trail->title();
```
