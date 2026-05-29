# CLAUDE.md — devkit

Project-level harness pointer for Claude Code. Loaded automatically at session start.

## Stack

Multi-version PHP library: `^7.3 || ^8.0` × Laravel 6–11 × PHPUnit 8.5/9.6 × Monolog 2/3 × Meta-Tags 2/3. Framework-agnostic core under `Devkit\Core\*` (no Illuminate imports); Laravel glue under `Devkit\Laravel\*`. Authoritative truth: `composer.json` `require` and `.github/workflows/tests.yml` `strategy.matrix`. See `README.md` for the supported runtime table.

## Active dhpk modules

Pinned in `.claude/settings.local.json` `pluginConfigs.dhpk@dhpk.options.modules`:

```
php-7.4, php-8.x, laravel-6, laravel-7, laravel-8, laravel-9, laravel-10, laravel-11, phpunit-9, phpunit-10, phpunit-11, library-author
```

Six-color sentinel-driven reviewers (code / db / sec / frontend / doc / polyfill) fire automatically post-edit. dhpk's php-cs-fixer post-edit hook + pre-commit static-check gate are wired through the `php-7.4` module. The `library-author` module (**requires dhpk ≥ 0.3.0**; `learning_db_enabled` requires dhpk ≥ 0.6.0; earlier cached versions silently drop unknown options from `DHPK_ACTIVE_MODULES`) is the cross-cutting glue for multi-major-library work (polyfill auto-trigger, OpenSpec artifact guard, matrix-cell onboard, dual-testsuite map). If sentinels are not firing post-edit, verify the loaded dhpk cache version: `ls ~/.claude/plugins/cache/dhpk/dhpk/` — see memory `reference_dhpk_cache_caveat.md`.

Coverage gap: dhpk has no `php-7.3` or `phpunit-8` module. The CI matrix has PHP 7.3 cells (Laravel 6/7) and PHPUnit `^8.5` cells; `php-7.4` and `phpunit-9` serve as the closest proxy reviewers.

Project-only additions (do not belong in dhpk because they're devkit-shape-specific):

- **`.claude/agents/ci-matrix-completeness-reviewer.md`** — seventh-color reviewer that verifies every PHP × Laravel cell declared in `composer.json` constraints has a row in `.github/workflows/tests.yml`. Triggered by the `.pending-ci-matrix-review` sentinel, written by `.claude/hooks/post-edit-ci-matrix.sh` on composer / workflow edits.
- **`.claude/skills/polyfill-dispatcher-template/`** — user-only skill (`disable-model-invocation: true`) that scaffolds devkit's canonical four-file dispatcher (stub + M2/M3 Internal concretes + test). Use when adding a class whose API differs between two majors of a dep (Monolog 2 vs 3, Flysystem 1 vs 3, etc.).

## Polyfill discipline

The library's core value is silent multi-major-version support. Every runtime version guard (`version_compare`, `class_exists`, `method_exists`, `interface_exists`, `Composer\InstalledVersions::satisfies`, `PHP_VERSION_ID`) is a fork in the execution tree. Three rules:

1. **Auto-trigger reviewer.** Any `.php` edit containing a version guard sets the `.pending-polyfill-review` sentinel; the `polyfill-reviewer` agent (library-author module) audits coverage on the spot. For an out-of-band deep audit (one guard, all files), invoke the `polyfill-version-matrix-audit` skill manually.
2. **Both branches need test coverage.** The reviewer cross-references guards against the CI matrix and flags uncovered branches. Don't suppress its findings without an explicit "this branch is unreachable because X" note in the commit message.
3. **Symmetric edits.** A fix to one branch (e.g. `fix: polyfill laravel 6 class casts`) usually implies the symmetric branch needs verification — the new-major branch may already be correct, but the diff should explicitly mention which branch was checked and which was not.

For diff-level blast-radius questions ("which of the 13 matrix cells could this change break?"), invoke the `version-matrix-impact-reviewer` agent. For onboarding a new cell to the matrix, invoke `/dhpk:matrix-cell-onboard`.

## Change workflow

OpenSpec is the SSOT for non-trivial changes. Skills under `.claude/skills/openspec-*` cover the lifecycle (new / continue / verify / archive). Memory `reference_openspec_workflow.md` documents the artifact-name gotcha (`specs` vs `spec-delta`). For small fixes that don't warrant a spec, follow the workflow in memory `feedback_workflow_review_first.md`.

## Composer scripts (entry points dhpk hooks consume)

```
composer test:core      # phpunit --testsuite=core
composer test:laravel   # phpunit --testsuite=laravel
composer test:unit      # both testsuites
composer lint           # php-cs-fixer --dry-run --diff
composer lint:fix       # php-cs-fixer
```

Matrix-subset helpers (reproduce a single CI cell locally; cleanup with `git checkout -- composer.json composer.lock && composer install`):

```
composer matrix:list                          # list every (php, laravel) cell in tests.yml
composer matrix:install -- <php> <laravel>    # install deps for one cell (e.g. 8.2 11)
composer matrix:test -- <php> <laravel>       # install + run both testsuites
```

`dhpk:precommit` and the `php-7.4` module's pre-commit gate call the `test:*` / `lint*` scripts.

## CI is authoritative

Local php-cs-fixer + a single matrix cell catches most regressions, but only `.github/workflows/tests.yml` runs the full PHP × Laravel × PHPUnit cartesian. Before tagging a release, the CI run must be green on all cells, not just the ones touched by recent diffs.
