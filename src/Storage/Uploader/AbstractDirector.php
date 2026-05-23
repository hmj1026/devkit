<?php

namespace Devkit\Storage\Uploader;

use Devkit\Storage\Contract\DirectorContract;
use Devkit\Storage\Contract\FileContract;
use Devkit\Storage\Enum\PathMethodEnum;
use Devkit\Storage\Enum\VisibilityEnum;
use Devkit\Storage\Exception\FileFormatException;
use Devkit\Storage\Foundation\File;
use Devkit\Storage\Internal\FilesystemBridge;
use Psr\Http\Message\UploadedFileInterface;
use SplFileInfo;

/**
 * Validates an uploaded file, generates a deterministic storage path
 * per the configured strategy, replicates the contents across one or
 * more disks, and returns a populated FileContract.
 *
 * Concrete subclasses (FileDirector / ImageDirector) override
 * createFile() to return the appropriate Foundation type, and may
 * tighten validateMime() or generateStoredName() if needed.
 *
 * Pure PHP — no Illuminate imports. The Laravel bridge in Wave 5
 * wires this through the service container.
 */
abstract class AbstractDirector implements DirectorContract
{
    /**
     * Disk name → FilesystemBridge. The first key is treated as the
     * default for FileContract::getPath() / getUrl() when no disk is
     * passed.
     *
     * @var array<string, FilesystemBridge>
     */
    protected $disks = array();

    /**
     * PathMethodEnum value: 'md5' or 'date'.
     *
     * @var string
     */
    protected $pathMethod = PathMethodEnum::MD5;

    /**
     * @var array<string>  Allowed MIME types; empty array = allow all.
     */
    protected $allowMimeTypes = array();

    /**
     * @var int  Max accepted size in bytes; 0 = no cap.
     */
    protected $maxFileSize = 0;

    /**
     * @var string  VisibilityEnum value applied to every write.
     */
    protected $visibility = VisibilityEnum::PUBLIC;

    /**
     * Prefix added to every generated path (e.g. "uploads/users").
     * No leading slash; trailing slash optional.
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Per-disk URL prefix used by buildUrl(). Empty string → return
     * the path itself, which is correct for disks where the consumer
     * builds the URL externally (signed URLs etc.).
     *
     * @var array<string, string>
     */
    protected $urlPrefixes = array();

