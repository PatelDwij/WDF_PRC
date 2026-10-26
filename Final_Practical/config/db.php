<?php
/**
 * StudentHub Portal - Reusable PDO Database Connection
 * Practical 8: Database Schema, ER Model, and PDO Connectivity
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    /**
     * Get or initialize the PDO database connection singleton.
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Log connection error securely server-side
                error_log('[StudentHub DB Error] Connection failed: ' . $e->getMessage());
                throw new PDOException('Unable to connect to the database service. Please ensure database server is running.');
            }
        }

        return self::$instance;
    }

    /**
     * Reset connection instance (useful for testing reconnects).
     */
    public static function resetConnection(): void {
        self::$instance = null;
    }
}

/**
 * Global helper function to get PDO database connection.
 *
 * @return PDO
 */
function getDB(): PDO {
    return Database::getConnection();
}
