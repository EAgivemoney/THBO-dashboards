<?php
/**
 * Database configuratie
 * 
 * Leest de waardes uit .env en geeft een associatieve array terug
 * die door src/Core/Database.php gebruikt wordt om de PDO connectie op te zetten.
 */

if (!function_exists('thbo_load_env')) {
    function thbo_load_env(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));

            // Strip eventuele quotes rondom de waarde
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name]    = $value;
                $_SERVER[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return $value;
    }
}

thbo_load_env(dirname(__DIR__) . '/.env');

return [
    'host'      => env('DB_HOST', '127.0.0.1'),
    'port'      => (int) env('DB_PORT', 3306),
    'database'  => env('DB_DATABASE', 'thbodb'),
    'username'  => env('DB_USERNAME', 'root'),
    'password'  => env('DB_PASSWORD', ''),
    'charset'   => 'utf8mb4'
];
