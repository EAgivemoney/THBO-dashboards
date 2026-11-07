<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Reads and filters the log files in storage/logs/ for the admin panel.
 *
 * Security: a file name from user input is never used. The date has to match
 * exactly an existing file that we find in the log folder ourselves (see
 * dates()), and the type comes from a fixed list.
 */
final class LogReader
{
    /** Available log types and levels (the display names live in resources/lang). */
    private const TYPE_KEYS  = ['activity', 'app'];
    private const LEVEL_KEYS = ['error', 'warning', 'info', 'debug'];

    public const PER_PAGE = 50;

    /** Large files: only read the last X bytes. */
    private const MAX_BYTES = 3 * 1024 * 1024;

    /**
     * Data keys written by older activity entries (before the English rename)
     * => current keys. Keeps old log lines readable.
     */
    private const LEGACY_DATA_KEYS = [
        'titel'             => 'title',
        'reden'             => 'reason',
        'naar'              => 'to',
        'doel_gebruiker_id' => 'target_user_id',
        'nieuwe_rol'        => 'new_role',
        'actief'            => 'active',
        'projecten'         => 'projects',
        'taken'             => 'tasks',
        'overgeslagen'      => 'skipped',
    ];

    /** Role values written by older entries => current role names. */
    private const LEGACY_ROLE_VALUES = [
        'gebruiker' => 'user',
        'eigenaar'  => 'owner',
    ];

    /**
     * type => display name
     *
     * @return array<string,string>
     */
    public static function types(): array
    {
        $names = [];
        foreach (self::TYPE_KEYS as $type) {
            $names[$type] = Lang::get('logs.types.' . $type);
        }

        return $names;
    }

    /**
     * level => display name
     *
     * @return array<string,string>
     */
    public static function levels(): array
    {
        $names = [];
        foreach (self::LEVEL_KEYS as $level) {
            $names[$level] = Lang::get('logs.levels.' . $level);
        }

        return $names;
    }

    /**
     * Available dates (Y-m-d) for a type, newest first.
     *
     * @return string[]
     */
    public static function dates(string $type): array
    {
        if (!in_array($type, self::TYPE_KEYS, true)) {
            return [];
        }

        $dates = [];
        foreach (glob(Logger::logDir() . '/' . $type . '-????-??-??.log') ?: [] as $file) {
            if (preg_match('/-(\d{4}-\d{2}-\d{2})\.log$/', $file, $m)) {
                $dates[] = $m[1];
            }
        }
        rsort($dates);

        return $dates;
    }