    /**
     * @param  array<string, FilesystemBridge|object>  $disks
     *   Map of disk name → FilesystemBridge or a raw Flysystem instance
     *   (auto-wrapped). Empty disks map throws on construction since a
     *   director without a target makes no sense.
     * @param  string  $pathMethod   PathMethodEnum value.
     * @param  array  $options       Keys: allowMimeTypes (string[]),
     *                               maxFileSize (int bytes),
     *                               visibility (VisibilityEnum value),
     *                               basePath (string),
     *                               urlPrefixes (array<string,string>).
     */
    public function __construct(array $disks, $pathMethod = PathMethodEnum::MD5, array $options = array())
    {
        if (empty($disks)) {
            throw new FileFormatException('Director requires at least one disk.');
        }

        foreach ($disks as $name => $fs) {
            $this->disks[(string) $name] = $fs instanceof FilesystemBridge
                ? $fs
                : new FilesystemBridge($fs);
        }

        $this->pathMethod = (string) $pathMethod;

        if (isset($options['allowMimeTypes']) && is_array($options['allowMimeTypes'])) {
            $this->allowMimeTypes = array_values(array_map('strtolower', $options['allowMimeTypes']));
        }
        if (isset($options['maxFileSize'])) {
            $this->maxFileSize = (int) $options['maxFileSize'];
        }
        if (isset($options['visibility'])) {
            $this->visibility = (string) $options['visibility'];
        }
        if (isset($options['basePath'])) {
            $this->basePath = trim((string) $options['basePath'], '/');
        }
        if (isset($options['urlPrefixes']) && is_array($options['urlPrefixes'])) {
            foreach ($options['urlPrefixes'] as $disk => $prefix) {
                $this->urlPrefixes[(string) $disk] = rtrim((string) $prefix, '/');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function upload($uploadedFile)
    {
        list($originalName, $mime, $size, $contents, $tmpPath) = $this->extractUploadMeta($uploadedFile);

        $this->validateSize($size);
        $this->validateMime($mime);

        $extension = $this->resolveExtension($originalName);
        $storedName = $this->generateStoredName($originalName, $extension);
        $path = $this->buildPath($storedName, $extension);

        // Spec: every disk receives the same path so getPath()/getUrl()
        // can use the path verbatim regardless of which disk the caller
        // queries — see devkit-file-uploader spec "Multi-Disk Replication".
        foreach ($this->disks as $diskName => $bridge) {
            $bridge->write($path, $contents, array('visibility' => $this->visibility));
        }

        $file = $this->createFile($originalName, $storedName, $path, $size, $mime, $tmpPath);
        $file->setExtension($extension);
        $file->setVisibility($this->visibility);

        $diskNames = array_keys($this->disks);
        $file->setDefaultDisk(reset($diskNames));

        foreach ($this->disks as $diskName => $_bridge) {
            $file->setPath($diskName, $path);
            $file->setUrl($diskName, $this->buildUrl($diskName, $path));
        }

        return $file;
    }

    /**
     * Subclass hook — return the right Foundation type. The default
     * here returns a generic File; ImageDirector overrides to extract
     * width/height into an Image.
     *
     * @param  string  $originalName
     * @param  string  $storedName
     * @param  string  $path
     * @param  int  $size
     * @param  string  $mime
     * @param  string|null  $tmpPath  Local fs path (for getimagesize etc.) — null when not available.
     * @return File
     */
    protected function createFile($originalName, $storedName, $path, $size, $mime, $tmpPath = null)
    {
        $file = new File();
        $file->setOriginalName($originalName);
        $file->setStoredName($storedName);
        $file->setSize($size);
        $file->setMimeType($mime);

        return $file;
    }

    /**
     * Default stored-name strategy: random hex tied to a microtime
     * seed, ensuring uniqueness across concurrent uploads without
     * needing any external state. Subclasses may override.
     *
     * Contract: the returned name (sans extension) MUST be at least
     * 4 characters long so {@see buildPath()} can derive the MD5
     * bucket as the first 2 + next 2 chars without re-hashing. Names
     * shorter than 4 chars get silently re-hashed by buildPath(),
     * meaning the bucket prefix will no longer match the filename.
     *
     * @param  string  $originalName
     * @param  string  $extension
     * @return string
     */
    protected function generateStoredName($originalName, $extension)
    {
        // 16 bytes of hex from a microtime+random seed; deterministic
        // shape but not predictable.
        $seed = uniqid('', true) . '|' . $originalName . '|' . mt_rand();
        $name = substr(md5($seed), 0, 16);

        return $extension === '' ? $name : ($name . '.' . $extension);
    }

    /**
     * @throws FileFormatException  When MIME is not in allowMimeTypes.
     */
    protected function validateMime($mime)
    {
        if ($this->allowMimeTypes === array()) {
            return;
        }

        $given = strtolower((string) $mime);
        if (!in_array($given, $this->allowMimeTypes, true)) {
            // Strip non-printable / control chars before reflecting the
            // value into the exception message — consumers that render
            // exception messages to HTML or log them unsafely benefit
            // from a defensive scrub here. Truncate to a sane length.
            $safe = substr(preg_replace('/[^\x20-\x7E]/', '', $given), 0, 127);
            throw new FileFormatException(
                'Uploaded file mime [' . $safe . '] is not in the allowed list: '
                . implode(', ', $this->allowMimeTypes)
            );
        }
    }

    /**
     * @throws FileFormatException  When size exceeds maxFileSize.
     */
    protected function validateSize($size)
    {
        if ($this->maxFileSize > 0 && (int) $size > $this->maxFileSize) {
            throw new FileFormatException(
                'Uploaded file size [' . $size . ' bytes] exceeds the limit of '
                . $this->maxFileSize . ' bytes.'
            );
        }
    }

    /**
     * Executable / interpretable extensions that must NEVER land on
     * disk via a client-supplied filename, because a consumer
     * inadvertently serving the storage directory through a webroot
     * would otherwise enable remote code execution.
     *
     * @var array<int, string>
     */
    protected $deniedExtensions = array(
        'php', 'php3', 'php4', 'php5', 'php7', 'phps', 'phtml', 'phar',
        'pl', 'cgi', 'py', 'rb', 'sh', 'bash', 'zsh',
        'jsp', 'jspx', 'asp', 'aspx', 'cer',
        'exe', 'dll', 'bat', 'cmd', 'com', 'msi', 'scr',
        'htaccess', 'htpasswd',
    );

    /**
     * Lowercase, sanitised extension safe to use on disk. Strips
     * dangerous executable extensions to `bin` so a client filename
     * like `evil.php` never lands as a `.php` file regardless of
     * how the consumer exposes the storage tree.
     *
     * @param  string  $originalName
     * @return string  Lowercase extension without dot; empty when none.
     */
    protected function resolveExtension($originalName)
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        if ($ext === '') {
            return '';
        }

        $lower = strtolower($ext);

        // Strip non-alphanumeric chars defensively (anything weird in
        // the extension is itself suspicious — paths with `..` etc.
        // are not allowed here).
        $lower = preg_replace('/[^a-z0-9]/', '', $lower);
        if ($lower === '') {
            return '';
        }

        if (in_array($lower, $this->deniedExtensions, true)) {
            return 'bin';
        }

        return $lower;
    }

    /**
     * Apply the configured path strategy and base prefix.
     *
     * @param  string  $storedName  Already includes the extension when known.
     * @param  string  $extension   May be unused depending on strategy.
     * @return string
     */
    protected function buildPath($storedName, $extension)
    {
        $bucket = '';
        $hashSource = pathinfo($storedName, PATHINFO_FILENAME);

        if ($this->pathMethod === PathMethodEnum::MD5) {
            $hash = strlen($hashSource) >= 4 ? $hashSource : md5($hashSource);
            $bucket = substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
        } elseif ($this->pathMethod === PathMethodEnum::DATE) {
            $bucket = date('Y/m/d');
        }

        $segments = array();
        if ($this->basePath !== '') {
            $segments[] = $this->basePath;
        }
        if ($bucket !== '') {
            $segments[] = $bucket;
        }
        $segments[] = $storedName;

        return implode('/', $segments);
    }

    /**
     * @param  string  $diskName
     * @param  string  $path
     * @return string
     */
    protected function buildUrl($diskName, $path)
    {
        if (isset($this->urlPrefixes[$diskName]) && $this->urlPrefixes[$diskName] !== '') {
            return $this->urlPrefixes[$diskName] . '/' . ltrim($path, '/');
        }

        return $path;
    }

    /**
     * Normalise the heterogeneous upload input shape into
     * (originalName, mime, size, contents, tmpPath).
     *
     * Accepts:
     *   - PSR-7 UploadedFileInterface
     *   - SplFileInfo / SplFileObject (uses basename as originalName,
     *     mime_content_type as mime when available)
     *   - Anything exposing getClientOriginalName() / getMimeType() /
     *     getSize() / getRealPath() (Laravel's UploadedFile).
     *
     * @param  mixed  $uploadedFile
     * @return array{0:string,1:string,2:int,3:string,4:string|null}
     *
     * @throws FileFormatException
     */
    protected function extractUploadMeta($uploadedFile)
    {
        if ($uploadedFile instanceof UploadedFileInterface) {
            $clientName = $uploadedFile->getClientFilename();
            $originalName = $clientName === null || $clientName === ''
                ? 'upload.bin'
                : $clientName;
            $declaredSize = (int) $uploadedFile->getSize();
            $stream = $uploadedFile->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            $contents = (string) $stream->getContents();

            // PSR-7 `getSize()` returns null when Content-Length was
            // absent (chunked transfer encoding) — cast to int gives 0,
            // which would silently bypass `validateSize()`. Use the
            // actually-read byte count as a floor.
            $size = max($declaredSize, strlen($contents));

            $clientMime = (string) $uploadedFile->getClientMediaType();
            $mime = $this->resolveTrustedMime($contents, $clientMime);

            return array($originalName, $mime, $size, $contents, null);
        }

        if (is_object($uploadedFile) && method_exists($uploadedFile, 'getClientOriginalName')) {
            $originalName = (string) $uploadedFile->getClientOriginalName();
            $clientMime = method_exists($uploadedFile, 'getMimeType')
                ? (string) $uploadedFile->getMimeType()
                : (method_exists($uploadedFile, 'getClientMimeType')
                    ? (string) $uploadedFile->getClientMimeType()
                    : '');
            $declaredSize = method_exists($uploadedFile, 'getSize') ? (int) $uploadedFile->getSize() : 0;
            $tmpPath = method_exists($uploadedFile, 'getRealPath')
                ? ($uploadedFile->getRealPath() ?: null)
                : (method_exists($uploadedFile, 'getPathname') ? $uploadedFile->getPathname() : null);
            $contents = $tmpPath !== null && is_readable($tmpPath)
                ? (string) file_get_contents($tmpPath)
                : '';

            // Same client-MIME caveat as the PSR-7 branch — frameworks
            // like Symfony/Laravel's UploadedFile expose
            // getClientMimeType() which is the unverified header value.
            // Prefer byte-level detection.
            $mime = $this->resolveTrustedMime($contents, $clientMime);

            // Mirror the PSR-7 chunked-upload defense — if a framework
            // returns size 0 / null but we did read bytes, use them.
            $size = max($declaredSize, strlen($contents));

            return array($originalName, $mime, $size, $contents, $tmpPath);
        }

        if ($uploadedFile instanceof SplFileInfo) {
            $tmpPath = $uploadedFile->getRealPath() ?: $uploadedFile->getPathname();
            $originalName = $uploadedFile->getFilename();
            $size = $uploadedFile->getSize() ?: 0;
            $mime = $this->guessMimeFromPath($tmpPath);
            $contents = $tmpPath !== '' && is_readable($tmpPath)
                ? (string) file_get_contents($tmpPath)
                : '';

            return array($originalName, $mime, (int) $size, $contents, $tmpPath);
        }

        throw new FileFormatException(
            'Unsupported upload type: ' . (is_object($uploadedFile) ? get_class($uploadedFile) : gettype($uploadedFile))
        );
    }

    /**
     * Combine byte-level MIME detection with the client-supplied
     * header. The detected value always wins when finfo returns
     * anything; the client header is a last-resort fallback only.
     *
     * @param  string  $contents
     * @param  string  $clientMime
     * @return string
     */
    protected function resolveTrustedMime($contents, $clientMime)
    {
        $detected = $this->detectMimeFromBuffer($contents);
        if ($detected !== '') {
            return $detected;
        }

        return $clientMime !== '' ? $clientMime : 'application/octet-stream';
    }

    /**
     * @param  string  $path
     * @return string
     */
    protected function guessMimeFromPath($path)
    {
        if ($path === '' || !is_readable($path)) {
            return 'application/octet-stream';
        }

        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Detect MIME by inspecting the actual byte buffer. Used for PSR-7
     * uploads where the only "client" MIME is the Content-Type header
     * the caller sent — i.e. untrusted. Returns empty string when
     * detection isn't possible (caller falls back to client header).
     *
     * @param  string  $contents
     * @return string
     */
    protected function detectMimeFromBuffer($contents)
    {
        if ($contents === '') {
            return '';
        }

        if (class_exists('\\finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = @$finfo->buffer($contents);
            // libmagic returns 'application/octet-stream' as its "I
            // don't recognise this" response (very short payloads,
            // unknown binary formats). Treat that as a non-detection
            // so the caller can fall back to the client header —
            // returning octet-stream verbatim would defeat MIME
            // allow-lists for legitimate edge-case payloads.
            if (is_string($detected) && $detected !== '' && $detected !== 'application/octet-stream') {
                return $detected;
            }
        }

        return '';
    }
}
