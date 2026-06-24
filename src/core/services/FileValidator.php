<?php
namespace Core\Services;

class FileValidator
{
    private static array $allowedExtensions = [
        'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg',
        'css', 'js', 'json',
        'html', 'htm', 'txt', 'md',
        'woff', 'woff2', 'ttf', 'otf', 'eot',
        'mp3', 'mp4', 'webm', 'ogg', 'wav',
        'ico', 'avif', 'pdf'
    ];
    
    private static array $blockedExtensions = [
        'php', 'phtml', 'phar',
        'exe', 'dll',
        'sh', 'bash', 'bat', 'cmd', 'ps1',
        'py', 'rb', 'pl', 'cgi',
        'env', 'htaccess', 'user.ini',
        'sql',
        'zip', 'rar', '7z', 'tar', 'gz'
    ];

    public static function validateZipEntryPath(string $path): array
    {
        // 1. Cek null byte
        if (strpos($path, "\0") !== false) {
            return [false, 'Path mengandung null byte.'];
        }

        // 2. Cek absolute path
        if (str_starts_with($path, '/') || (strlen($path) > 1 && $path[1] === ':')) {
            return [false, 'Path absolute tidak diperbolehkan.'];
        }

        // 3. Cek traversal ../ atau ..\
        if (strpos($path, '../') !== false || strpos($path, '..\\') !== false) {
            return [false, 'Path directory traversal tidak diperbolehkan.'];
        }

        // 4. Folder depth maksimal 10
        $depth = substr_count(rtrim(str_replace('\\', '/', $path), '/'), '/');
        if ($depth > 10) {
            return [false, 'Kedalaman folder maksimal 10 tingkat.'];
        }

        // 5. Path tidak boleh terlalu panjang
        if (strlen($path) > 255) {
            return [false, 'Nama path terlalu panjang (maksimal 255 karakter).'];
        }

        return [true, ''];
    }

    /**
     * Cek ekstensi file
     */
    public static function validateExtension(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, self::$blockedExtensions, true)) {
            return false;
        }

        return in_array($ext, self::$allowedExtensions, true);
    }

    /**
     * Validasi konten file (Magic Number untuk Image, Text-check untuk CSS/JS)
     */
    public static function validateFileContent(string $filePath): array
    {
        if (!file_exists($filePath) || is_dir($filePath)) {
            return [false, 'File tidak ditemukan untuk divalidasi.'];
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return [false, 'Gagal membaca file.'];
        }

        $header = fread($handle, 512);
        fclose($handle);

        // Reject nested archive by magic signature, not only extension.
        $hexHeader = bin2hex($header);
        if (
            str_starts_with($hexHeader, '504b0304') || // ZIP
            str_starts_with($hexHeader, '526172211a0700') || // RAR4
            str_starts_with($hexHeader, '526172211a070100') || // RAR5
            str_starts_with($hexHeader, '377abcaf271c') || // 7z
            str_starts_with($hexHeader, '1f8b') || // GZ
            substr($header, 257, 5) === 'ustar' // TAR
        ) {
            return [false, 'Nested archive terdeteksi dari magic signature.'];
        }

        // 1. Validasi Image Magic Number
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            $hex = bin2hex($header);

            if ($ext === 'png') {
                if (!str_starts_with($hex, '89504e470d0a1a0a')) {
                    return [false, 'Header file PNG tidak valid (salah magic number).'];
                }
            } elseif (in_array($ext, ['jpg', 'jpeg'], true)) {
                if (!str_starts_with($hex, 'ffd8ff')) {
                    return [false, 'Header file JPEG tidak valid (salah magic number).'];
                }
            } elseif ($ext === 'gif') {
                if (!str_starts_with($hex, '474946383761') && !str_starts_with($hex, '474946383961')) {
                    return [false, 'Header file GIF tidak valid (salah magic number).'];
                }
            } elseif ($ext === 'webp') {
                if (strlen($header) < 12 || substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WEBP') {
                    return [false, 'Header file WEBP tidak valid (salah magic number).'];
                }
            }
        }

        // 2. Validasi text content untuk CSS dan JS (tidak boleh binary)
        if (in_array($ext, ['css', 'js'], true)) {
            $content = file_get_contents($filePath);
            if ($content === false) {
                return [false, 'Gagal membaca file CSS/JS.'];
            }

            if (strpos($content, "\0") !== false) {
                return [false, 'File CSS/JS terdeteksi mengandung null byte (kemungkinan binary).'];
            }

            $len = strlen($content);
            if ($len > 0) {
                $sampleLength = min($len, 4096);
                $controlCount = 0;
                for ($i = 0; $i < $sampleLength; $i++) {
                    $c = ord($content[$i]);
                    // Tolak control chars kecuali tab, LF, CR. Byte >= 0x80 tetap boleh untuk UTF-8/Latin-1.
                    if ($c < 9 || ($c > 10 && $c < 13) || ($c > 13 && $c < 32)) {
                        $controlCount++;
                    }
                }
                if ($controlCount / $sampleLength > 0.02) {
                    return [false, 'File CSS/JS terdeteksi merupakan file binary (bukan teks).'];
                }
            }
        }

        return [true, ''];
    }

    private static function isUtf8(string $string): bool
    {
        return preg_match('%^(?:
              [\x09\x0A\x0D\x20-\x7E]            # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
        )*$%xs', $string) === 1;
    }
}
