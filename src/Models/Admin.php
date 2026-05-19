<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Admin model — queries voor het admin panel.
 * Alle methoden werken zonder gebruikersfilter (admin/eigenaar zien alles).
 */
class Admin
{
    // Rol ID's (zie sql/database.sql)
    public const ROL_GEBRUIKER = 1;
    public const ROL_ADMIN     = 2;
    public const ROL_EIGENAAR  = 3;

    public static function rolNaam(int $rolId): string
    {
        return match ($rolId) {
            self::ROL_ADMIN    => 'Admin',
            self::ROL_EIGENAAR => 'Eigenaar',
            default            => 'Gebruiker',
        };
    }

    /**
     * Systeembrede statistieken voor de overzichtskaarten.
     */
    public static function systeemStats(): array
    {
        $pdo = Database::connection();

        $row = $pdo->query(
            'SELECT
                (SELECT COUNT(*) FROM Gebruiker  WHERE IsActief = 1)                    AS aantalGebruikers,
                (SELECT COUNT(*) FROM Gebruiker  WHERE IsActief = 0)                    AS aantalInactief,
                (SELECT COUNT(*) FROM Project    WHERE IsActief = 1)                    AS aantalProjecten,
                (SELECT COUNT(*) FROM Taak       WHERE IsActief = 1)                    AS aantalTaken,
                (SELECT COUNT(*) FROM Taak       WHERE IsActief = 1 AND StatusId = 3)   AS aantalVoltooid'
        )->fetch(PDO::FETCH_ASSOC);

        return $row ?: [];
    }

    /**
     * Alle gebruikers inclusief rolnaam, gesorteerd op naam.
     */
    public static function alleGebruikers(): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->query(
            'SELECT
                g.Id,
                g.VolledigeNaam,
                g.Email,
                g.RolId,
                r.Naam          AS RolNaam,
                g.IsActief,
                g.AangemaaktOp,
                COUNT(p.Id)     AS AantalProjecten
             FROM      Gebruiker g
             JOIN      Rol r       ON r.Id = g.RolId
             LEFT JOIN Project p   ON p.GebruikerId = g.Id AND p.IsActief = 1
             GROUP BY  g.Id, g.VolledigeNaam, g.Email, g.RolId, r.Naam, g.IsActief, g.AangemaaktOp
             ORDER BY  g.IsActief DESC, g.VolledigeNaam ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Alle projecten met eigenaar en taakaantallen.
     */
    public static function alleProjecten(): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->query(
            'SELECT
                p.Id,
                p.Titel,
                p.IsActief,
                g.VolledigeNaam         AS Eigenaar,
                COUNT(t.Id)             AS AantalTaken,
                SUM(t.StatusId = 3)     AS AantalVoltooid,
                FN_ProjectVoortgang(p.Id) AS Voortgang,
                p.AangemaaktOp
             FROM      Project p
             JOIN      Gebruiker g   ON g.Id = p.GebruikerId
             LEFT JOIN Taak t        ON t.ProjectId = p.Id AND t.IsActief = 1
             GROUP BY  p.Id, p.Titel, p.IsActief, g.VolledigeNaam, p.AangemaaktOp
             ORDER BY  p.IsActief DESC, p.AangemaaktOp DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verander de rol van een gebruiker (alleen eigenaar mag dit).
     */
    public static function updateRol(int $gebruikerId, int $nieuweRolId): bool
    {
        if (!in_array($nieuweRolId, [self::ROL_GEBRUIKER, self::ROL_ADMIN, self::ROL_EIGENAAR], true)) {
            return false;
        }

        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE Gebruiker SET RolId = :rol WHERE Id = :id'
        );
        $stmt->execute([':rol' => $nieuweRolId, ':id' => $gebruikerId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Zet een gebruiker actief of inactief (alleen eigenaar mag dit).
     */
    public static function toggleActief(int $gebruikerId, bool $actief): bool
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE Gebruiker SET IsActief = :actief WHERE Id = :id'
        );
        $stmt->execute([':actief' => (int) $actief, ':id' => $gebruikerId]);
        return $stmt->rowCount() > 0;
    }
}