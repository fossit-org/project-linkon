<?php
/**
 * LinkGenerator Class
 * 
 * Generates short, unique links for publicly shareable content.
 * Uses a URL-safe character set and collision detection.
 * 
 * This class is designed to be easily portable to other languages:
 * - Uses cryptographically secure random generation
 * - URL-safe character set (Base62)
 * - Configurable link length
 */

namespace Linkon\Classes;

class LinkGenerator
{
    // URL-safe characters (Base62: alphanumeric only)
    private const CHARSET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const CHARSET_LENGTH = 62;

    private int $linkLength;
    private ?DatabaseConnector $db;
    private string $tableName;

    /**
     * Constructor
     * 
     * @param int $linkLength Length of generated short links
     * @param DatabaseConnector|null $db Database connector for collision checking
     * @param string $tableName Table name for link storage
     */
    public function __construct(int $linkLength = 8, ?DatabaseConnector $db = null, string $tableName = 'links')
    {
        if ($linkLength < 4 || $linkLength > 32) {
            throw new \InvalidArgumentException("Link length must be between 4 and 32 characters");
        }

        $this->linkLength = $linkLength;
        $this->db = $db;
        $this->tableName = $tableName;
    }

    /**
     * Generate a unique short code
     * 
     * @param int $maxAttempts Maximum number of generation attempts
     * @return string Unique short code
     * @throws \RuntimeException If unable to generate unique code
     */
    public function generate(int $maxAttempts = 10): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = $this->generateRandomCode();

            // If no database connector, just return the code
            if ($this->db === null) {
                return $code;
            }

            // Check for collision
            if (!$this->codeExists($code)) {
                return $code;
            }
        }

        throw new \RuntimeException("Failed to generate unique code after {$maxAttempts} attempts");
    }

    /**
     * Generate a random code using cryptographically secure random bytes
     * 
     * @return string Random code
     */
    private function generateRandomCode(): string
    {
        $code = '';
        $randomBytes = random_bytes($this->linkLength);

        for ($i = 0; $i < $this->linkLength; $i++) {
            $index = ord($randomBytes[$i]) % self::CHARSET_LENGTH;
            $code .= self::CHARSET[$index];
        }

        return $code;
    }

    /**
     * Check if a code already exists in the database
     * 
     * @param string $code Code to check
     * @return bool True if code exists
     */
    private function codeExists(string $code): bool
    {
        if ($this->db === null) {
            return false;
        }

        $query = "SELECT 1 FROM {$this->tableName} WHERE short_code = :code LIMIT 1";
        $result = $this->db->fetchOne($query, ['code' => $code]);

        return $result !== null;
    }

    /**
     * Generate a deterministic code from a seed (useful for testing)
     * 
     * @param string $seed Seed value
     * @return string Deterministic code
     */
    public function generateFromSeed(string $seed): string
    {
        $hash = hash('sha256', $seed);
        $code = '';

        for ($i = 0; $i < $this->linkLength; $i++) {
            $index = hexdec(substr($hash, $i * 2, 2)) % self::CHARSET_LENGTH;
            $code .= self::CHARSET[$index];
        }

        return $code;
    }

    /**
     * Build a full URL from a short code
     * 
     * @param string $code Short code
     * @param string $baseUrl Base URL of the application
     * @return string Full URL
     */
    public function buildUrl(string $code, string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        return "{$baseUrl}/l/{$code}";
    }

    /**
     * Validate a short code format
     * 
     * @param string $code Code to validate
     * @return bool True if valid
     */
    public function isValidCode(string $code): bool
    {
        if (strlen($code) !== $this->linkLength) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9]+$/', $code) === 1;
    }

    /**
     * Get the configured link length
     * 
     * @return int Link length
     */
    public function getLinkLength(): int
    {
        return $this->linkLength;
    }
}
