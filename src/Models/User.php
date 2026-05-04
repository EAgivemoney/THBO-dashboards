<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * User model — werkt tegen de tabel `Gebruiker` uit sql/database.sql.
 * 
 * Belangrijke kolommen:
 *   Id, RolId, Voornaam, Tussenvoegsel, Achternaam, VolledigeNaam (generated),
 *   Email, WachtwoordHash, IsActief
 */
class User
{
    public static function findByEmail(string $email): ?array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT Id, RolId, Voornaam, Tussenvoegsel, Achternaam, VolledigeNaam,
                    Email, WachtwoordHash, IsActief
             FROM  Gebruiker
             WHERE Email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT Id, RolId, Voornaam, Tussenvoegsel, Achternaam, VolledigeNaam,
                    Email, IsActief
            FROM  Gebruiker
            WHERE Id = :id
            LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Maakt een nieuwe gebruiker aan en geeft het nieuwe Id terug.
     *
     * @param array{
     *   voornaam:string, tussenvoegsel:?string, achternaam:string,
     *   email:string, wachtwoord:string, rolId?:int
     * } $data
     */
    public static function create(array $data): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO Gebruiker
                (RolId, Voornaam, Tussenvoegsel, Achternaam, Email, WachtwoordHash)
             VALUES
                (:rol, :vn, :tv, :an, :email, :hash'
        );
        $stmt->execute([
            ':rol'   => $data['rolId'] ?? 1, // 1 = gebruiker
            ':vn'    => $data['voornaam'],
            ':tv'    => $data['tussenvoegsel'] ?: null,
            ':an'    => $data['achternaam'],
            'email'  => $data['email'],
            ':hash'  => password_hash($data['wachtwoord'], PASSWORD_DEFAULT),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}