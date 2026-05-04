<?php
namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Eenvoudige PDO singleton.
 * 
 * Gebruik:
 *   $pdo = Database::connection();
 *   $stmt = $pdo->prepare('SELECT * FROM Gebruiker WHERE Email = ?');
 */

class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        /** @var array $config */
        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            self::$instance = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Kan geen verbinding maken met de database: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        return self::$instance;
    }
}