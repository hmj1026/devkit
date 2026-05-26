---
name: polyfill-dispatcher-template
description: 'Scaffold a new dual-version dispatcher (Monolog 2/3, Flysystem 1/2/3, PHPUnit 8/9, or any "old vs new" major split) in devkit''s canonical shape. Generates the dispatcher stub + two Internal concretes + a test stub, wires composer.json autoload.files, and queues the polyfill-reviewer agent. Use when adding a class whose API differs between two majors of a runtime dep and PHP''s LSP rules prevent a single class from satisfying both signatures (the typical Monolog 2 vs 3 case in this repo). Not for cosmetic version branches — those go inline with a single version_compare.'
disable-model-invocation: true
---

# Polyfill dispatcher template

The canonical multi-major dispatcher in devkit is a four-file unit:

1. **Public stub** (`src/<Area>/<Feature>.php`)
   - Detects which major is installed
   - `require_once` the matching Internal/ concrete
   - `class_alias` it to the public name
2. **Old-major concrete** (`src/<Area>/Internal/<Feature>M2.php`) — for Monolog
   2 / Flysystem 1 / PHPUnit 8 etc.
3. **New-major concrete** (`src/<Area>/Internal/<Feature>M3.php`) — for Monolog
   3 / Flysystem 3 / PHPUnit 10 etc.
4. **Shared trait** (`src/<Area>/Internal/Handles<Feature>.php`) when the two
   concretes share dispatch / formatting logic. Optional — skip if the
   concretes are independent.

Reference implementation: `src/Logging/GoogleChat/`
(`GoogleChatLogHandler.php` + `Internal/{M2,M3,HandlesGoogleChatCard}.php`).

> This is a **procedural** skill — the user must approve each file
> before Write. Do not batch-create silently.

## When to use

- A class needs different method signatures between two majors of a dep
  (most common: Monolog 2 `write(array $record)` vs Monolog 3
  `write(LogRecord $record)`)
- A class touches APIs that disappeared / renamed between majors
  (Flysystem 1 `getMimetype()` vs 2/3 `mimeType()`)
- An interface gained / lost methods between majors and you need a fork
  per version

## When NOT to use

- Single inline `version_compare` or `class_exists` guard inside one
  method — keep it inline, don't fork the whole class
- The two majors are signature-compatible but error semantics differ —
  use a trait and inline guards instead
- You only support one major (no polyfill needed)

## Inputs

Ask the user — **never assume**:

| Slot | Example | Purpose |
|------|---------|---------|
| Feature name | `GoogleChatLogHandler` | The public class name |
| Namespace | `Devkit\Logging\GoogleChat` | PSR-4 namespace |
| Area path | `src/Logging/GoogleChat` | Filesystem location |
| Detection condition | `class_exists('Monolog\\LogRecord')` | Truthy when **new** major present |
| Old-major suffix | `M2` | Convention: `M<old>` (Monolog2 → `M2`, Flysystem1 → `F1`) |
| New-major suffix | `M3` | Convention: `M<new>` |
| Parent class (old) | `\Monolog\Handler\AbstractProcessingHandler` (Monolog 2) | What `M2.php` extends |
| Parent class (new) | `\Monolog\Handler\AbstractProcessingHandler` (Monolog 3) | What `M3.php` extends — often same class with different method signature |
| Method that differs | `write(array $record): void` vs `write(LogRecord $record): void` | The signature fork |
| Shared trait? | yes/no | Only if both concretes share substantial body |

If the user supplies only the feature name + detection condition, derive
the rest from devkit conventions and read back for confirmation.

## Procedure

### Step 1 — Confirm inputs

Repeat the inputs back to the user as a checklist. If anything is
ambiguous (e.g. "Monolog handler" without specifying which Monolog
parent), ask.

### Step 2 — Verify the canonical reference

```bash
test -f src/Logging/GoogleChat/GoogleChatLogHandler.php
```

