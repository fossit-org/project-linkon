<?php
/**
 * User Model
 * 
 * Handles user authentication and management.
 * Stores only username and password hash - minimal user data as per requirements.
 */

namespace Linkon\Models;

use Linkon\Classes\DatabaseConnector;
use Linkon\Classes\Encryption;

class User
{
    private DatabaseConnector $db;
    private ?int $id = null;
    private ?string $username = null;
    private ?string $passwordHash = null;
    private ?string $createdAt = null;

    /**
     * Constructor
     * 
     * @param DatabaseConnector $db Database connector instance
     */
    public function __construct(DatabaseConnector $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new user
     * 
     * @param string $username Unique username
     * @param string $password Strong password (will be hashed)
     * @return int User ID
     * @throws \InvalidArgumentException If validation fails
     * @throws \RuntimeException If username already exists
     */
    public function create(string $username, string $password): int
    {
        // Validate username
        $this->validateUsername($username);

        // Validate password strength
        $this->validatePassword($password);

        // Check if username already exists
        if ($this->usernameExists($username)) {
            throw new \RuntimeException("Username already exists");
        }

        // Hash password
        $passwordHash = Encryption::hashPassword($password);

        // Insert user
        $query = "INSERT INTO users (username, password_hash, created_at) VALUES (:username, :password_hash, NOW())";
        $this->db->execute($query, [
            'username' => $username,
            'password_hash' => $passwordHash,
        ]);

        $this->id = (int) $this->db->lastInsertId();
        $this->username = $username;
        $this->passwordHash = $passwordHash;

        return $this->id;
    }

    /**
     * Authenticate a user
     * 
     * @param string $username Username
     * @param string $password Password to verify
     * @return bool True if authentication successful
     */
    public function authenticate(string $username, string $password): bool
    {
        $user = $this->findByUsername($username);

        if ($user === null) {
            // Perform a dummy password verify to prevent timing attacks
            Encryption::verifyPassword($password, '$argon2id$v=19$m=65536,t=4,p=3$dummysalt$dummyhash');
            return false;
        }

        if (!Encryption::verifyPassword($password, $user['password_hash'])) {
            return false;
        }

        // Load user data
        $this->id = (int) $user['id'];
        $this->username = $user['username'];
        $this->passwordHash = $user['password_hash'];
        $this->createdAt = $user['created_at'];

        return true;
    }

    /**
     * Find user by username
     * 
     * @param string $username Username to search
     * @return array|null User data or null if not found
     */
    public function findByUsername(string $username): ?array
    {
        $query = "SELECT id, username, password_hash, created_at FROM users WHERE username = :username LIMIT 1";
        return $this->db->fetchOne($query, ['username' => $username]);
    }

    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return array|null User data or null if not found
     */
    public function findById(int $id): ?array
    {
        $query = "SELECT id, username, password_hash, created_at FROM users WHERE id = :id LIMIT 1";
        return $this->db->fetchOne($query, ['id' => $id]);
    }

    /**
     * Check if username exists
     * 
     * @param string $username Username to check
     * @return bool True if exists
     */
    public function usernameExists(string $username): bool
    {
        $query = "SELECT 1 FROM users WHERE username = :username LIMIT 1";
        return $this->db->fetchOne($query, ['username' => $username]) !== null;
    }

    /**
     * Update password
     * 
     * @param string $currentPassword Current password for verification
     * @param string $newPassword New password
     * @return bool True if successful
     * @throws \RuntimeException If user not loaded or current password incorrect
     */
    public function updatePassword(string $currentPassword, string $newPassword): bool
    {
        if ($this->id === null) {
            throw new \RuntimeException("User not loaded");
        }

        // Verify current password
        if (!Encryption::verifyPassword($currentPassword, $this->passwordHash)) {
            throw new \RuntimeException("Current password is incorrect");
        }

        // Validate new password
        $this->validatePassword($newPassword);

        // Hash and update
        $newHash = Encryption::hashPassword($newPassword);
        $query = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
        $this->db->execute($query, [
            'password_hash' => $newHash,
            'id' => $this->id,
        ]);

        $this->passwordHash = $newHash;

        return true;
    }

    /**
     * Delete user account
     * 
     * @param string $password Password for verification
     * @return bool True if successful
     * @throws \RuntimeException If verification fails
     */
    public function delete(string $password): bool
    {
        if ($this->id === null) {
            throw new \RuntimeException("User not loaded");
        }

        if (!Encryption::verifyPassword($password, $this->passwordHash)) {
            throw new \RuntimeException("Password verification failed");
        }

        // Delete user's links first
        $this->db->execute("DELETE FROM links WHERE user_id = :user_id", ['user_id' => $this->id]);

        // Delete user
        $this->db->execute("DELETE FROM users WHERE id = :id", ['id' => $this->id]);

        $this->id = null;
        $this->username = null;
        $this->passwordHash = null;

        return true;
    }

    /**
     * Validate username format
     * 
     * @param string $username Username to validate
     * @throws \InvalidArgumentException If invalid
     */
    private function validateUsername(string $username): void
    {
        if (strlen($username) < 3 || strlen($username) > 30) {
            throw new \InvalidArgumentException("Username must be between 3 and 30 characters");
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new \InvalidArgumentException("Username can only contain letters, numbers, and underscores");
        }
    }

    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @throws \InvalidArgumentException If password is weak
     */
    private function validatePassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException("Password must be at least 8 characters");
        }

        // Check for complexity
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);

        $complexity = ($hasUpper ? 1 : 0) + ($hasLower ? 1 : 0) + ($hasNumber ? 1 : 0) + ($hasSpecial ? 1 : 0);

        if ($complexity < 3) {
            throw new \InvalidArgumentException(
                "Password must contain at least 3 of: uppercase, lowercase, numbers, special characters"
            );
        }
    }

    // Getters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }
}
