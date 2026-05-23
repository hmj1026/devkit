# Devkit Eloquent Helpers

## Use Case

Use the Laravel entity traits and casts for common model behavior: UUID generation, status helpers, audit delegation, encrypted columns, hashed columns, and reusable query criteria.

## Laravel Configuration

```php
'modules' => array(
    'database' => array('enabled' => true),
),
```

Model example:

```php
use Devkit\Laravel\Database\Entity\HasStatus;
use Devkit\Laravel\Database\Entity\HasUuid;

class Order extends Model
{
    use HasUuid;
    use HasStatus;
}
```

## Pure PHP Usage

Concrete traits and casts depend on Eloquent and are Laravel-only. Pure PHP code can still type-hint the core contracts under `Devkit\Database\Contract\Entity`.
