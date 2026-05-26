## Why

Commits `7fb92a8 fix: support laravel 6 cast writes` and `59f0705 fix: polyfill laravel 6 class casts` introduced `UsesClassCastCompatibility` trait + a minimal `Illuminate\Contracts\Database\Eloquent\CastsAttributes` polyfill so that `EncryptedCast` / `HashedCast` work on Laravel 6 (where native class-cast machinery does not exist). A high-effort `/code-review high` pass over that diff surfaced seven findings; two are real functional bugs invisible to the existing test suite, and three more are observable semantic divergence vs the Laravel 7+ behaviour the polyfill claims to emulate.

**P0 functional bug** — `$model->toArray()` / `$model->toJson()` return the encrypted ciphertext (or hashed blob) on the Laravel 6 path. The trait intercepts `getAttributeValue` and `setAttribute` but not `attributesToArray`; Laravel 6's `HasAttributes::castAttribute` falls through `default: return $value` for class-cast names, so any API resource, event payload, or `json_encode($model)` leaks raw stored bytes instead of the plaintext. Current `CastsTest` only asserts `->ssn` property access and `->fresh()`, so the gap is uncovered.

**P1 behavioural bug** — `$record->ssn = $sameValue` always flips `isDirty('ssn')` to true. `Crypt::encryptString` uses a fresh IV per call, so the stored ciphertext differs byte-for-byte even when the plaintext is identical. Laravel 6's `originalIsEquivalent` compares raw `$this->attributes` bytes, which never match. Every `save()` then issues a spurious UPDATE; downstream `updated` events, audit logs, and optimistic-locking sentinels fire on every request that touches the model.

**P1 backward-compat regression** — `EncryptedCast::set` / `HashedCast::set` were changed from `return $ciphertext` to `return array($key => $ciphertext)`. The Eloquent flow tolerates both shapes (`array_merge` / our trait's `normalizeDevkitCastResponse`), but any direct caller — `(new EncryptedCast())->set(...)` used as a value transformer outside the model layer — now writes the literal string `'Array'` (with notice) to its destination.

**P2 brittleness** — `hasNativeClassCasts()` probes for the protected helper `isClassCastable` on the parent class. If a future Laravel major renames or relocates that helper, detection silently returns false, the trait re-runs the polyfill path *on top of* native class-cast machinery, and casts execute twice (double-encrypting on save / decrypting twice on read). A sharper signal — interface presence + a polyfill-self-marker — fails closed.

**P2 performance regression** — Native Laravel 7+ uses `HasAttributes::$classCastCache` to memoize each cast's `get()` per attribute per instance. The trait creates a fresh `new EncryptedCast()` and calls `Crypt::decryptString` on every read of `$record->ssn`. In hot paths (loop bodies, view rendering, repeated accessor calls) this is measurable.

This change closes the four highest-severity gaps in one coherent edit: the trait owns serialization, dirty-state, instance caching, and version detection; the cast classes go back to their pre-fix scalar return contract. Two remaining P2 findings from the review (parameterized cast constructor args; Composer files-autoload bypass) are explicitly out of scope and deferred.

## What Changes

### MODIFIED Capability: `devkit-eloquent-helpers`

The `Encrypted and Hashed Casts` requirement is expanded so the Laravel 6 trait-based path is observably indistinguishable from the Laravel 7+ native path across the four behaviours that consumer code depends on:

1. **Serialization** — the trait SHALL override `attributesToArray` so `toArray()` / `toJson()` return the cast's `get()` result for devkit class-cast keys. `JsonSerializable` and `Arrayable` results SHALL be unwrapped to match Laravel 7+ semantics.
2. **Dirty equivalence** — the trait SHALL override `getDirty()` so attributes whose decoded cast values are equal (via `$caster->get()` on both `$this->attributes[$key]` and `$this->original[$key]`) are removed from the dirty set. `isDirty($key)` inherits this through the parent's existing `getDirty()`-based implementation.
3. **Per-instance cast cache** — the trait SHALL memoize each cast's `get()` result per attribute per model instance. Writes via `setAttribute` SHALL invalidate that key; `setRawAttributes` SHALL clear the whole cache.
4. **Native-cast detection** — the trait SHALL detect native support by `interface_exists(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class, false)` AND `! defined('DEVKIT_CASTS_ATTRIBUTES_POLYFILLED')`. The polyfill file SHALL define that constant only inside the `if (! interface_exists(...))` block.

Additionally:

5. **`set()` return shape** — `EncryptedCast::set` and `HashedCast::set` SHALL return scalar values. The trait's existing `normalizeDevkitCastResponse` already converts scalars into the `[key => value]` shape that both the trait and Laravel 7+ native paths need.

### Out of Scope (Deferred)

- **Parameterized cast args** — `'col' => Foo::class . ':arg1,arg2'` syntax; current shipped casts take no constructor arguments, so this is a P2 extension-API gap. Tracked for a follow-up change.
- **Composer files-autoload bypass** — niche packaging path; deferred.

## Capabilities

### Modified Capabilities

- `devkit-eloquent-helpers` — see `specs/devkit-eloquent-helpers/spec.md` delta.

### New Capabilities

None.

## Impact

- **Source**: 3 files modified, 0 added, 0 removed.
  - `src/Laravel/Database/Cast/UsesClassCastCompatibility.php` — adds ~70 LOC (4 overrides + cache field + helpers).
  - `src/Laravel/Database/Cast/EncryptedCast.php` — `set()` reverts to scalar return.
  - `src/Laravel/Database/Cast/HashedCast.php` — `set()` reverts to scalar return.
  - `src/Laravel/Database/Cast/polyfills.php` — defines `DEVKIT_CASTS_ATTRIBUTES_POLYFILLED` inside the interface-existence guard.
- **Tests**: new test class `tests/Laravel/Database/CastCompatibilityTest.php` (or split per behaviour) covering toArray, toJson, isDirty equivalence, cache memoization, and a stub-cast verifying call-count.
- **Consumer API**: zero changes. `use UsesClassCastCompatibility;` line in consumer models still opts in; the trait now simply covers four more interception points.
- **CI matrix**: no matrix change. The Laravel 6 + Testbench 4 cell exercises the trait path; Laravel 11 + Testbench 9 exercises the native path (trait short-circuits via `hasNativeClassCasts`).
- **SemVer**: patch — fixes the regression introduced by `7fb92a8`/`59f0705` before any consumer can rely on the array-return shape.
