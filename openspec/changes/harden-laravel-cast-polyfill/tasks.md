# Tasks

## 1. Spec authoring

- [x] 1.1 Author `proposal.md` summarizing seven review findings and committing to four fixes.
- [x] 1.2 Author `design.md` covering detection, serialization, dirty equivalence, cache, set-shape revert.
- [x] 1.3 Author `specs/devkit-eloquent-helpers/spec.md` delta — MODIFIED `Encrypted and Hashed Casts` + ADDED detection / serialization / dirty / cache requirements.
- [x] 1.4 `openspec validate harden-laravel-cast-polyfill --strict` clean.

## 2. RED — failing tests (write before any source change)

- [x] 2.1 `tests/Laravel/Database/CastCompatibilityTest.php::testToArrayDecryptsEncryptedCast` — `SensitiveRecord::create(['ssn' => 'A123'])->fresh()->toArray()['ssn'] === 'A123'`.
- [x] 2.2 same file `testToJsonContainsDecryptedSsn` — `json_decode($record->fresh()->toJson(), true)['ssn'] === 'A123'`.
- [x] 2.3 same file `testReSettingSameEncryptedValueIsNotDirty` — assign `'A123'` twice with `refresh()` between; `isDirty('ssn') === false`.
- [x] 2.4 same file `testHashedCastDirtyEquivalence` — assign already-hashed value retrieved from DB; `isDirty('password') === false`. (Passes on HEAD as non-regression — HashedCast::get returns raw bytes so equivalence already holds in L6; remains a guard.)
- [x] 2.5 New stub cast `tests/Laravel/Database/Fixture/CountingCast.php` (implements `CastsAttributes`, increments static counter on `get`). Use in a fixture model; test reads same attribute 5×, asserts counter == 1 across reads, == 2 after one setAttribute + one read.
- [x] 2.6 `testNativeDetectionUsesInterfacePresence` — augmented with source-grep assertion: trait source must not reference `isClassCastable`; behaviour mirrors `interface_exists(...) AND ! defined(DEVKIT_CASTS_ATTRIBUTES_POLYFILLED)`.
- [x] 2.7 `testEncryptedCastSetReturnsScalar` + `testHashedCastSetReturnsScalar` — direct call returns a string, not an array.
- [x] 2.8 Ran `vendor/bin/phpunit tests/Laravel/Database/CastCompatibilityTest.php` on L6 cell (PHP 7.4 × Laravel 6.* × PHPUnit 9.6.34): 11 tests, 7 failures — RED evidence captured for 2.1, 2.2, 2.3, 2.5 (5-read count), 2.6 (source-grep), 2.7 (both casts). Non-regression passes: testGenuineValueChangeStillFlagsDirty, testHashedCastDirtyEquivalence, testWritingAttributeInvalidatesCache (vacuous on HEAD), testSetRawAttributesClearsCache (vacuous on HEAD).

## 3. GREEN — implementation

- [x] 3.1 Edit `src/Laravel/Database/Cast/UsesClassCastCompatibility.php`:
  - Add `private $devkitClassCastCache = array();`.
  - Add private `getDevkitClassCastableValue(string $key)` helper that reads/writes the cache.
  - Rewrite `getAttributeValue` to delegate to the helper.
  - Add `attributesToArray()` override (parent::attributesToArray then post-process devkit cast keys).
  - Add `getDirty()` override (decoded-value equivalence for devkit cast keys).
  - Add `setRawAttributes(array $attributes, $sync = false)` override clearing the cache.
  - In `setAttribute`, `unset($this->devkitClassCastCache[$key])` and per-attribute in the foreach.
  - Rewrite `hasNativeClassCasts()` to use `interface_exists(...) && ! defined('DEVKIT_CASTS_ATTRIBUTES_POLYFILLED')`.
  - Add private `serializeDevkitCastValue($value)` helper for JsonSerializable / Arrayable unwrap.
- [x] 3.2 Edit `src/Laravel/Database/Cast/polyfills.php` — define `DEVKIT_CASTS_ATTRIBUTES_POLYFILLED` inside the `if (! interface_exists(...))` block, guarded by `! defined(...)`.
- [x] 3.3 Edit `src/Laravel/Database/Cast/EncryptedCast.php` — `set()` returns scalar (revert array wrapping).
- [x] 3.4 Edit `src/Laravel/Database/Cast/HashedCast.php` — `set()` returns scalar (revert array wrapping).
- [x] 3.5 Re-ran the test file from 2.8 — all GREEN (11/11 on PHP 7.4 × Laravel 6 cell).

## 4. Verify

- [x] 4.1 Full PHPUnit run green locally on **both** matrix endpoints exercised via dockerized cells: PHP 7.4 × Laravel 6 (trait path) → 221/221 tests, 422 assertions; PHP 8.2 × Laravel 11 (native path) → 221/221, 420 assertions, 2 intentional `markTestSkipped` on the two scenarios that are polyfill-only by spec (decoded-value dirty equivalence; scalar-result cast memoization).
- [x] 4.2 `vendor/bin/php-cs-fixer fix --dry-run --diff` clean against `src/Laravel/Database/Cast/*.php` + new test/fixture files.
- [ ] 4.3 CI matrix run on the branch — needs push + GitHub Actions; local endpoints validated.
- [ ] 4.4 Smoke check: in a Laravel 6 sandbox, `dd(SensitiveRecord::first()->toArray())` shows plaintext `ssn`. (Skip — proven by `testToArrayDecryptsEncryptedCast` in the L6 cell.)
- [x] 4.5 `openspec validate harden-laravel-cast-polyfill --strict` → "Change 'harden-laravel-cast-polyfill' is valid".

## 5. Document & ship

- [ ] 5.1 Suggested commit slices:
  - `test(laravel/cast): add failing compatibility tests for L6 polyfill` (RED commit, 2.1–2.7).
  - `fix(laravel/cast): decrypt class-castable attributes in toArray/toJson on laravel 6` (3.1 attributesToArray + helpers + tests 2.1–2.2 pass).
  - `fix(laravel/cast): equate dirty state by decrypted cast value` (3.1 getDirty + 2.3–2.4 pass).
  - `perf(laravel/cast): memoize devkit class-cast results per instance` (3.1 cache + 2.5 pass).
  - `refactor(laravel/cast): revert set() return shape; trait owns array normalization` (3.3 + 3.4 + 2.7 pass).
  - `refactor(laravel/cast): detect native casts via interface presence + polyfill marker` (3.1 hasNativeClassCasts + 3.2 + 2.6 pass).
- [x] 5.2 Update `README.md` cast section with a one-line note: "Laravel 6 consumers must `use UsesClassCastCompatibility` on models with `EncryptedCast`/`HashedCast`."
- [ ] 5.3 Open PR; on merge, `openspec archive harden-laravel-cast-polyfill` to fold the delta into `openspec/specs/devkit-eloquent-helpers/spec.md`.
