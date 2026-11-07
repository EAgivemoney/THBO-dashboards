<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Lang;
use PDO;
use PDOException;

/**
 * Task model — works against the tables `Taak`, `TaakStatus` and `Project`.
 *
 * Dutch column names are mapped to English keys with an alias
 * (Titel → title, Beschrijving → description, Prioriteit → priority, ...).
 *
 * StatusId mapping (see sql/database.sql):
 *   1 = todo
 *   2 = doing
 *   3 = done
 */
class Task
{
    public const STATUS_TODO = 1;
    public const STATUS_DOING = 2;
    public const STATUS_DONE = 3;

    /**
     * Returns all active tasks of a user, grouped by status.
     *
     * @return array{1:array,2:array,3:array}
     */
    public static function boardForUser(int $userId): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT  t.Id           AS id,
                     t.ProjectId    AS project_id,
                     t.StatusId     AS status_id,
                     t.Titel        AS title,
                     t.Beschrijving AS description,
                     t.Prioriteit   AS priority,
                     t.Deadline     AS deadline,
                     t.AangemaaktOp AS created_at,
                     p.Titel        AS project_title,
                     s.Naam         AS status_name
            FROM     Taak t
            JOIN     Project p     ON p.Id = t.ProjectId
            JOIN     TaakStatus s  ON s.Id = t.StatusId
            WHERE    p.GebruikerId = :uid
              AND    t.IsActief    = 1
              AND    p.IsActief    = 1
            ORDER BY t.Prioriteit ASC, t.Deadline IS NULL, t.Deadline ASC, t.Id DESC'
        );
        $stmt->execute([':uid' => $userId]);

        $board = [
            self::STATUS_TODO  => [],
            self::STATUS_DOING => [],
            self::STATUS_DONE  => [],
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $statusId = (int) $row['status_id'];
            if (isset($board[$statusId])) {
                $board[$statusId][] = $row;
            }
        }

        return $board;
    }

    public static function create(int $projectId, string $title, ?string $description, int $priority, ?string $deadline): int
    {
        $pdo    = Database::connection();
        $params = [
            ':pid'         => $projectId,
            ':status'      => self::STATUS_TODO,
            ':title'       => $title,
            ':description' => $description ?: null,
            ':priority'    => $priority,
            ':deadline'    => $deadline ?: null,
        ];

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO Taak (ProjectId, StatusId, Titel, Beschrijving, Prioriteit, Deadline)
                 VALUES           (:pid, :status, :title, :description, :priority, :deadline)'
            );
            $stmt->execute($params);

            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            if (!self::isDuplicate($e)) {
                throw $e;
            }

            // The table has UNIQUE (ProjectId, Titel). A deleted task (IsActief = 0)
            // stays in the table and blocks that name; in that case we restore it.
            $find = $pdo->prepare('SELECT Id AS id, IsActief AS is_active FROM Taak WHERE ProjectId = :pid AND Titel = :title LIMIT 1');
            $find->execute([':pid' => $projectId, ':title' => $title]);
            $existing = $find->fetch(PDO::FETCH_ASSOC);

            if (!$existing || (int) $existing['is_active'] === 1) {
                throw $e; // a genuine duplicate name: the controller shows a message
            }

            $revive = $pdo->prepare(
                'UPDATE Taak
                 SET    IsActief     = 1,
                        StatusId     = :status,
                        Beschrijving = :description,
                        Prioriteit   = :priority,
                        Deadline     = :deadline,
                        AangemaaktOp = CURRENT_TIMESTAMP(6)
                 WHERE  Id = :id'
            );
            $revive->execute([
                ':status'      => $params[':status'],
                ':description' => $params[':description'],
                ':priority'    => $params[':priority'],
                ':deadline'    => $params[':deadline'],
                ':id'          => (int) $existing['id'],
            ]);

            return (int) $existing['id'];
        }
    }

    /**
     * Is this a "Duplicate entry" error (MySQL 1062, UNIQUE constraint)?
     */
    public static function isDuplicate(PDOException $e): bool
    {
        return (int) ($e->errorInfo[1] ?? 0) === 1062;
    }

    public static function update(int $taskId, int $projectId, string $title, ?string $description, int $priority, ?string $deadline, int $userId): bool
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE Taak t
             JOIN   Project p ON p.Id = t.ProjectId
             SET    t.Titel        = :title,
                    t.Beschrijving = :description,
                    t.Prioriteit   = :priority,
                    t.Deadline     = :deadline,
                    t.ProjectId    = :pid
             WHERE  t.Id           = :tid
               AND  p.GebruikerId  = :uid'
        );
        $stmt->execute([
            ':title'       => $title,
            ':description' => $description,
            ':priority'    => $priority,
            ':deadline'    => $deadline,
            ':pid'         => $projectId,
            ':tid'         => $taskId,
            ':uid'         => $userId,
        ]);
        return true;
    }

    /**
     * Moves a task to another status — called from the drag & drop
     * handler in public/js/app.js.
     */
    public static function updateStatus(int $taskId, int $statusId, int $userId): bool
    {
        if (!in_array($statusId, [self::STATUS_TODO, self::STATUS_DOING, self::STATUS_DONE], true)) {
            return false;
        }

        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE Taak t
             JOIN   Project p ON p.Id = t.ProjectId
             SET    t.StatusId = :status
             WHERE  t.Id = :tid
               AND  p.GebruikerId = :uid'
        );
        $stmt->execute([
            ':status' => $statusId,
            ':tid'    => $taskId,
            ':uid'    => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function softDelete(int $taskId, int $userId): bool
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE Taak t
             JOIN   Project p ON p.Id = t.ProjectId
             SET    t.IsActief = 0
             WHERE  t.Id = :tid
               AND  p.GebruikerId = :uid'
        );
        $stmt->execute([':tid' => $taskId, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Helper: all projects of a user (for the project dropdown).
     *
     * @return array<int, array{id:int,title:string}>
     */
    public static function projectsForUser(int $userId): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT Id AS id, Titel AS title
             FROM   Project
             WHERE  GebruikerId = :uid AND IsActief = 1
             ORDER BY Titel ASC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Makes sure a user has at least one project — uses the stored procedure
     * SP_ProjectAanmaken from sql/database.sql. Returns the id of the first
     * project (existing or newly created).
     */
    public static function ensureDefaultProject(int $userId): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT Id AS id FROM Project WHERE GebruikerId = :uid AND IsActief = 1 LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return (int) $row['id'];
        }

        $call = $pdo->prepare('CALL SP_ProjectAanmaken(:uid, :title, :description)');
        $call->execute([
            ':uid'         => $userId,
            ':title'       => Lang::get('project.default_title'),
            ':description' => Lang::get('project.default_description'),
        ]);
        $call->closeCursor();

        return (int) $pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn();
    }

    /**
     * Returns $base, or "$base (2)", "$base (3)", ... — the first project title
     * this user has not used yet. Deleted projects keep their title (UNIQUE on
     * GebruikerId + Titel), so they have to be taken into account as well.
     */
    private static function availableProjectTitle(int $userId, string $base): string
    {
        $pdo   = Database::connection();
        $stmt  = $pdo->prepare('SELECT 1 FROM Project WHERE GebruikerId = :uid AND Titel = :title LIMIT 1');
        $title = $base;

        for ($n = 2; $n <= 100; $n++) {
            $stmt->execute([':uid' => $userId, ':title' => $title]);
            if (!$stmt->fetchColumn()) {
                return $title;
            }
            $title = $base . ' (' . $n. ')';
        }

        return $base . ' (' . bin2hex(random_bytes(3)) . ')'; // practically unreachable
    }
}
