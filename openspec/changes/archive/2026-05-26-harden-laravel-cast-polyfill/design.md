# Design

## Goal

After this change, the Laravel 6 polyfill path (trait-driven) and the Laravel 7+ native path (`HasAttributes::setClassCastableAttribute` / `addCastAttributesToArray` / `originalIsEquivalent` / `$classCastCache`) SHALL be observably indistinguishable for the four behaviours that consumer code touches: property access, serialization, dirty tracking, and read-time cost.

## Native vs polyfill detection

Current probe:

```php
private function hasNativeClassCasts(): bool
{
    return method_exists(get_parent_class($this), 'isClassCastable');
}
```

Failure mode: `isClassCastable` is a protected helper on `Illuminate\Database\Eloquent\Concerns\HasAttributes`. If a future Laravel major renames it, splits it across enum/object casts, or factors class-cast resolution into a different helper, this returns `false` even though native machinery is fully wired. The trait then double-runs casts on top of native ones.

Replacement:

```php
private function hasNativeClassCasts(): bool
{
    return interface_exists(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class, false)
        && ! defined('DEVKIT_CASTS_ATTRIBUTES_POLYFILLED');
}
```

`polyfills.php`:

```php
namespace Illuminate\Contracts\Database\Eloquent {
    if (! interface_exists(CastsAttributes::class, true)) {
        interface CastsAttributes
        {
            public function get($model, string $key, $value, array $attributes);
            public function set($model, string $key, $value, array $attributes);
        }
        if (! defined('DEVKIT_CASTS_ATTRIBUTES_POLYFILLED')) {
            define('DEVKIT_CASTS_ATTRIBUTES_POLYFILLED', true);
        }
    }
}
```

The marker is set iff *we* declared the interface. Detection becomes: "interface loaded AND not by us → native".

## attributesToArray

Laravel 6's flow (in `HasAttributes::attributesToArray`):
1. `getArrayableAttributes()`
2. `addDateAttributesToArray`
3. `addMutatedAttributesToArray`
4. `addCastAttributesToArray` — for class-name cast strings, falls through `getCastType`'s `switch` `default` and returns the raw value unchanged.
5. Visibility filtering

We let parent run unchanged, then post-process devkit cast keys only:

```php
public function attributesToArray()
{
    $attributes = parent::attributesToArray();
    if ($this->hasNativeClassCasts()) {
        return $attributes;
    }
    foreach ($this->getCasts() as $key => $_) {
        if (! array_key_exists($key, $attributes)) continue;
        if ($this->hasGetMutator($key)) continue;
        if (! $this->isDevkitClassCastable($key)) continue;
        $attributes[$key] = $this->serializeDevkitCastValue(
            $this->getDevkitClassCastableValue($key)
        );
    }
    return $attributes;
}

private function serializeDevkitCastValue($value)
{
    if ($value instanceof \JsonSerializable) return $value->jsonSerialize();
    if ($value instanceof \Illuminate\Contracts\Support\Arrayable) return $value->toArray();
    return $value;
}
```

`toJson()` is `json_encode($this->jsonSerialize())` → `attributesToArray() + relationsToArray()`, so fixing one fixes both.

## Dirty equivalence via getDirty()

Why not `originalIsEquivalent`: signature changed between Laravel 6 (`protected function originalIsEquivalent($key, $current)`, 2 args) and Laravel 7+ (`public function originalIsEquivalent($key)`, 1 arg). Overriding both shapes in a trait gets ugly and the protected vs public access modifier collision is fatal on some PHP/Laravel combos.

`getDirty()` has a stable 0-arg public signature across all Laravel majors:

```php
public function getDirty()
{
    $dirty = parent::getDirty();
    if ($this->hasNativeClassCasts()) {
        return $dirty;
    }
    foreach ($dirty as $key => $current) {
        if (! $this->isDevkitClassCastable($key)) continue;
        $original = array_key_exists($key, $this->original) ? $this->original[$key] : null;
        if ($current === null || $original === null) continue;
        $caster = $this->resolveDevkitCaster($key);
        if ($caster->get($this, $key, $current, $this->attributes)
            === $caster->get($this, $key, $original, $this->attributes)) {
            unset($dirty[$key]);
        }
    }
    return $dirty;
}
```

