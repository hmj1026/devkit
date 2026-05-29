# Contributing to hmj1026/devkit

Thanks for your interest in improving devkit. This document describes the workflow,
conventions, and gates the project expects.

## Ground rules

- **CI is authoritative.** A change is not done until the full GitHub Actions matrix (19
  PHP × Laravel cells) and the `quality` job are green. Local checks catch most issues but
  only CI runs the complete cartesian.
- **The framework-agnostic core stays framework-agnostic.** Code under `Devkit\Core\*`,
  `Devkit\Http\*`, `Devkit\Storage\*`, `Devkit\Search\*`, `Devkit\Messaging\*`,
  `Devkit\Logging\*`, and `Devkit\Ui\*` must not import `Illuminate\*`. Laravel-specific code
  lives in `Devkit\Laravel\*`.
- **PHP 7.3 is the floor.** Production code must use only PHP 7.3-compatible syntax (no typed
  properties, arrow functions, `match`, nullsafe `?->`, enums, or constructor promotion).
  Dependency *runtime variants* (Monolog 2/3, Flysystem 1/2/3, meta-tags 2/3) are selected by
  Composer per consumer and adapted at runtime.

## Branch & PR flow

- Branch off `develop` (e.g. `feature/...`, `fix/...`).
- Open PRs against `develop`. Releases are cut by merging `develop` → `master` and tagging.
- Use [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`,
  `refactor:`, `docs:`, `test:`, `chore:`, `ci:`).

## OpenSpec is the SSOT for non-trivial changes

Non-trivial work (new capabilities, cross-cutting policy, behaviour changes) is specified in
[OpenSpec](https://github.com/Fission-AI/OpenSpec) **before** implementation:

- Canonical specs: `openspec/specs/<capability>/spec.md`.
- A change carries a proposal, design, and a spec delta under
  `openspec/changes/<name>/specs/<capability>/spec.md`.
- Validate with `openspec validate <change>`; archive on completion.

Small fixes that don't warrant a spec can go straight to a PR.

## Polyfill discipline

The library's core value is silent multi-major-version support, so every runtime version
guard (`version_compare`, `class_exists`, `interface_exists`, `method_exists`,
`Composer\InstalledVersions::satisfies`, `PHP_VERSION_ID`) is a fork in the execution tree:

1. **Both branches need test coverage.** Where both majors cannot load in one process (e.g.
   the Monolog 2/3 handler, the Flysystem 1/2-3 bridge), write symmetric **skip-guarded**
   tests — one per major — so each branch is proven on the CI cell where that major is
   installed. Symmetric tests need symmetric skips.
2. **Symmetric edits.** A fix to one branch usually implies the other branch needs
   verification; state in the commit which branch was checked.

## Local commands

```bash
composer test:core      # pure-PHP suite (no Laravel)
composer test:laravel   # Orchestra Testbench suite
composer test:unit      # both
composer lint:fix       # php-cs-fixer (no emojis; follow existing style)
composer stan           # PHPStan level 5 (needs phpstan on PATH; runs on PHP 8.2)

# Reproduce one CI cell, then clean up:
composer matrix:test -- 8.2 11
git checkout -- composer.json && composer install
```

PHPStan 2.x cannot install on the PHP 7.3 cells, so it is not in `require-dev`; install it
globally (or rely on the CI `quality` job). New code must analyse clean — the
`phpstan-baseline.neon` only freezes pre-existing cross-version polyfill artifacts; do not
add to it without a documented rationale.

## Versioning

Semantic Versioning. Public contracts and documented facades are stable within `1.x`.
Breaking changes (PHP floor bump to `^8.1`, dropping Monolog 2 / Flysystem 1, newer-syntax
scaffolding) are reserved for `2.0` — see [`docs/v2-roadmap.md`](./docs/v2-roadmap.md). Do not
slip a breaking change into a `1.x` patch.
