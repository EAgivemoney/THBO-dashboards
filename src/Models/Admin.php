<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Lang;
use PDO;

/**
 * Admin model — queries for the admin panel.
 * All methods work without a user filter (admins and owners see everything).
 *
 * Dutch table and column names are mapped to English keys with an alias.
 */
class Admin
{
    // Role ids (see sql/database.sql)
    public const ROLE_USER  = 1;
    public const ROLE_ADMIN = 2;
    public const ROLE_OWNER = 3;

    /**
     * Role name in the visitor's language (resources/lang, group "roles").
     * roleName() stays the fixed English name that is also used for CSS classes.
     */
    public static function roleLabel(int $roleId): string
    {
        return Lang::get('roles.' . strtolower(self::roleName($roleId)));
    }

    public static function roleName(int $roleId): string
    {
        return match ($roleId) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_OWNER => 'Owner',
            default          => 'User',
        };
    }

    /**
     * System-wide statistics for the overview cards.
     *
     * @return array{user_count:int,inactive_user_count:int,project_count:int,task_count:int,completed_task_count:int}|array{}
     */
    public static function systemStats(): array
    {
        $pdo = Database::connection();

        $row = $pdo->query(
            'SELECT
                (SELECT COUNT(*) FROM Gebruiker  WHERE IsActief = 1)                    AS user_count,
                (SELECT COUNT(*) FROM Gebruiker  WHERE IsActief = 0)                    AS inactive_user_count,
                (SELECT COUNT(*) FROM Project    WHERE IsActief = 1)                    AS project_count,
                (SELECT COUNT(*) FROM Taak       WHERE IsActief = 1)                    AS task_count,
                (SELECT COUNT(*) FROM Taak       WHERE IsActief = 1 AND StatusId = 3)   AS completed_task_count'
        )->fetch(PDO::FETCH_ASSOC);

        return $row ?: [];
    }

    /**
     * All users including role name, sorted by name.
     */
    public static function allUsers(): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->query(
            'SELECT
                g.Id            AS id,
                g.VolledigeNaam AS full_name,
                g.Email         AS email,
                g.RolId         AS role_id,
                r.Naam          AS role_name,
                g.IsActief      AS is_active,
                g.AangemaaktOp  AS created_at,
                COUNT(p.Id)     AS project_count
             FROM      Gebruiker g
             JOIN      Rol r       ON r.Id = g.RolId
             LEFT JOIN Project p   ON p.GebruikerId = g.Id AND p.IsActief = 1
             GROUP BY  g.Id, g.VolledigeNaam, g.Email, g.RolId, r.Naam, g.IsActief, g.AangemaaktOp
             ORDER BY  g.IsActief DESC, g.VolledigeNaam ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * All projects with owner and task counts.
     */
    public static function allProjects(): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->query(
            'SELECT
                p.Id                      AS id,
                p.Titel                   AS title,
                p.IsActief                AS is_active,
                g.VolledigeNaam           AS owner,
                COUNT(t.Id)               AS task_count,
                SUM(t.StatusId = 3)       AS completed_count,
                FN_ProjectVoortgang(p.Id) AS progress,
                p.AangemaaktOp            AS created_at
             FROM      Project p
             JOIN      Gebruiker g   ON g.Id = p.GebruikerId
             LEFT JOIN Taak t        ON t.ProjectId = p.Id AND t.IsActief = 1
             GROUP BY  p.Id, p.Titel, p.IsActief, g.VolledigeNaam, p.AangemaaktOp
             ORDER BY  p.IsActief DESC, p.AangemaaktOp DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Changes a user's role (owner only).
     */
    public static function updateRole(int $userId, int $newRoleId): bool
    {
        if (!in_array($newRoleId, [self::ROLE_USER, self::ROLE_ADMIN, self::ROLE_OWNER], true)) {
            return false;
        }

        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE Gebruiker SET RolId = :role WHERE Id = :id'
        );
        $stmt->execute([':role' => $newRoleId, ':id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Activates or deactivates a user (owner only).
     */
    public static function toggleActive(int $userId, bool $active): bool
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE Gebruiker SET IsActief = :active WHERE Id = :id'
        );
        $stmt->execute([':active' => (int) $active, ':id' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
