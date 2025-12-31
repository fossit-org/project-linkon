<?php
/**
 * Link Model
 * 
 * Handles creation, storage, and retrieval of shortened links.
 * User content is encrypted before storage for security.
 */

namespace Linkon\Models;

use Linkon\Classes\DatabaseConnector;
use Linkon\Classes\Encryption;
use Linkon\Classes\LinkGenerator;

class Link
{
    private DatabaseConnector $db;
    private Encryption $encryption;
    private LinkGenerator $linkGenerator;

    /**
     * Constructor
     * 
     * @param DatabaseConnector $db Database connector instance
     * @param Encryption $encryption Encryption instance
     * @param LinkGenerator $linkGenerator Link generator instance
     */
    public function __construct(DatabaseConnector $db, Encryption $encryption, LinkGenerator $linkGenerator)
    {
        $this->db = $db;
        $this->encryption = $encryption;
        $this->linkGenerator = $linkGenerator;
    }

    /**
     * Create a new link
     * 
     * @param int $userId User ID (owner)
     * @param string $title Link title
     * @param string $content Content to store (will be encrypted)
     * @param bool $isPublic Whether the link is publicly accessible
     * @param string|null $expiresAt Expiration datetime (null for no expiration)
     * @return array Created link data
     */
    public function create(
        int $userId,
        string $title,
        string $content,
        bool $isPublic = true,
        ?string $expiresAt = null
    ): array {
        // Validate inputs
        if (empty($title)) {
            throw new \InvalidArgumentException("Title cannot be empty");
        }

        if (strlen($title) > 255) {
            throw new \InvalidArgumentException("Title cannot exceed 255 characters");
        }

        // Generate unique short code
        $shortCode = $this->linkGenerator->generate();

        // Encrypt content
        $encryptedContent = $this->encryption->encrypt($content);

        // Insert link
        $query = "INSERT INTO links (user_id, short_code, title, content, is_public, expires_at, created_at, updated_at) 
                  VALUES (:user_id, :short_code, :title, :content, :is_public, :expires_at, NOW(), NOW())";

        $this->db->execute($query, [
            'user_id' => $userId,
            'short_code' => $shortCode,
            'title' => $title,
            'content' => $encryptedContent,
            'is_public' => $isPublic ? 1 : 0,
            'expires_at' => $expiresAt,
        ]);

        $id = (int) $this->db->lastInsertId();

        return [
            'id' => $id,
            'user_id' => $userId,
            'short_code' => $shortCode,
            'title' => $title,
            'is_public' => $isPublic,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Find link by short code
     * 
     * @param string $shortCode Short code to search
     * @param bool $decryptContent Whether to decrypt content
     * @return array|null Link data or null if not found
     */
    public function findByShortCode(string $shortCode, bool $decryptContent = true): ?array
    {
        $query = "SELECT id, user_id, short_code, title, content, is_public, expires_at, view_count, created_at, updated_at 
                  FROM links WHERE short_code = :short_code LIMIT 1";

        $link = $this->db->fetchOne($query, ['short_code' => $shortCode]);

        if ($link === null) {
            return null;
        }

        // Check expiration
        if ($link['expires_at'] !== null && strtotime($link['expires_at']) < time()) {
            return null; // Link has expired
        }

        // Decrypt content if requested
        if ($decryptContent && !empty($link['content'])) {
            $link['content'] = $this->encryption->decrypt($link['content']);
        }

        $link['is_public'] = (bool) $link['is_public'];

        return $link;
    }

    /**
     * Find link by ID
     * 
     * @param int $id Link ID
     * @param bool $decryptContent Whether to decrypt content
     * @return array|null Link data or null if not found
     */
    public function findById(int $id, bool $decryptContent = true): ?array
    {
        $query = "SELECT id, user_id, short_code, title, content, is_public, expires_at, view_count, created_at, updated_at 
                  FROM links WHERE id = :id LIMIT 1";

        $link = $this->db->fetchOne($query, ['id' => $id]);

        if ($link === null) {
            return null;
        }

        if ($decryptContent && !empty($link['content'])) {
            $link['content'] = $this->encryption->decrypt($link['content']);
        }

        $link['is_public'] = (bool) $link['is_public'];

        return $link;
    }

    /**
     * Get all links for a user
     * 
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Links data (without decrypted content for performance)
     */
    public function findByUserId(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $query = "SELECT id, user_id, short_code, title, is_public, expires_at, view_count, created_at, updated_at 
                  FROM links 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";

        // Using separate execute for parameter binding with LIMIT/OFFSET
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $links = $stmt->fetchAll();

        return array_map(function ($link) {
            $link['is_public'] = (bool) $link['is_public'];
            return $link;
        }, $links);
    }

    /**
     * Get total link count for a user
     * 
     * @param int $userId User ID
     * @return int Total count
     */
    public function countByUserId(int $userId): int
    {
        $query = "SELECT COUNT(*) as count FROM links WHERE user_id = :user_id";
        $result = $this->db->fetchOne($query, ['user_id' => $userId]);
        return (int) $result['count'];
    }

    /**
     * Update a link
     * 
     * @param int $id Link ID
     * @param int $userId User ID (for ownership verification)
     * @param array $data Update data (title, content, is_public, expires_at)
     * @return bool True if successful
     * @throws \RuntimeException If not authorized
     */
    public function update(int $id, int $userId, array $data): bool
    {
        // Verify ownership
        $link = $this->findById($id, false);
        if ($link === null) {
            throw new \RuntimeException("Link not found");
        }

        if ((int) $link['user_id'] !== $userId) {
            throw new \RuntimeException("Not authorized to update this link");
        }

        $updates = [];
        $params = ['id' => $id];

        if (isset($data['title'])) {
            if (strlen($data['title']) > 255) {
                throw new \InvalidArgumentException("Title cannot exceed 255 characters");
            }
            $updates[] = "title = :title";
            $params['title'] = $data['title'];
        }

        if (isset($data['content'])) {
            $updates[] = "content = :content";
            $params['content'] = $this->encryption->encrypt($data['content']);
        }

        if (isset($data['is_public'])) {
            $updates[] = "is_public = :is_public";
            $params['is_public'] = $data['is_public'] ? 1 : 0;
        }

        if (array_key_exists('expires_at', $data)) {
            $updates[] = "expires_at = :expires_at";
            $params['expires_at'] = $data['expires_at'];
        }

        if (empty($updates)) {
            return true; // Nothing to update
        }

        $updates[] = "updated_at = NOW()";
        $query = "UPDATE links SET " . implode(', ', $updates) . " WHERE id = :id";
        $this->db->execute($query, $params);

        return true;
    }

    /**
     * Delete a link
     * 
     * @param int $id Link ID
     * @param int $userId User ID (for ownership verification)
     * @return bool True if successful
     * @throws \RuntimeException If not authorized
     */
    public function delete(int $id, int $userId): bool
    {
        // Verify ownership
        $link = $this->findById($id, false);
        if ($link === null) {
            throw new \RuntimeException("Link not found");
        }

        if ((int) $link['user_id'] !== $userId) {
            throw new \RuntimeException("Not authorized to delete this link");
        }

        $query = "DELETE FROM links WHERE id = :id";
        $this->db->execute($query, ['id' => $id]);

        return true;
    }

    /**
     * Increment view count for a link
     * 
     * @param int $id Link ID
     */
    public function incrementViewCount(int $id): void
    {
        $query = "UPDATE links SET view_count = view_count + 1 WHERE id = :id";
        $this->db->execute($query, ['id' => $id]);
    }

    /**
     * Access a public link (increments view count)
     * 
     * @param string $shortCode Short code
     * @return array|null Link data or null if not found/not public
     */
    public function accessPublicLink(string $shortCode): ?array
    {
        $link = $this->findByShortCode($shortCode);

        if ($link === null) {
            return null;
        }

        if (!$link['is_public']) {
            return null; // Not publicly accessible
        }

        // Increment view count
        $this->incrementViewCount($link['id']);

        return $link;
    }
}
