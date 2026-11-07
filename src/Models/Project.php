<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Project model — works against the `Project` table.
 *
 * Contains methods to create, update, delete (soft delete) and fetch the
 * projects that belong to a specific user. The Dutch column names are mapped
 * to English keys with an alias (Titel → title, Beschrijving → description, ...).
 */
class Project
{
    /**
     * Returns all active projects for a user, including the number of tasks
     * per status and the progress (0–100) from the database function
     * FN_ProjectVoortgang.
     *
     * @return array<int, array{id:int,title:string,description:string|null,task_count:int,completed_count:int,progress:int}>
     */
    public static function forUser(int $userId): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT
                p.Id                      AS id,
                p.Titel                   AS title,
                p.Beschrijving            AS description,
                COUNT(t.Id)               AS task_count,
                SUM(t.StatusId = 3)       AS completed_count,
                FN_ProjectVoortgang(p.Id) AS progress
             FROM   Project p
             LEFT JOIN Taak t ON t.ProjectId = p.Id AND t.IsActief = 1
             WHERE  p.GebruikerId = :uid AND p.IsActief = 1
             GROUP BY p.Id, p.Titel, p.Beschrijving
             ORDER BY p.Titel ASC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Number of active (not deleted) projects of a user.
     */
    public static function countActiveForUser(int $userId): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Project WHERE GebruikerId = :uid AND IsActief = 1');
        $stmt->execute([':uid' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Creates a new project through the stored procedure SP_ProjectAanmaken.
     * Returns the id of the new project.
     */
    public static function create(int $userId, string $title, ?string $description): int
    {
        $pdo  = Database::connection();
        $call = $pdo->prepare('CALL SP_ProjectAanmaken(:uid, :title, :description)');
        $call->execute([
            ':uid'         => $userId,
            ':title'       => $title,
            ':description' => $description ?: null,
        ]);
        $call->closeCursor();

        return (int) $pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn();
    }

    /**
     * Updates an existing project.
     * Also checks that the project belongs to the given user.
     */
    public static function update(int $projectId, string $title, ?string $description, int $userId): bool
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE Project
             SET    Titel        = :title,
                    Beschrijving = :description
             WHERE  Id           = :id
               AND  GebruikerId  = :uid
               AND  IsActief     = 1'
        );
        $stmt->execute([
            ':title'       => $title,
            ':description' => $description ?: null,
            ':id'          => $projectId,
            ':uid'         => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Soft delete: sets IsActief to 0.
     * Also checks ownership.
     */
    public static function softDelete(int $projectId, int $userId): bool
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE Project
             SET    IsActief = 0
             WHERE  Id          = :id
               AND  GebruikerId = :uid'
        );
        $stmt->execute([':id' => $projectId, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
