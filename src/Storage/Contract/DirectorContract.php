<?php

namespace Devkit\Storage\Contract;

/**
 * Director that accepts an uploaded file, validates MIME / size, generates
 * a storage path per the configured strategy, replicates across disks, and
 * returns a FileContract describing the result.
 *
 * Concrete directors (FileDirector / ImageDirector under
 * Devkit\Storage\Uploader\, Wave 4) supply file-type-specific validation
 * and naming conventions on top of this contract.
 *
 * Pure PHP — no Illuminate imports.
 */
interface DirectorContract
{
    /**
     * Upload the given file through this director's validation + storage
     * pipeline and return the resulting FileContract.
     *
     * Implementations SHOULD raise Devkit\Storage\Exception\FileFormatException
     * (or a subclass) when validation fails.
     *
     * @param  mixed  $uploadedFile  PSR-7 UploadedFileInterface, SplFileInfo, or framework-specific upload object
     * @return FileContract
     */
    public function upload($uploadedFile);
}
