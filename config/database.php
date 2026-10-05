<?php
/**
 * Database Connection Manager (PDO)
 * Supports MySQL 8+ (XAMPP / Production) with auto-fallback to SQLite for seamless local testing
 */

require_once __DIR__ . '/config.php';

// Database Credentials (Set for standard XAMPP / MySQL)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'achar_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    /**
     * Get Singleton PDO connection
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Establish connection
     */
    private static function connect(): void {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // 1. Attempt MySQL connection first
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            // Short timeout to avoid hanging if MySQL is not running locally
            $mysqlOptions = $options + [PDO::ATTR_TIMEOUT => 2];
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $mysqlOptions);
            self::$driver = 'mysql';
            return;
        } catch (PDOException $e) {
            // MySQL unavailable; fall through to local SQLite database engine
        }

        // 2. Fallback to SQLite (Guarantees zero-friction development & local running)
        try {
            $sqlitePath = ROOT_PATH . '/database/achar_store.sqlite';
            $isNew = !file_exists($sqlitePath);
            self::$instance = new PDO("sqlite:" . $sqlitePath, null, null, $options);
            self::$instance->exec("PRAGMA foreign_keys = ON;");
            self::$driver = 'sqlite';

            if ($isNew || filesize($sqlitePath) === 0) {
                self::initializeSqliteDatabase(self::$instance);
            }
        } catch (PDOException $e) {
            die("<div style='font-family:sans-serif;padding:30px;color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;border-radius:8px;max-width:600px;margin:50px auto;'>" .
                "<h3>Database Connection Error</h3>" .
                "<p>" . htmlspecialchars($e->getMessage()) . "</p>" .
                "<p>Please ensure MySQL is running in XAMPP or check database settings in <code>config/database.php</code>.</p>" .
                "</div>");
        }
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    /**
     * Initializes SQLite schema & seed if running in fallback mode
     */
    private static function initializeSqliteDatabase(PDO $pdo): void {
        $schemaFile = ROOT_PATH . '/database/sqlite_schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
        }
    }
}

// Global convenience accessor
function db(): PDO {
    return Database::getConnection();
}
