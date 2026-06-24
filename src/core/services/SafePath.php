<?php
namespace Core\Services;

class SafePath
{
    public static function normalize(string $path): string
    {
        // Ganti backslash dengan forward slash
        $path = str_replace('\\', '/', $path);
        
        // Hapus null byte
        $path = str_replace("\0", '', $path);

        $parts = array_filter(explode('/', $path), 'strlen');
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        
        return implode('/', $absolutes);
    }

    /**
     * Reject path input berbahaya sebelum dinormalisasi.
     */
    public static function validateRelativeInput(string $path): array
    {
        if ($path === '') {
            return [false, 'Path kosong.'];
        }

        if (strpos($path, "\0") !== false) {
            return [false, 'Path mengandung null byte.'];
        }

        $normalizedSlash = str_replace('\\', '/', $path);
        if (str_starts_with($normalizedSlash, '/') || preg_match('/^[A-Za-z]:/', $normalizedSlash)) {
            return [false, 'Path absolute tidak diperbolehkan.'];
        }

        $segments = explode('/', $normalizedSlash);
        if (in_array('..', $segments, true)) {
            return [false, 'Path traversal tidak diperbolehkan.'];
        }

        if (strlen($normalizedSlash) > 255) {
            return [false, 'Path terlalu panjang.'];
        }

        if (substr_count(rtrim($normalizedSlash, '/'), '/') > 10) {
            return [false, 'Folder depth maksimal 10.'];
        }

        return [true, ''];
    }

    /**
     * Pastikan hasil path tetap di dalam base path.
     */
    public static function toProjectPath(string $basePath, string $relativePath): array
    {
        [$valid, $message] = self::validateRelativeInput($relativePath);
        if (!$valid) {
            return [false, $message, '', ''];
        }

        $safeRelative = self::normalize($relativePath);
        $realBase = realpath($basePath);
        if (!$realBase) {
            return [false, 'Project root tidak ditemukan.', '', ''];
        }

        $fullPath = rtrim($realBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeRelative);
        $baseCompare = strtolower(rtrim($realBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        $fullCompare = strtolower($fullPath);

        if (!str_starts_with($fullCompare, $baseCompare)) {
            return [false, 'Path keluar dari project root.', '', ''];
        }

        return [true, '', $safeRelative, $fullPath];
    }
}

