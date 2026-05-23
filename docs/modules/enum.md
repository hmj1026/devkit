# Devkit Enum

## Use Case

Use `Devkit\Core\Enum\AbstractEnum` when a project must support PHP 7.3 but still wants enum-like constants with labels, aliases, and value/key helpers.

## Laravel Configuration

No Laravel service provider is required. Define enums under the application namespace:

```php
namespace App\Enums;

use Devkit\Core\Enum\AbstractEnum;

class OrderStatus extends AbstractEnum
{
    const OPEN = 'open';
    const CLOSED = 'closed';

    protected static $contents = array(
        'OPEN' => 'Open',
        'CLOSED' => 'Closed',
    );
}
```

The optional generator can scaffold this class when enabled:

```php
'commands' => array('generators' => array('enabled' => true)),
```

## Pure PHP Usage

```php
$values = OrderStatus::values();
$label = OrderStatus::content(OrderStatus::OPEN);
```
