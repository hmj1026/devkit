<?php

namespace Devkit\Storage\Uploader;

/**
 * Concrete director for generic files. Inherits the full AbstractDirector
 * pipeline as-is — present mostly so callers have a class to construct
 * when they don't need image-specific handling.
 */
class FileDirector extends AbstractDirector
{
}
