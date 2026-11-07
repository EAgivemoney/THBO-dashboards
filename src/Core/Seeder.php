<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Project;
use App\Models\Task;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Loads sample projects and tasks from resources/seed/demo.<code>.json into
 * one user's account. Handy for testing the dashboard (many projects,
 * scrollbar, columns with tasks, deadlines).
 *
 * Safe to run repeatedly: existing projects and tasks are reused or
 * skipped, so no duplicates are created.
 */
final class Seeder
{
    /**
     * Demo file in the chosen language (resources/seed/demo.<code>.json),
     * or the Dutch file if that language has none.
     */
    public static function demoFile(): string
    {
        $dir  = dirname(__DIR__, 2) . '/resources/seed/';
        $file = $dir . 'demo.' . Lang::locale() . '.json';

        return is_file($file) ? $file : $dir . 'demo.nl.json';
    }

    /**
     * @return array{projects:int, tasks:int, skipped:int}
     * @throws RuntimeException  if the demo file is missing or invalid
     */
    public static function loadDemo(int $userId): array
    {
        $file = self::demoFile();
        $json = is_file($file) ? file_get_contents($file) : false;
        $data = $json === false ? null : json_decode($json, true);

        if (!is_array($data) || !is_array($data['projects'] ?? null)) {
            throw new RuntimeException('Demo file is missing or invalid: ' . $file);
        }

        $pdo   = Database::connection();
        $stats = ['projects' => 0, 'tasks' => 0, 'skipped' => 0];

        $find = $pdo->prepare('SELECT Id AS id, IsActief AS is_active FROM Project WHERE GebruikerId = :uid AND Titel = :title LIMIT 1');

        foreach ($data['projects'] as $project) {
            $title = trim((string) ($project['title'] ?? ''));
            $tasks = is_array($project['tasks'] ?? null) ? $project['tasks'] : [];
            if ($title === '') {
                continue;
            }

            $find->execute([':uid' => $userId, ':title' => $title]);
            $existing = $find->fetch(PDO::FETCH_ASSOC);

            if ($existing && !(int) $existing['is_active']) {
                // A deleted project keeps its name reserved (UNIQUE) and cannot be restored via
                // the stored procedure: skip it.
                $stats['skipped'] += 1 + count($tasks);
                continue;
            }

            if ($existing) {
                $projectId = (int) $existing['id'];
                $stats['skipped']++;
            } else {
                $projectId = Project::create($userId, $title, self::text($project['description'] ?? null));
                $stats['projects']++;
            }

            foreach ($tasks as $task) {
                $taskTitle = trim((string) ($task['title'] ?? ''));
                if ($taskTitle === '') {
                    continue;
                }

                try {
                    $taskId = Task::create(
                        $projectId,
                        $taskTitle,
                        self::text($task['description'] ?? null),
                        max(1, min(5, (int) ($task['priority'] ?? 3))),
                        self::deadline($task['deadline_days'] ?? null)
                    );
                } catch (PDOException $e) {
                    if (!Task::isDuplicate($e)) {
                        throw $e;
                    }
                    $stats['skipped']++;
                    continue;
                }

                $status = match ($task['status'] ?? 'todo') {
                    'doing' => Task::STATUS_DOING,
                    'done'  => Task::STATUS_DONE,
                    default => Task::STATUS_TODO,
                };
                if ($status !== Task::STATUS_TODO) {
                    Task::updateStatus($taskId, $status, $userId);
                }

                $stats['tasks']++;
            }
        }

        return $stats;
    }

    private static function text(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
    }

    /** "3" → date 3 days from now (Y-m-d); null/empty → no deadline. */
    private static function deadline(mixed $days): ?string
    {
        if ($days === null || $days === '') {
            return null;
        }
        return date('Y-m-d', strtotime(((int) $days >= 0 ? '+' : '') . (int) $days . ' days'));
    }
}