If the reference is missing, the dispatcher convention may have changed
— ask the user to point at the current canonical implementation before
proceeding.

### Step 3 — Generate the dispatcher stub

Path: `<area>/<Feature>.php`

```php
<?php

/**
 * Dispatcher stub for the dual <runtime> <old-major>/<new-major>
 * <Feature>.
 *
 * Composer PSR-4 autoloads this file when code references
 * `<Namespace>\<Feature>`. Detects which <runtime> major is installed
 * via `<detection-condition>` and requires the matching concrete from
 * Internal/, then exposes it under the canonical name via
 * `class_alias`.
 *
 * Rationale: <one-line summary of the signature divergence>
 *
 * The class_alias guard prevents redefinition errors if some other code
 * path defined the alias first.
 */

namespace <Namespace>;

if (!class_exists(<Feature>::class, false)) {
    if (<detection-condition>) {
        require_once __DIR__ . '/Internal/<Feature><new-suffix>.php';
        if (!class_exists(<Feature>::class, false)) {
            class_alias(
                Internal\<Feature><new-suffix>::class,
                <Feature>::class
            );
        }
    } else {
        require_once __DIR__ . '/Internal/<Feature><old-suffix>.php';
        if (!class_exists(<Feature>::class, false)) {
            class_alias(
                Internal\<Feature><old-suffix>::class,
                <Feature>::class
            );
        }
    }
}
```

Do not omit the inner `class_exists` check — it preserves idempotence
when composer autoloader runs the file twice (e.g. on dev-mode rebuild).

### Step 4 — Generate the two Internal concretes

Path: `<area>/Internal/<Feature><old-suffix>.php` and
`<area>/Internal/<Feature><new-suffix>.php`

Each starts with:

```php
<?php

namespace <Namespace>\Internal;

use <parent-class>;
// ... other use statements

/**
 * @internal Not part of the public API. Resolved via
 *           <Namespace>\<Feature> dispatcher at autoload time.
 */
final class <Feature><suffix> extends <parent-class>
{
    // ... method using the major-specific signature
}
```

Body content: ask the user to paste the actual implementation, or
generate a `// TODO: implement` stub for them to fill in. **Do not
guess the method body** — different features have very different shape.

### Step 5 — Optional shared trait

If both concretes share a substantial dispatch / formatting body, extract
to `<area>/Internal/Handles<Feature>.php`:

```php
<?php

namespace <Namespace>\Internal;

trait Handles<Feature>
{
    // shared helpers — kept signature-agnostic
}
```

Then `use Handles<Feature>;` in both M2 and M3.

### Step 6 — Generate the test stub

Path: `tests/<Area>/<Feature>Test.php` (or `tests/Logging/<Feature>Test.php`
following devkit convention)

```php
<?php

namespace Devkit\Tests\<Area>;

use Devkit\<Namespace>\<Feature>;
use PHPUnit\Framework\TestCase;

class <Feature>Test extends TestCase
{
    public function test_dispatcher_resolves_to_installed_major(): void
    {
        // Assert which Internal/ class the alias resolved to.
        // The expected branch depends on which Monolog (or other dep)
        // version is installed in THIS test run — read it from the
        // detection condition, not from a hardcoded assumption.

        $expected = (<detection-condition>)
            ? \Devkit\<Namespace>\Internal\<Feature><new-suffix>::class
            : \Devkit\<Namespace>\Internal\<Feature><old-suffix>::class;

        $this->assertSame($expected, get_parent_class(<Feature>::class) ?: <Feature>::class);
    }

    // Add behaviour tests per branch — name them
    // test_..._on_<new-major>() and skip with markTestSkipped() when
    // the wrong branch is loaded. See tests/Logging/GoogleChat/ for the
    // pattern.
}
```

The dispatcher resolution test is mandatory — it's the safety net for
the auto-aliasing logic.

### Step 7 — Update composer.json autoload.files

If the dispatcher stub depends on side-effect file loading (it does, by
design), add it to `composer.json` `autoload.files`:

