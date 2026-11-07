<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Activity log: records WHO did WHAT (log in, task moved, ...).
 * 
 * Writes one JSON line per event to storage/logs/activity-YYYY-MM-DD.log.
 * The file is shown in the admin panel (Admin → Logs).
 * 
 * Usage:
 *   Activity::log('task.created', ['task_id' => 5, 'title' => 'Foo']);
 * 
 * The logged-in user is taken from the session automatically. NEVER pass
 * passwords or other secrets as data.
 */
final class Activity
{
    /**
     * @param array<string,mixed> $data     Extra data (scalars/small arrays only)
     * @param ?int                $userId   Overrides the session user (e.g. when registering)
     * @param ?string             $userName Same, for the name
     */
    public static function log(string $action, array $data = [], ?int $userId = null, ?string $userName = null): void
    {
        try {
            $entry = [
                'time'    => Logger::now()->format('Y-m-d H:i:s'),
                'user_id' => $userId ?? ($_SESSION['user_id'] ?? null),
                'user'    => $userName ?? ($_SESSION['user_name'] ?? null),
                'action'  => $action,
                'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
                'data'    => $data,
            ];

            $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
            if ($json !== false) {
                Logger::append('activity', $json . "\n");
            }
        } catch (Throwable) {
            // Logging must never crash the application.
        }
    }

    /** Display name of an action code, from resources/lang (falls back to the code itself). */
    public static function label(string $action): string
    {
        $key = 'activity.actions.' . $action;

        return Lang::has($key) ? Lang::get($key) : $action;
    }

    /**
     * Category (the part before the dot) => display name, for the filter.
     * 
     * @return array<string,string>
     */
    public static function categories(): array
    {
        $names = [];
        foreach (['auth', 'task', 'project', 'profile', 'admin'] as $category) {
            $names[$category] = Lang::get('activity.categories.' . $category);
        }

        return $names;
    }

    /** Truncates long user input (titles etc.) before it goes into the log. */
    public static function short(?string $text, int $max = 80): string
    {
        $text = (string) $text;
        return function_exists('mb_substr') ? mb_substr($text, 0, $max) : substr($text, 0, $max);
    }
}