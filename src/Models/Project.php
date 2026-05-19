<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Project model — werkt tegen de tabel `Project`.
 *
 * Bevat methoden voor aanmaken, bijwerken, verwijderen (soft-delete) en
 * ophalen van projecten die bij een specifieke gebruiker horen.
 */
class Project
{
    /**
     * Geeft alle actieve projecten terug voor een gebruiker,
     * inclusief het aantal taken per status en de voortgang (0–100)
     * via de database-functie FN_ProjectVoortgang.
     *
     * @return array<int, array{Id:int,Titel:string,Beschrijving:string|null,AantakTaken:int,AantalVoltooid:int,Voortgang:int}>
     */
    public static function forUser(int $userId): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT
                p.Id,
                p.Titel,
                p.Beschrijving,
                COUNT(t.Id)              AS AantakTaken,
                SUM(t.StatusId = 3)      AS AantalVoltooid,
                FN_ProjectVoortgang(p.Id) AS Voortgang
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
     * Maakt een nieuw project aan via de stored procedure SP_ProjectAanmaken.
     * Geeft het Id van het nieuwe project terug.
     */
    public static function create(int $userId, string $titel, ?string $beschrijving): int
    {
        $pdo  = Database::connection();
        $call = $pdo->prepare('CALL SP_ProjectAanmaken(:uid, :titel, :beschr)');
        $call->execute([
            ':uid'    => $userId,
            ':titel'  => $titel,
            ':beschr' => $beschrijving ?: null,
        ]);
        $call->closeCursor();

        return (int) $pdo->lastInsertId();
    }

    /**
     * Werkt een bestaand project bij.
     * Controleert ook of het project van de opgegeven gebruiker is.
     */
    public static function update(int $projectId, string $titel, ?string $beschrijving, int $userId): bool
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE Project
             SET    Titel        = :titel,
                    Beschrijving = :beschr
             WHERE  Id           = :id
               AND  GebruikerId  = :uid
               AND  IsActief     = 1'
        );
        $stmt->execute([
            ':titel'  => $titel,
            ':beschr' => $beschrijving ?: null,
            ':id'     => $projectId,
            ':uid'    => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Soft-delete: zet IsActief op 0.
     * Controleert ook eigenaarschap.
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