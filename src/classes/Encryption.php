<?php
/**
 * Encryption Class
 * 
 * Provides symmetric encryption using AES-256-GCM for authenticated encryption.
 * Uses unique IVs for each encryption operation and includes authentication tags.
 * 
 * This class is designed to be easily portable to other languages:
 * - Uses standard AES-256-GCM algorithm available in most languages
 * - IV is prepended to ciphertext for easy extraction
 * - Authentication tag is appended for integrity verification
 */

namespace Linkon\Classes;

class Encryption
{
    private string $method;
    private string $key;
    private const IV_LENGTH = 12;  // GCM recommended IV length
    private const TAG_LENGTH = 16; // GCM authentication tag length

    /**
     * Constructor
     * 
     * @param string $key Encryption key (should be 32 bytes for AES-256)
     * @param string $method Encryption method (default: aes-256-gcm)
     */
    public function __construct(string $key, string $method = 'aes-256-gcm')
    {
        if (!in_array($method, openssl_get_cipher_methods())) {
            throw new \InvalidArgumentException("Unsupported encryption method: {$method}");
        }

        $this->method = $method;
        $this->key = $this->deriveKey($key);
    }

    /**
     * Derive a proper 32-byte key from the provided key using SHA-256
     * 
     * @param string $key Original key
     * @return string 32-byte derived key
     */
    private function deriveKey(string $key): string
    {
        return hash('sha256', $key, true);
    }

    /**
     * Encrypt data using AES-256-GCM
     * 
     * Output format: base64(IV + ciphertext + tag)
     * 
     * @param string $plaintext Data to encrypt
     * @return string Base64-encoded encrypted data
     * @throws \RuntimeException If encryption fails
     */
    public function encrypt(string $plaintext): string
    {
        // Generate cryptographically secure random IV
        $iv = random_bytes(self::IV_LENGTH);

        // Encrypt with authentication tag
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->method,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new \RuntimeException("Encryption failed: " . openssl_error_string());
        }

        // Combine IV + ciphertext + tag and encode
        return base64_encode($iv . $ciphertext . $tag);
    }

    /**
     * Decrypt data encrypted with AES-256-GCM
     * 
     * @param string $encrypted Base64-encoded encrypted data (IV + ciphertext + tag)
     * @return string Decrypted plaintext
     * @throws \RuntimeException If decryption fails or authentication fails
     */
    public function decrypt(string $encrypted): string
    {
        $data = base64_decode($encrypted, true);
        if ($data === false) {
            throw new \RuntimeException("Invalid base64 encoding");
        }

        // Minimum length: IV + tag (at minimum, for empty plaintext)
        $minLength = self::IV_LENGTH + self::TAG_LENGTH;
        if (strlen($data) < $minLength) {
            throw new \RuntimeException("Encrypted data is too short");
        }

        // Extract components
        $iv = substr($data, 0, self::IV_LENGTH);
        $tag = substr($data, -self::TAG_LENGTH);
        $ciphertext = substr($data, self::IV_LENGTH, -self::TAG_LENGTH);

        // Decrypt with authentication
        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->method,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException("Decryption failed: authentication failed or data corrupted");
        }

        return $plaintext;
    }

    /**
     * Hash a password using Argon2id
     * 
     * @param string $password Password to hash
     * @param array $options Argon2id options
     * @return string Hashed password
     */
    public static function hashPassword(string $password, array $options = []): string
    {
        $defaultOptions = [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ];

        $options = array_merge($defaultOptions, $options);

        return password_hash($password, PASSWORD_ARGON2ID, $options);
    }

    /**
     * Verify a password against a hash
     * 
     * @param string $password Password to verify
     * @param string $hash Hash to verify against
     * @return bool True if password matches
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate a secure random key
     * 
     * @param int $length Length of the key in bytes
     * @return string Hex-encoded random key
     */
    public static function generateKey(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Create a hash of data for integrity checking
     * 
     * @param string $data Data to hash
     * @return string SHA-256 hash
     */
    public static function hash(string $data): string
    {
        return hash('sha256', $data);
    }
}
