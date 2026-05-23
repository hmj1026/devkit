# Devkit v2 Roadmap

Devkit v1 keeps PHP 7.3 and Laravel 6/7/8 compatibility. Devkit v2 can raise the floor to PHP 8.1+ and simplify several compatibility layers.

## Planned Changes

- Replace reflection-style `AbstractEnum` usage with native PHP enums plus helper traits.
- Drop Monolog 2 support and target Monolog 3 only.
- Drop Flysystem 1 and 2 compatibility adapters and target Flysystem 3 only.
- Consider wrapping `spatie/laravel-activitylog` as the audit logging engine while keeping Devkit target contracts stable.
- Consider `spatie/laravel-package-tools` for provider boilerplate.
- Add migration helpers for applications moving from `AbstractEnum` classes to native enums.

## Compatibility Policy

The public contracts should remain stable where possible. Breaking changes should be limited to platform floors, dependency majors, and scaffolding output that requires newer PHP syntax.
