# harden-laravel-cast-polyfill

Close behavioural gaps between `UsesClassCastCompatibility` (Laravel 6 polyfill path) and Laravel 7+ native class casts: `toArray`/`toJson` decryption, dirty-state semantics, per-instance cast cache, sharper native-vs-polyfill detection. Revert the breaking scalar→array `set()` return-shape change so direct callers keep working.
