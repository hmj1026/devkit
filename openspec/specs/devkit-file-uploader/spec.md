# devkit-file-uploader Specification

## Purpose
Director-pattern uploader over Flysystem 1/2/3 with MIME/size validation, multiple path strategies, multi-disk replication, and visibility mapping.

## Requirements

### Requirement: Director Pattern for File Types
`Devkit\Storage\Uploader\AbstractDirector` SHALL provide a base for type-specific directors, with concrete `FileDirector` and `ImageDirector` subclasses available out of the box.

#### Scenario: Manager resolves director by name
- **WHEN** code calls `$manager->director('image')`
- **THEN** the resolved director is an instance of `ImageDirector` extending `AbstractDirector`

#### Scenario: Default director from config
- **WHEN** the default director key in config is `'file'` and code calls `$manager->upload($uploadedFile)`
- **THEN** the call is proxied to `FileDirector::upload($uploadedFile)`

### Requirement: Flysystem 1/2/3 Filesystem Abstraction
Directors SHALL accept a storage handle that works across Flysystem v1, v2, and v3 (`league/flysystem ^1.1 || ^2.0 || ^3.0`), so the module is installable on Laravel 6/7/8 (flysystem v1) as well as Laravel 9+ (flysystem v3). An internal adapter SHALL normalise the API differences between `League\Flysystem\FilesystemInterface` (v1) and `League\Flysystem\FilesystemOperator` (v2/v3) — write/read/delete signatures, visibility constants, and exception classes.

#### Scenario: In-memory upload on Flysystem v2/v3
- **WHEN** a director is configured with `League\Flysystem\InMemory\InMemoryFilesystemAdapter` (v2/v3) and code uploads a file
- **THEN** the file content is readable from the in-memory adapter without touching disk

#### Scenario: In-memory upload on Flysystem v1
- **WHEN** a director is configured with `League\Flysystem\Memory\MemoryAdapter` (v1 via `league/flysystem-memory ^1.0`) and code uploads a file
- **THEN** the file content is readable from the in-memory adapter without touching disk, with the v1 API differences (e.g. `write()` boolean return) absorbed by the internal adapter

#### Scenario: Visibility constant mapped across versions
- **WHEN** code requests visibility `'public'`
- **THEN** the adapter writes `'public'` (v1/v2) or `Visibility::PUBLIC` (v3) to the underlying filesystem, transparently to the caller

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
