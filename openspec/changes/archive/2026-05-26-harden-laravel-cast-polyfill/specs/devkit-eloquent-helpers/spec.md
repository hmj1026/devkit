## MODIFIED Requirements

### Requirement: Encrypted and Hashed Casts
The Laravel sub-namespace SHALL ship `EncryptedCast` and `HashedCast` Eloquent casts (implementing `Illuminate\Contracts\Database\Eloquent\CastsAttributes`) for transparent column-level encryption and one-way hashing of sensitive columns. Both casts SHALL return scalar values from `set()` so that direct callers (consumers using the cast as a standalone value transformer) receive the encrypted / hashed string, not a `[key => value]` array. When used through an Eloquent model on Laravel 7+, the framework's `normalizeCastClassResponse` wraps the scalar into the multi-attribute shape it expects; on Laravel 6 the `UsesClassCastCompatibility` trait's `normalizeDevkitCastResponse` performs the same wrap.

#### Scenario: Encrypted cast round-trip
- **WHEN** a model declares `'ssn' => EncryptedCast::class` and sets `$model->ssn = 'A123456789'`
- **THEN** the DB row stores an encrypted blob; subsequent `$model->ssn` reads decrypt back to `'A123456789'`

#### Scenario: Hashed cast is one-way
- **WHEN** a model declares `'password' => HashedCast::class` and sets `$model->password = 'plain'`
- **THEN** the DB row stores a bcrypt hash, and reads return the hash (not the plain text)

#### Scenario: EncryptedCast::set returns scalar to direct callers
- **WHEN** code calls `(new EncryptedCast())->set($model, 'ssn', 'plain', array())` outside the model flow
- **THEN** the return value is a string (the ciphertext), not an array

#### Scenario: HashedCast::set returns scalar to direct callers
- **WHEN** code calls `(new HashedCast())->set($model, 'password', 'plain', array())` outside the model flow
- **THEN** the return value is a string (the hash), not an array

## ADDED Requirements

### Requirement: Class casts decrypt during serialization on Laravel 6 path
The `UsesClassCastCompatibility` trait SHALL ensure that `$model->toArray()` and `$model->toJson()` return the cast's `get()` result for every key whose `$casts` entry resolves to a devkit class-cast class. `JsonSerializable` results SHALL be unwrapped via `jsonSerialize()`; `\Illuminate\Contracts\Support\Arrayable` results SHALL be unwrapped via `toArray()`. On Laravel 7+ the trait SHALL short-circuit and rely on the framework's native serialization path.

#### Scenario: toArray decrypts EncryptedCast attribute on Laravel 6
- **WHEN** a model using `UsesClassCastCompatibility` with `'ssn' => EncryptedCast::class` stores `'A123456789'` and is reloaded
- **THEN** `$model->toArray()['ssn']` equals `'A123456789'` (not the ciphertext)

#### Scenario: toJson decrypts EncryptedCast attribute on Laravel 6
- **WHEN** the same model is serialized via `$model->toJson()`
- **THEN** `json_decode($model->toJson(), true)['ssn']` equals `'A123456789'`

#### Scenario: toArray on Laravel 7+ defers to native handling
- **WHEN** the trait detects native class-cast support (`hasNativeClassCasts()` returns true)
- **THEN** the trait's `attributesToArray()` returns `parent::attributesToArray()` unchanged

### Requirement: Dirty-state semantics equate decoded cast values
The `UsesClassCastCompatibility` trait SHALL override `getDirty()` so that, for each devkit class-cast key present in the parent's dirty set, the trait compares `$caster->get($this, $key, $this->attributes[$key], ...)` against `$caster->get($this, $key, $this->original[$key], ...)`; when those decoded values are strictly equal the key SHALL be removed from the dirty set. `isDirty($key)`, which is implemented in `HasAttributes` on top of `getDirty()`, inherits this semantics. On Laravel 7+ the trait SHALL short-circuit and rely on the framework's native `originalIsEquivalent`.

#### Scenario: Re-assigning the same plaintext does not mark encrypted column dirty
- **WHEN** a model with `'ssn' => EncryptedCast::class` is loaded, then `$model->ssn = 'A123456789'` is re-assigned (the same plaintext)
- **THEN** `$model->isDirty('ssn')` returns `false` even though `$this->attributes['ssn']` now holds a fresh ciphertext with a different IV than `$this->original['ssn']`

#### Scenario: Genuine value change still flags dirty
- **WHEN** the same model is then assigned `$model->ssn = 'B987654321'`
- **THEN** `$model->isDirty('ssn')` returns `true`

### Requirement: Per-instance class-cast cache
The `UsesClassCastCompatibility` trait SHALL memoize each devkit class-cast key's `get()` result per attribute per model instance. Cache entries SHALL be invalidated when `setAttribute()` writes to that key, and the cache SHALL be cleared whenever `setRawAttributes()` is called (covering hydration, `refresh()`, `fresh()`, and `replicate()`).

#### Scenario: Repeated reads call cast::get() exactly once
- **WHEN** a fixture cast counts its `get()` invocations and a model is read five times (`$m->col`, `$m->col`, ...)
- **THEN** the counter equals one for that model instance

#### Scenario: Writing the attribute invalidates the cache
- **WHEN** after the five reads, `$m->col = 'new'` is executed and `$m->col` is read again
- **THEN** the counter equals two

#### Scenario: setRawAttributes clears the entire cache
- **WHEN** `$m->setRawAttributes($m->getAttributes(), true)` is invoked and `$m->col` is read
- **THEN** the cast's `get()` runs again for `col`

### Requirement: Native-vs-polyfill detection by interface presence
The `UsesClassCastCompatibility` trait's `hasNativeClassCasts()` SHALL return `true` if and only if `\Illuminate\Contracts\Database\Eloquent\CastsAttributes` interface is loaded AND the constant `DEVKIT_CASTS_ATTRIBUTES_POLYFILLED` is NOT defined. The polyfill file (`src/Laravel/Database/Cast/polyfills.php`) SHALL define that constant only when it declares the interface itself.

#### Scenario: Laravel 7+ — native interface present, no polyfill marker
- **WHEN** the application loads `illuminate/contracts ^7.0 || ^8.0 || ... || ^11.0` so `CastsAttributes` exists natively
- **THEN** `hasNativeClassCasts()` returns `true` and the trait short-circuits to `parent::*`

#### Scenario: Laravel 6 — polyfill declared the interface
- **WHEN** the application is on Laravel 6 and `polyfills.php` declared the polyfill interface and set the marker constant
- **THEN** `hasNativeClassCasts()` returns `false` and the trait's overrides handle get/set/serialize/dirty/cache

#### Scenario: Detection does not depend on protected helpers
- **WHEN** a maintainer greps `UsesClassCastCompatibility.php` for `isClassCastable`
- **THEN** no reference is found (detection no longer probes that protected method)
