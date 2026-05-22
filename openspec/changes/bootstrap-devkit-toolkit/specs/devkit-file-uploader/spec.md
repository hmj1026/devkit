## ADDED Requirements

### Requirement: Director Pattern for File Types
`Devkit\Storage\Uploader\AbstractDirector` SHALL provide a base for type-specific directors, with concrete `FileDirector` and `ImageDirector` subclasses available out of the box.

#### Scenario: Manager resolves director by name
- **WHEN** code calls `$manager->director('image')`
- **THEN** the resolved director is an instance of `ImageDirector` extending `AbstractDirector`

#### Scenario: Default director from config
- **WHEN** the default director key in config is `'file'` and code calls `$manager->upload($uploadedFile)`
- **THEN** the call is proxied to `FileDirector::upload($uploadedFile)`

### Requirement: Flysystem 3 Filesystem Operator
Directors SHALL accept a `League\Flysystem\FilesystemOperator` instance for the storage layer, replacing the original Laravel Storage facade dependency.

#### Scenario: In-memory upload for testing
- **WHEN** a director is configured with `League\Flysystem\InMemory\InMemoryFilesystemAdapter` and code uploads a file
- **THEN** the file content is readable from the in-memory adapter without touching disk

### Requirement: MIME and Size Validation
The director SHALL validate uploaded file MIME type against an `allowMimeTypes` list and reject files exceeding `allowFileSize` bytes.

#### Scenario: Reject oversized file
- **WHEN** an upload's size exceeds the configured limit
- **THEN** the director raises `Devkit\Storage\Exception\FileFormatException`

#### Scenario: Reject disallowed MIME
- **WHEN** an upload's MIME is not in the allow list
- **THEN** the director raises `Devkit\Storage\Exception\FileFormatException`

### Requirement: Path Generation Strategies
The director SHALL support multiple path generation methods via `PathMethodEnum`: `MD5` (hash-bucketed) and `DATE` (`Y/m/d` bucketed).

#### Scenario: MD5 path
- **WHEN** path method is `MD5` and the generated filename is `abcdef1234`
- **THEN** the storage path begins with `ab/cd/abcdef1234.<ext>`

#### Scenario: Date path
- **WHEN** path method is `DATE` and the upload occurs on 2026-05-20
- **THEN** the storage path begins with `2026/05/20/`

### Requirement: Multi-Disk Replication
The director SHALL accept multiple destination disks and write the same file to all of them, returning a `FileContract` exposing per-disk URLs.

#### Scenario: Replicate to local and s3
- **WHEN** disks are configured as `['local', 's3']` and code uploads one file
- **THEN** the file is written to both disks and `$file->getUrl('s3')` and `$file->getUrl('local')` both return populated URLs

### Requirement: Visibility Mapping
The `VisibilityEnum` SHALL map to Flysystem 3's `League\Flysystem\Visibility::PUBLIC` and `PRIVATE` constants.

#### Scenario: Public visibility
- **WHEN** the director is configured with `VisibilityEnum::PUBLIC`
- **THEN** the file is written with `Visibility::PUBLIC` and accessible without signed URLs (for adapters supporting it)
