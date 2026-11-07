<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Simple file logger.
 *
 * Writes to storage/logs/app-YYYY-MM-DD.log (one file per day).
 *
 * Usage:
 *   Logger::error('Failed to create project', ['project' => $title]);
 *   Logger::exception($e);                       // with stack trace + previous exceptions
 *   Logger::warning('Session cleared');
 *
 * Optional .env settings:
 *   LOG_LEVEL=debug            debug | info | warning | error  (default: debug)
 *   LOG_RETENTION_DAYS=30      log files older than this are deleted
 *   LOG_TIMEZONE=Europe/Amsterdam
 *
 * The logger itself never throws: if writing fails, the application
 * simply carries on.
 */
final class Logger
{
    private const LEVELS = [
        'debug'   => 100,
        'info'    => 200,
        'warning' => 300,
        'error'   => 400,
    ];

    /** Maximum number of stack trace lines per exception in the log file. */
    private const MAX_TRACE_FRAMES = 15;

    public static function debug(string $message, array $context = []): void
    {
        self::write('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    /**
     * Logs an exception including file/line, stack trace and any previous
     * exceptions (e.g. the PDOException behind a RuntimeException).
     */
    public static function exception(Throwable $e, string $message = 'Unhandled exception', array $context = []): void
    {
        $details = [];
        $current = $e;
        $depth   = 0;

        while ($current !== null && $depth < 5) {
            $details[] = ($depth === 0 ? '' : 'Caused by: ')
                . get_class($current) . ': ' . $current->getMessage()
                . ' (code ' . $current->getCode() . ')'
                . ' in ' . self::shortPath($current->getFile()) . ':' . $current->getLine();

            foreach (array_slice($current->getTrace(), 0, self::MAX_TRACE_FRAMES) as $i => $frame) {
                $details[] = '    #' . $i . ' '
                    . (isset($frame['file']) ? self::shortPath($frame['file']) . '(' . ($frame['line'] ?? '?') . ')' : '[internal]')
                    . ': ' . ($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function'] . '()';
            }

            $current = $current->getPrevious();
            $depth++;
        }

        // Do not log function arguments: they can contain passwords.
        self::write('error', $message, $context, $details);
    }

    public static function logDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/logs';
    }

    // -----------------------------------------------------------------------

    private static function write(string $level, string $message, array $context = [], array $extraLines = []): void
    {
        try {
            if (self::LEVELS[$level] < self::minLevel()) {
                return;
            }

            $context = array_merge(self::requestContext(), $context);
            $json    = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

            $entry = sprintf(
                "[%s] %s: %s %s\n",
                self::now()->format('Y-m-d H:i:s'),
                strtoupper($level),
                str_replace(["\r", "\n"], ' ', $message),
                $json === false ? '{}' : $json
            );
            foreach ($extraLines as $line) {
                $entry .= str_replace(["\r", "\n"], ' ', $line) . "\n";
            }

            self::append('app', $entry);
        } catch (Throwable) {
            // Logging must never crash the application.
        }
    }

    /**
     * Current time in the log time zone (LOG_TIMEZONE).
     */
    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::setting('LOG_TIMEZONE', 'Europe/Amsterdam')));
    }

    /**
     * Appends text to storage/logs/<channel>-YYYY-MM-DD.log.
     * Also used by Activity (channel 'activity').
     * Never throws.
     */
    public static function append(string $channel, string $text): void
    {
        try {
            $dir = self::logDir();
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                return;
            }

            $file      = $dir . '/' . $channel . '-' . self::now()->format('Y-m-d') . '.log';
            $isNewFile = !is_file($file);

            file_put_contents($file, $text, FILE_APPEND | LOCK_EX);

            // Once a day (on the first entry), clean up old logs.
            if ($isNewFile) {
                self::prune($dir);
            }
        } catch (Throwable) {
            // Logging must never crash the application.
        }
    }

    private static function requestContext(): array
    {
        $context = [];

        if (PHP_SAPI !== 'cli') {
            $context['method'] = $_SERVER['REQUEST_METHOD'] ?? null;
            $context['uri']    = $_SERVER['REQUEST_URI'] ?? null;
            $context['ip']     = $_SERVER['REMOTE_ADDR'] ?? null;
        }

        $context['user'] = $_SESSION['user_id'] ?? null;

        return $context;
    }

    private static function prune(string $dir): void
    {
        $days   = max(1, (int) self::setting('LOG_RETENTION_DAYS', '30'));
        $cutoff = time() - ($days * 86400);

        foreach (glob($dir . '/*-????-??-??.log') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    private static function minLevel(): int
    {
        return self::LEVELS[strtolower(self::setting('LOG_LEVEL', 'debug'))] ?? self::LEVELS['debug'];
    }

    private static function setting(string $key, string $default): string
    {
        return function_exists('env') ? (string) env($key, $default) : $default;
    }

    /** Shows paths relative to the project folder so logs stay readable. */
    private static function shortPath(string $path): string
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 2)) . '/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