```bash
grep -A 10 '"files"' composer.json
```

Append the new path. Example final shape:

```json
"files": [
    "src/Laravel/Database/Cast/polyfills.php",
    "src/Core/Support/helpers.php",
    ...
    "src/<Area>/<Feature>.php"
]
```

Then run `composer dump-autoload` so the side-effect file actually loads
on next request.

### Step 8 — Queue polyfill-reviewer

Once the four files exist and composer.json is updated, the dhpk
`post-edit-polyfill-sentinel.sh` hook will write `.pending-polyfill-review`
on its own (assuming `library-author` module is active and the file
bodies contain a recognised guard pattern). Verify:

```bash
cat .claude/artifacts/sessions/.pending-polyfill-review
```

If the sentinel is empty, the hook did not detect the guard — most
likely cause is the dispatcher stub uses `class_exists` (a guard
pattern) but the path is outside the hook's default `skip_paths`. Check
`modules/library-author/module.yaml` `library_author.skip_paths` and
remove `src/` if it's listed.

If `library-author` is not active, fall back to manual:

```bash
/skill dhpk:polyfill-version-matrix-audit
```

### Step 9 — Trigger CI matrix completeness review

Editing `composer.json` triggers the project hook
`.claude/hooks/post-edit-ci-matrix.sh`, which writes
`.pending-ci-matrix-review`. The `ci-matrix-completeness-reviewer` agent
will run on session stop. No manual step needed.

## Output (skill summary)

When the procedure completes:

```
✅ Created dispatcher: <path>
✅ Created old-major concrete: <path>
✅ Created new-major concrete: <path>
   <optional> Shared trait: <path>
✅ Created test stub: <path>
✅ Updated composer.json autoload.files (run: composer dump-autoload)
✅ Sentinel queued: <which sentinels were written>

Next steps:
1. Fill in method bodies (search for "// TODO: implement")
2. Run composer dump-autoload
3. Run composer matrix:test -- <php> <laravel> on the lowest cell that
   enters each branch (verify both branches load)
4. Let polyfill-reviewer and ci-matrix-completeness-reviewer run on
   session stop
```

## Common traps

- **Forgetting the inner `class_exists` guard inside the require block**
  — causes "Cannot redeclare class" on composer dev-mode autoloader
  rebuilds.
- **Suffix collision** — if two features share a suffix (`M2`, `M3`)
  inside the same Internal/ namespace, PHP's class table will collide.
  Use the area subdirectory; devkit's `Internal\` namespaces are
  per-area, not shared across the repo.
- **Forgetting composer.json autoload.files** — the dispatcher stub
  must be eagerly loaded (it's side-effect code, not lazy class
  resolution). Forgetting this means the alias is never registered and
  consumers get "class not found" only on first instantiation in prod.
- **Hardcoding the parent class** — Monolog 2 and 3 BOTH have
  `AbstractProcessingHandler` but the signature differs. The parent
  class name is the same in both concretes; only the method signature
  changes.
- **Testing only the resolved branch** — both `M2` and `M3` must have
  behaviour tests. Use `markTestSkipped()` when the wrong major is
  installed; do NOT rely on `version_compare()` to fork the test, use
  the same detection condition the dispatcher uses (consistency).

## Related

- `dhpk:polyfill-version-matrix-audit` — deep audit of all guards
  (available at dhpk ≥ 0.2.x root skills)
- `dhpk:matrix-cell-onboard` — when adding a new cell that exercises
  this dispatcher (root-alias command, available at **dhpk ≥ 0.3.0**;
  earlier versions only have the module-scoped skill at
  `modules/library-author/skills/matrix-cell-onboard/`)
- `dhpk:composer-package-hygiene` — semver implication if this dispatcher
  raises the floor
- `.claude/agents/ci-matrix-completeness-reviewer.md` — verifies the
  composer.json constraint change is reflected in CI matrix
