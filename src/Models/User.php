<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * User model — works against the `Gebruiker` table from sql/database.sql.
 *
 * The database column names are Dutch; every query maps them to English
 * snake_case keys with an alias, so the rest of the application never sees
 * the Dutch names:
 *   Id → id, RolId → role_id, Voornaam → first_name, Tussenvoegsel → name_prefix,
 *   Achternaam → last_name, VolledigeNaam → full_name (generated column),
 *   Email → email, WachtwoordHash → password_hash, IsActief → is_active,
 *   AangemaaktOp → created_at
 */
class User
{
    public static function findByEmail(string $email): ?array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT Id            AS id,
                    RolId         AS role_id,
                    Voornaam      AS first_name,
                    Tussenvoegsel AS name_prefix,
                    Achternaam    AS last_name,
                    VolledigeNaam AS full_name,
                    Email         AS email,
                    WachtwoordHash AS password_hash,
                    IsActief      AS is_active
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
            'SELECT Id            AS id,
                    RolId         AS role_id,
                    Voornaam      AS first_name,
                    Tussenvoegsel AS name_prefix,
                    Achternaam    AS last_name,
                    VolledigeNaam AS full_name,
                    Email         AS email,
                    IsActief      AS is_active,
                    AangemaaktOp  AS created_at
             FROM  Gebruiker
             WHERE Id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Creates a new user and returns the new id.
     *
     * @param array{
     *   first_name:string, name_prefix:?string, last_name:string,
     *   email:string, password:string, role_id?:int
     * } $data
     */
    public static function create(array $data): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO Gebruiker
                (RolId, Voornaam, Tussenvoegsel, Achternaam, Email, WachtwoordHash)
             VALUES
                (:role, :first_name, :name_prefix, :last_name, :email, :hash)'
        );
        $stmt->execute([
            ':role'        => $data['role_id'] ?? Admin::ROLE_USER,
            ':first_name'  => $data['first_name'],
            ':name_prefix' => $data['name_prefix'] ?: null,
            ':last_name'   => $data['last_name'],
            ':email'       => $data['email'],
            ':hash'        => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    /**
     * Updates a user's password.
     * Returns false if the current password is wrong.
     */
    public static function updatePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT WachtwoordHash AS password_hash FROM Gebruiker WHERE Id = :id AND IsActief = 1 LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            return false;
        }

        $update = $pdo->prepare(
            'UPDATE Gebruiker SET WachtwoordHash = :hash WHERE Id = :id'
        );
        $update->execute([
            ':hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id'   => $userId,
        ]);

        return true;
    }
}
