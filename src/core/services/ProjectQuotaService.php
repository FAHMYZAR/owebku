<?php
namespace Core\Services;

class ProjectQuotaService
{
    public const ZIP_MAX_BYTES = 100 * 1024 * 1024;
    public const ZIP_MAX_UNCOMPRESSED_BYTES = 100 * 1024 * 1024;
    public const ZIP_MAX_SINGLE_FILE_BYTES = 20 * 1024 * 1024;
    public const ZIP_MAX_FILES = 1000;
    public const ZIP_MAX_DEPTH = 10;
    public const ZIP_MAX_PATH_LENGTH = 255;

    public function getDirectorySize(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
