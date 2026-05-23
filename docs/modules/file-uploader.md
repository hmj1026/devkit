# Devkit File Uploader

## Use Case

Use directors for MIME/size validation, path generation, visibility mapping, and multi-disk replication across Flysystem 1, 2, and 3.

## Laravel Configuration

```php
'modules' => array(
    'storage' => array('enabled' => true),
),
'disks' => array(
    'default' => 'local',
),
```

Resolve the manager through the facade or container:

```php
$file = app('devkit.file_uploader')->director('image')->upload($uploadedFile);
```

## Pure PHP Usage

```php
use Devkit\Storage\Uploader\ImageDirector;

$director = new ImageDirector($filesystem, array(
    'allow_mime_types' => array('image/png', 'image/jpeg'),
    'allow_file_size' => 2048000,
));

$file = $director->upload($upload);
```
