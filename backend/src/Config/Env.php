<?php

declare(strict_types=1);

namespace App\Config;

final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if ($name === '' || getenv($name) !== false) {
                continue;
            }

            $value = trim($value, "\"'");
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }

    public static function get(string $name, ?string $default = null): ?string
    {
        $value = getenv($name);
        return $value === false ? $default : $value;
    }

    public static function bool(string $name, bool $default = false): bool
    {
        $value = self::get($name);
        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
