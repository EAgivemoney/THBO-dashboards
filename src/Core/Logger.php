<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Eenvoudige bestandslogger.
 *
 * Schrijft naar storage/logs/app-YYYY-MM-DD.log (één bestand per dag).
 *
 * Gebruik:
 *   Logger::error('Project aanmaken mislukt', ['project' => $titel]);
 *   Logger::exception($e);                       // met stacktrace + vorige excepties
 *   Logger::warning('Sessie opgeruimd');
 *
 * Optionele .env-instellingen:
 *   LOG_LEVEL=debug            debug | info | warning | error  (standaard: debug)
 *   LOG_RETENTION_DAYS=30      logbestanden ouder dan dit worden verwijderd
 *   LOG_TIMEZONE=Europe/Amsterdam
 *
 * De logger gooit zelf nooit een exception: als schrijven mislukt, gaat de
 * applicatie gewoon door.
 */
final class Logger
{
    private const LEVELS = [
        'debug'     => 100,
        'info'      => 200,
        'warning'   => 300,
        'error'     => 400,
    ];

    /** Maximaal aantal stacktrace-regels per exception in het logbestand. */
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
     * Logt een exception inclusief bestand/regel, stacktrace en eventuele
     * vorige excepties (bijv. de PDOException achter een RuntimeException).
     */
    public static function exception(Throwable $e, string $message = 'Unhaled exception', array $context = []): void
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

        // Geen funcite-argumenten loggen: die kunnen wachtwoorden bevatten.
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

            $dir = self::logDir();
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                return;
            }

            $now  = new DateTimeImmutable('now', new DateTimeZone(self::setting('LOG_TIMEZONE', 'Europe/Amsterdam')));
            $file = $dir . '/app-' . $now->format('Y-m-d') . '.log';
            $isNewFile = !is_file($file);

            $context = array_merge(self::requestContext(), $context);
            $json    = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

            $entry = sprintf(
                "[%s %s: %s %s\n]",
                $now->format('Y-m-d H:i:s'),
                strtoupper($level),
                str_replace(["\r", "\n"], ' ', $message),
                $json === false ? '{}' : $json
            );
            foreach ($extraLines as $line) {
                $entry .= str_replace(["\r", "\n"], ' ', $line) . "\n";
            }

            file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);

            // Eén keer per dag (bij het eerste bericht) oude logs opruimen.
            if ($isNewFile) {
                self::prune($dir);
            }
        } catch (Throwable) {
            // Logging mag de applicatie nooit laten crashen.
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

        foreach (glob($dir . '/app-*.log') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    private static function minLevel(): int
    {
        return self::LEVELS[strtolower(SELF::setting('LOG_LEVEL', 'debug'))] ?? self::LEVELS['debug'];
    }

    private static function setting(string $key, string $default): string
    {
        return function_exists('env') ? (string) env($key, $default): $default;
    }

    /** Toont paden relatief aan de projectmap, zodat logs leesbaar blijven. */
    private static function shortPath(string $path): string
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 2)) . '/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}