<?php

namespace App\Libraries;

/**
 * Reads long options for spark commands straight from $_SERVER['argv'].
 *
 * CodeIgniter's CLI parser only understands "--name value": "--name=value"
 * becomes an option literally named "name=value", and option values never
 * reach a command's $params. This reader supports all three forms:
 *   --name=value   --name value   --flag (returns '1')
 */
final class CliOptions
{
    /**
     * @param list<string>|null $argv Defaults to $_SERVER['argv'].
     */
    public static function get(string $name, ?array $argv = null): ?string
    {
        $argv ??= $_SERVER['argv'] ?? [];
        $needle = '--' . $name;
        $count  = count($argv);

        for ($i = 0; $i < $count; $i++) {
            $arg = (string) $argv[$i];

            if (str_starts_with($arg, $needle . '=')) {
                return substr($arg, strlen($needle) + 1);
            }

            if ($arg === $needle) {
                $next = $argv[$i + 1] ?? null;

                // Bare flag (last argument, or followed by another option)
                if ($next === null || str_starts_with((string) $next, '--')) {
                    return '1';
                }

                return (string) $next;
            }
        }

        return null;
    }

    /**
     * @param list<string>|null $argv Defaults to $_SERVER['argv'].
     */
    public static function has(string $name, ?array $argv = null): bool
    {
        return self::get($name, $argv) !== null;
    }

    /**
     * True for a real calendar date in YYYY-MM-DD form (rejects e.g. 2026-13-45).
     */
    public static function isDate(string $value): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $d !== false && $d->format('Y-m-d') === $value;
    }
}
