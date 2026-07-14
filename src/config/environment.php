<?php

declare(strict_types=1);

/**
 * Load KEY=VALUE entries from the project .env without overriding variables
 * supplied by Docker, PHP-FPM, or the operating system.
 */
function load_environment(string $file): void
{
    if (!is_readable($file)) {
        return;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ($key === '' || !preg_match('/^[A-Z_][A-Z0-9_]*$/', $key) || getenv($key) !== false) {
            continue;
        }

        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}
