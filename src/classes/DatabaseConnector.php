<?php
/**
 * DatabaseConnector Class
 * 
 * Provides a secure PDO-based database connection for SQL RDBMS.
 * Supports MySQL, PostgreSQL, and SQLite.
 * 
 * This class is designed to be easily portable to other languages:
 * - Uses standard SQL and prepared statements
 * - Configuration-driven connection parameters
 * - Singleton pattern for connection management
 */

namespace Linkon\Classes;

use PDO;
use PDOException;

class DatabaseConnector
{
    private static ?DatabaseConnector $instance = null;
    private ?PDO $connection = null;
    private array $config;

    /**
     * Private constructor to prevent direct instantiation
     * 
     * @param array $config Database configuration array
     */
    private function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Get singleton instance of DatabaseConnector
     * 
     * @param array $config Database configuration array
     * @return DatabaseConnector
     */
    public static function getInstance(array $config): DatabaseConnector
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     * 
     * @throws PDOException If connection fails
     */
    private function connect(): void
    {
        $driver = $this->config['driver'] ?? 'mysql';
        $dsn = $this->buildDSN($driver);

        try {
            $this->connection = new PDO(
                $dsn,
                $this->config['username'] ?? null,
                $this->config['password'] ?? null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            // Log the actual error for debugging, but don't expose details
            error_log("Database connection failed: " . $e->getMessage());
            throw new PDOException("Database connection failed");
        }
    }

    /**
     * Build DSN string based on database driver
     * 
     * @param string $driver Database driver (mysql, pgsql, sqlite)
     * @return string DSN connection string
     */
    private function buildDSN(string $driver): string
    {
        switch ($driver) {
            case 'mysql':
                return sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['database'],
                    $this->config['charset'] ?? 'utf8mb4'
                );
            case 'pgsql':
                return sprintf(
                    "pgsql:host=%s;port=%s;dbname=%s",
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['database']
                );
            case 'sqlite':
                return sprintf("sqlite:%s", $this->config['database']);
            default:
                throw new PDOException("Unsupported database driver: {$driver}");
        }
    }

    /**
     * Get the PDO connection instance
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Execute a prepared statement with parameters
     * 
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind
     * @return \PDOStatement
     */
    public function execute(string $query, array $params = []): \PDOStatement
    {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     * 
     * @param string $query SQL query
     * @param array $params Parameters to bind
     * @return array|null
     */
    public function fetchOne(string $query, array $params = []): ?array
    {
        $stmt = $this->execute($query, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows
     * 
     * @param string $query SQL query
     * @param array $params Parameters to bind
     * @return array
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->execute($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get the last inserted ID
     * 
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    /**
     * Reset the singleton instance (useful for testing)
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
