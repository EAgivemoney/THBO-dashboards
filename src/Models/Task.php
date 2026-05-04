<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Task model — werkt tegen de tabellen `Taak`, `TaakStatus` en `Project`.
 * 
 * StatusId-mapping (zie sql/database.sql):
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
     * Geeft alle actieve taken van een gebruiker terug, gegroepeerd per status.
     * 
     * @return array{1:array,2:array,3:array}
     */
    public static function boardForUser(int $userId): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT  t.Id, t.ProjectId, t.StatusId, t.Titel, t.Beschrijving,
                     t.Prioriteit, t.Deadline, t.AangemaaktOp,
                     p.Titel AS ProjectTitel,
                     s.Naam AS StatusNaam
            FROM     Taak t
            JOIN     Project p     ON p.Id = t.ProjectId
            JOIN     TaakStatus s  ON s.Id = t.StatusId
            WHERE    p.GebruikerId = :uid
              AND    t.IsActief    = 1
              AND    p.IsActief    = 1
            ORDER BY t.Prioriteit ASC, t.Deadline IS NULL, t.Deadline ASC, t.Id DESC'
        );
        $stmt->execute([':uid => $userId']);

        $board = [
            self::STATUS_TODO  => [],
            self::STATUS_DOING => [],
            self::STATUS_DONE  => [],
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $statusId = (int) $row['StatusId'];
            if (isset($board[$statusId])) {
                $board[$statusId][] = $row;
            }
        }

        return $board;
    }

    public static function create(int $projectId, string $titel, ?string $beschrijving, int $prioriteit, ?string $deadline): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO Taak (ProjectId, StatusId, Titel, Beschrijving, Prioriteit, Deadline)
             VALUES           (:pid, :status, :titel, :beschr, :prio, :deadline)'
        );
        $stmt->execute([
            ':pid'      => $projectId,
            ':status'   => self::STATUS_TODO,
            ':titel'    => $titel,
            ':beschr'   => $beschrijving ?: null,
            ':prio'     => $prioriteit,
            ':deadline' => $deadline ?: null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Verplaatst een taak naar een andere status — wordt aangeroepen vanuit
     * de drag & drop handler in public/js/app.js.
     */
    public static function updateStatus(int $taskId, int $statusId, int $userId): bool
    {
        if (in_array($statusId, [self::STATUS_TODO, self::STATUS_DOING, self::STATUS_DONE], true)) {
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
             JOIN   Project P ON p.Id = t.ProjectId
             SET    t.IsActief = 0
             WHERE  t.Id = :tid
               AND  p.GebruikerId = :uid'
        );
        $stmt->execute([':tid' => $taskId, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Helper: aale projecten voor een gebruiker (voor het project-dropdown).
     */
    public static function projectsForUser(int $userId): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT Id, Titel
             FROM   Project
             WHERE  GebruikerId = :uid AND IsActief = 1
             ORDER BY Titel ASC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Snelle stub voor wanneer een gebruiker nog geen project heeft —
     * gebruikt de stored procedure SP_ProjectAanmaken uit sql/database.sql
     */
    public static function ensureDefaultProject(int $userId): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT Id FROM Project WHERE GebruikerId = :uid AND IsActief = 1 LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return (int) $row['Id'];
        }

        $call = $pdo->prepare('CALL SP_ProjectAanmaken(:uid, :titel, :beschr)');
        $call->execute([
            ':uid'    => $userId,
            ':titel'  => 'Mijn eerste project',
            ':beschr' => 'Standaardproject — automatisch aangemaakt',
        ]);
        $call->closeCursor();

        return (int) $pdo->lastInsertId();
    }
}