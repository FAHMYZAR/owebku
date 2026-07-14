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

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || preg_match('/[\x00-\x1F\x7F]/', $segment)) {
                return [false, 'Nama path tidak valid.'];
            }
            if (preg_match('/^(con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\..*)?$/i', $segment)) {
                return [false, 'Nama file sistem tidak diperbolehkan.'];
            }
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
     * Pastikan path database berada di bawah root aplikasi dan bukan symlink.
     */
    public static function validateOwnedDirectory(string $path, string $rootPath): array
    {
        $realRoot = realpath($rootPath);
        $realPath = realpath($path);
        if (!$realRoot || !$realPath || is_link($path)) {
            return [false, 'Direktori project tidak valid.'];
        }

        $root = strtolower(rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        $candidate = strtolower(rtrim($realPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        if (!str_starts_with($candidate, $root)) {
            return [false, 'Direktori project berada di luar root aplikasi.'];
        }

        return [true, ''];
    }

    /**
     * Validasi target yang mungkin belum ada dengan memeriksa parent terdekat.
     */
    public static function validateDestinationPath(string $path, string $rootPath): array
    {
        $realRoot = realpath($rootPath);
        if (!$realRoot || strpos($path, "\0") !== false) {
            return [false, 'Root atau target tidak valid.'];
        }

        $root = strtolower(rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        $candidate = strtolower(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        if (!str_starts_with($candidate, $root)) {
            return [false, 'Target berada di luar root aplikasi.'];
        }

        $parent = dirname($path);
        while (!file_exists($parent) && dirname($parent) !== $parent) {
            $parent = dirname($parent);
        }
        $realParent = realpath($parent);
        if (!$realParent || is_link($parent) || !str_starts_with(strtolower(rtrim($realParent, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR), $root)) {
            return [false, 'Parent target tidak aman.'];
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