`isDirty($key)` in `HasAttributes` is implemented atop `getDirty()`, so one override fixes both. `HashedCast::get` returns the raw stored hash, so re-assigning the same plaintext (which Hash::needsRehash() detects and skips) compares equal-hash-to-equal-hash and stays clean.

Edge case — re-hashing path: if the user assigns the original plaintext to a column that already holds a valid bcrypt hash, `HashedCast::set` calls `Hash::needsRehash($value)` on the plaintext (returns true → rehash). The new hash differs from the stored one, so `getDirty()` sees a difference; comparing `caster->get()` of two hashes returns each hash unchanged, so they remain unequal → still dirty. That is the correct behaviour — the user passed plaintext, the column will be rehashed. No change needed here.

## Per-instance cast cache

```php
private $devkitClassCastCache = array();

private function getDevkitClassCastableValue(string $key)
{
    if (array_key_exists($key, $this->devkitClassCastCache)) {
        return $this->devkitClassCastCache[$key];
    }
    return $this->devkitClassCastCache[$key] = $this->resolveDevkitCaster($key)->get(
        $this, $key, $this->getAttributeFromArray($key), $this->attributes
    );
}
```

`getAttributeValue` and `attributesToArray` both route through this helper.

Invalidation:

```php
public function setAttribute($key, $value)
{
    if (! $this->hasNativeClassCasts() && $this->isDevkitClassCastable($key) && ! $this->hasSetMutator($key)) {
        unset($this->devkitClassCastCache[$key]);
        foreach ($this->normalizeDevkitCastResponse($key, $this->resolveDevkitCaster($key)->set($this, $key, $value, $this->attributes)) as $attribute => $attributeValue) {
            $this->attributes[$attribute] = $attributeValue;
            unset($this->devkitClassCastCache[$attribute]); // multi-key responses
        }
        return $this;
    }
    return parent::setAttribute($key, $value);
}

public function setRawAttributes(array $attributes, $sync = false)
{
    $this->devkitClassCastCache = array();
    return parent::setRawAttributes($attributes, $sync);
}
```

`setRawAttributes` is called by `newFromBuilder` (hydration), `refresh`, `fresh`, and `replicate` paths — covering every flow where raw bytes land in `$this->attributes`.

## Reverting set() to scalar

`EncryptedCast::set`:
```php
public function set($model, string $key, $value, array $attributes)
{
    if ($value === null || $value === '') return $value;
    return Crypt::encryptString((string) $value);
}
```

`HashedCast::set`:
```php
public function set($model, string $key, $value, array $attributes)
{
    if ($value === null || $value === '') return $value;
    $value = (string) $value;
    return Hash::needsRehash($value) ? Hash::make($value) : $value;
}
```

The trait's `normalizeDevkitCastResponse(string $key, $value): array` already wraps scalars into `[$key => $value]`. Laravel 7+ native flow does the same via `Model::normalizeCastClassResponse`. Both paths handle scalar return identically.

## Trade-offs

- **`attributesToArray` walks casts twice** (once in parent's `addCastAttributesToArray` no-op default branch, once in our post-process). One extra hash lookup per cast key per serialization call; negligible.
- **`getDirty` decodes both `current` and `original`** for every dirty class-cast key. Worst case for `EncryptedCast`: 2 × `Crypt::decryptString` per save per encrypted column. Cache helps reads but writes-then-save touches both sides. Acceptable: the native path pays the same cost in `originalIsEquivalent` → `castAttribute`.
- **Cache TTL is the model instance lifetime.** Same as native `$classCastCache`. No need for explicit eviction beyond setAttribute/setRawAttributes.

## Verification plan

Per task in `tasks.md` §4. Key gates:

1. New test class `tests/Laravel/Database/CastCompatibilityTest.php` covering all four behaviours; each test MUST fail against the current `develop` HEAD and pass after this change (RED→GREEN evidence).
2. `composer test` (both `core` and `laravel` suites) green.
3. Manual matrix sanity: Laravel 6 + Testbench 4 (trait active) AND Laravel 11 + Testbench 9 (trait short-circuits).
4. `openspec validate harden-laravel-cast-polyfill --strict` passes.
