## ADDED Requirements

### Requirement: Reflection-Based Enum Constants
The `Devkit\Core\Enum\AbstractEnum` base class SHALL expose its public class constants as enumerable values, memoising the reflection result per subclass to avoid repeated runtime cost.

#### Scenario: Enumerate constants to array
- **WHEN** a subclass declares `const ACTIVE = 1; const INACTIVE = 0;` and code calls `ChildEnum::toArray()`
- **THEN** the result equals `['ACTIVE' => 1, 'INACTIVE' => 0]`

#### Scenario: Values and keys helpers
- **WHEN** code calls `ChildEnum::values()` and `ChildEnum::keys()`
- **THEN** they return `[1, 0]` and `['ACTIVE', 'INACTIVE']` respectively

### Requirement: Alias Lookup
The base class SHALL support secondary alias lookup via a `protected static $aliases` map keyed by constant name.

#### Scenario: Lookup by alias key
- **WHEN** the subclass declares `protected static $aliases = ['ACTIVE' => 'enabled']` and code calls `ChildEnum::getByAlias('enabled')`
- **THEN** the result equals `1`

#### Scenario: Missing alias returns null
- **WHEN** code calls `ChildEnum::getByAlias('nonexistent')`
- **THEN** the result equals `null`

### Requirement: Display Content Mapping
The base class SHALL support a `mapping()` method returning constant name → human-readable label, declared via `protected static $contents`.

#### Scenario: Mapping returns labels
- **WHEN** the subclass declares `protected static $contents = ['ACTIVE' => '啟用', 'INACTIVE' => '停用']` and code calls `ChildEnum::mapping()`
- **THEN** the result equals `['ACTIVE' => '啟用', 'INACTIVE' => '停用']`

#### Scenario: Mapping fallback to constant name
- **WHEN** a constant has no entry in `$contents` and code calls `ChildEnum::content('ACTIVE')`
- **THEN** the result equals the constant name `'ACTIVE'`

### Requirement: Framework Independence
`Devkit\Core\Enum\AbstractEnum` SHALL NOT import or depend on any `Illuminate\*` namespace.

#### Scenario: Loads in pure-PHP project
- **WHEN** the class is autoloaded in a project without `illuminate/*` installed
- **THEN** no missing-class errors are raised