    /**
     * @return array{entries:array, total:int, page:int, pages:int, counts:array<string,int>, truncated:bool, size:int}|null
     *         null if the file does not exist
     */
    public static function read(string $type, string $date, string $filter, string $query, int $page): ?array
    {
        if (!in_array($date, self::dates($type), true)) {
            return null;
        }

        $path = Logger::logDir() . '/' . $type . '-' . $date . '.log';
        $size = (int) @filesize($path);
        $fh   = @fopen($path, 'rb');
        if ($fh === false) {
            return null;
        }

        $truncated = false;
        if ($size > self::MAX_BYTES) {
            fseek($fh, -self::MAX_BYTES, SEEK_END);
            fgets($fh); // skip the first (probably partial) line
            $truncated = true;
        }
        $content = (string) stream_get_contents($fh);
        fclose($fh);

        $lines   = preg_split('/\r?\n/', $content) ?: [];
        $entries = $type === 'activity' ? self::parseActivity($lines) : self::parseApp($lines);

        // Counts per level/category over the whole file (before filtering)
        $counts = [];
        foreach ($entries as $e) {
            $key = $type === 'activity' ? $e['category'] : $e['level'];
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        // Filters
        $entries = array_values(array_filter($entries, function (array $e) use ($type, $filter, $query): bool {
            $key = $type === 'activity' ? $e['category'] : $e['level'];
            if ($filter !== '' && $key !== $filter) {
                return false;
            }
            return $query === '' || self::contains($e['haystack'], $query);
        }));

        // Newest first
        $entries = array_reverse($entries);

        $total = count($entries);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min(max(1, $page), $pages);

        return [
            'entries'   => array_slice($entries, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            'total'     => $total,
            'page'      => $page,
            'pages'     => $pages,
            'counts'    => $counts,
            'truncated' => $truncated,
            'size'      => $size,
        ];
    }

    // -----------------------------------------------------------------------

    /**
     * activity file: one JSON object per line.
     */
    private static function parseActivity(array $lines): array
    {
        $entries = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = json_decode($line, true);
            if (!is_array($row) || !isset($row['action'])) {
                continue;
            }

            $action = (string) $row['action'];
            $data   = self::normalizeData(is_array($row['data'] ?? null) ? $row['data'] : []);

            $entries[] = [
                'time'     => (string) ($row['time'] ?? ''),
                'user_id'  => $row['user_id'] ?? null,
                'user'     => $row['user'] ?? null,
                'action'   => $action,
                'label'    => Activity::label($action),
                'category' => explode('.', $action, 2)[0],
                'ip'       => $row['ip'] ?? null,
                'data'     => $data,
                'haystack' => implode(' ', [
                    Activity::label($action), $action, (string) ($row['user'] ?? ''),
                    (string) ($row['ip'] ?? ''), json_encode($data, JSON_UNESCAPED_UNICODE),
                ]),
            ];
        }

        return $entries;
    }

    /**
     * Renames data keys (and role values) written by older entries to the
     * current English ones.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $key = self::LEGACY_DATA_KEYS[$key] ?? $key;

            if ($key === 'new_role' && is_string($value)) {
                $value = self::LEGACY_ROLE_VALUES[strtolower($value)] ?? $value;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * app file (Logger): "[time] LEVEL: message {json context}" followed by
     * any stack trace lines that belong to the same entry.
     */
    private static function parseApp(array $lines): array
    {
        $entries = [];
        $current = null;

        $flush = function () use (&$entries, &$current): void {
            if ($current === null) {
                return;
            }
            $current['details']  = implode("\n", $current['detail_lines']);
            $current['haystack'] = $current['message'] . ' ' . $current['details'] . ' '
                . json_encode($current['context'], JSON_UNESCAPED_UNICODE);
            unset($current['detail_lines']);
            $entries[] = $current;
            $current   = null;
        };

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (DEBUG|INFO|WARNING|ERROR): (.*)$/', $line, $m)) {
                $flush();

                [$message, $context] = self::splitContext($m[3]);
                $current = [
                    'time'         => $m[1],
                    'level'        => strtolower($m[2]),
                    'message'      => $message,
                    'context'      => $context,
                    'detail_lines' => [],
                ];
            } elseif ($current !== null && trim($line) !== '') {
                $current['detail_lines'][] = $line;
            }
        }
        $flush();

        return $entries;
    }

    /**
     * Splits "message {"method":...}" into message + context array.
     * The Logger always puts the context at the end and makes it start with
     * "method" (web) or "user" (cli).
     *
     * @return array{0:string,1:array}
     */
    private static function splitContext(string $rest): array
    {
        $pos = null;
        foreach ([' {"method":', ' {"user":'] as $marker) {
            $p = strpos($rest, $marker);
            if ($p !== false && ($pos === null || $p < $pos)) {
                $pos = $p;
            }
        }

        if ($pos !== null) {
            $decoded = json_decode(substr($rest, $pos + 1), true);
            if (is_array($decoded)) {
                return [substr($rest, 0, $pos), $decoded];
            }
        }

        return [$rest, []];
    }

    private static function contains(string $haystack, string $needle): bool
    {
        return function_exists('mb_stripos')
            ? mb_stripos($haystack, $needle) !== false
            : stripos($haystack, $needle) !== false;
    }
}
