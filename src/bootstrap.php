<?php
/**
 * Application Bootstrap
 * 
 * Initializes the application by setting up autoloading,
 * loading configuration, and initializing core services.
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set default timezone
date_default_timezone_set('UTC');

// Define base paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('SRC_PATH', ROOT_PATH . '/src');

// Simple autoloader for Linkon namespace
spl_autoload_register(function ($class) {
    $prefix = 'Linkon\\';
    $baseDir = SRC_PATH . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', strtolower(dirname($relativeClass))) . '/' . basename($relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
function loadConfig(): array
{
    $configFile = CONFIG_PATH . '/config.php';
    if (!file_exists($configFile)) {
        throw new RuntimeException("Configuration file not found. Please create config/config.php from config.example.php");
    }
    return require $configFile;
}

// Get configuration
$config = loadConfig();

// Initialize services
use Linkon\Classes\DatabaseConnector;
use Linkon\Classes\Encryption;
use Linkon\Classes\LinkGenerator;

/**
 * Get database connector instance
 * 
 * @return DatabaseConnector
 */
function getDatabase(): DatabaseConnector
{
    global $config;
    static $db = null;
    if ($db === null) {
        $db = DatabaseConnector::getInstance($config['database']);
    }
    return $db;
}

/**
 * Get encryption instance
 * 
 * @return Encryption
 */
function getEncryption(): Encryption
{
    global $config;
    static $encryption = null;
    if ($encryption === null) {
        $encryption = new Encryption($config['encryption']['key'], $config['encryption']['method']);
    }
    return $encryption;
}

/**
 * Get link generator instance
 * 
 * @return LinkGenerator
 */
function getLinkGenerator(): LinkGenerator
{
    global $config;
    static $generator = null;
    if ($generator === null) {
        $generator = new LinkGenerator($config['app']['link_length'], getDatabase());
    }
    return $generator;
}

/**
 * Send JSON response
 * 
 * @param mixed $data Response data
 * @param int $statusCode HTTP status code
 */
function jsonResponse($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send error response
 * 
 * @param string $message Error message
 * @param int $statusCode HTTP status code
 */
function errorResponse(string $message, int $statusCode = 400): void
{
    jsonResponse(['error' => $message], $statusCode);
}

/**
 * Get JSON request body
 * 
 * @return array
 */
function getJsonBody(): array
{
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Validate required fields
 * 
 * @param array $data Data to validate
 * @param array $required Required field names
 * @throws InvalidArgumentException If required field is missing
 */
function validateRequired(array $data, array $required): void
{
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            throw new InvalidArgumentException("Missing required field: {$field}");
        }
    }
}

/**
 * Generate session token
 * 
 * @return string Cryptographically secure token
 */
function generateSessionToken(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Create a session for user
 * 
 * @param int $userId User ID
 * @param int $expireHours Hours until expiration (default: 24)
 * @return string Session token
 */
function createSession(int $userId, int $expireHours = 24): string
{
    $db = getDatabase();
    $token = generateSessionToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expireHours} hours"));

    $db->execute(
        "INSERT INTO sessions (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)",
        ['user_id' => $userId, 'token' => $token, 'expires_at' => $expiresAt]
    );

    return $token;
}

/**
 * Validate session token and get user ID
 * 
 * @param string $token Session token
 * @return int|null User ID or null if invalid
 */
function validateSession(string $token): ?int
{
    $db = getDatabase();

    $session = $db->fetchOne(
        "SELECT user_id FROM sessions WHERE token = :token AND expires_at > NOW()",
        ['token' => $token]
    );

    return $session ? (int) $session['user_id'] : null;
}

/**
 * Destroy a session
 * 
 * @param string $token Session token
 */
function destroySession(string $token): void
{
    $db = getDatabase();
    $db->execute("DELETE FROM sessions WHERE token = :token", ['token' => $token]);
}

/**
 * Get authenticated user ID from request
 * 
 * @return int|null User ID or null if not authenticated
 */
function getAuthenticatedUserId(): ?int
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return validateSession($matches[1]);
    }

    return null;
}

/**
 * Require authentication
 * 
 * @return int User ID
 */
function requireAuth(): int
{
    $userId = getAuthenticatedUserId();
    if ($userId === null) {
        errorResponse('Unauthorized', 401);
    }
    return $userId;
}
