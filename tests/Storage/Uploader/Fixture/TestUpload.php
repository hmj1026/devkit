<?php

namespace Devkit\Tests\Storage\Uploader\Fixture;

use SplFileInfo;

/**
 * SplFileInfo subclass that records the original client filename
 * the test wants the director to see. Mimics the Symfony/Laravel
 * UploadedFile API surface AbstractDirector probes via
 * method_exists() — just enough to exercise that code path
 * without dragging in Symfony as a dev dependency.
 */
class TestUpload extends SplFileInfo
{
    /** @var string */
    private $clientOriginalName;

    /** @var string */
    private $clientMimeType;

    public function __construct($realPath, $clientOriginalName, $clientMimeType)
    {
        parent::__construct($realPath);
        $this->clientOriginalName = (string) $clientOriginalName;
        $this->clientMimeType = (string) $clientMimeType;
    }

    public function getClientOriginalName()
    {
        return $this->clientOriginalName;
    }

    public function getMimeType()
    {
        return $this->clientMimeType;
    }
}
