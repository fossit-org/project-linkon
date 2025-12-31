<?php
/**
 * User API Endpoint
 * 
 * Handles user registration, authentication, and account management.
 * 
 * Endpoints:
 * POST /api/user/register - Register a new user
 * POST /api/user/login - Authenticate user
 * POST /api/user/logout - Logout user
 * GET /api/user/profile - Get user profile
 * PUT /api/user/password - Update password
 * DELETE /api/user - Delete account
 */

require_once __DIR__ . '/../bootstrap.php';

use Linkon\Models\User;

// Set CORS headers from configuration
setCorsHeaders($config);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Get request path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/user';
$path = parse_url($requestUri, PHP_URL_PATH);
$action = str_replace($basePath, '', $path);
$action = trim($action, '/');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDatabase();
    $user = new User($db);

    switch ($method) {
        case 'POST':
            if ($action === 'register') {
                // Register new user
                $data = getJsonBody();
                validateRequired($data, ['username', 'password']);

                $userId = $user->create($data['username'], $data['password']);
                $token = createSession($userId);

                jsonResponse([
                    'message' => 'User registered successfully',
                    'user_id' => $userId,
                    'token' => $token,
                ], 201);
            } elseif ($action === 'login') {
                // Login
                $data = getJsonBody();
                validateRequired($data, ['username', 'password']);

                if ($user->authenticate($data['username'], $data['password'])) {
                    $token = createSession($user->getId());

                    jsonResponse([
                        'message' => 'Login successful',
                        'user_id' => $user->getId(),
                        'token' => $token,
                    ]);
                } else {
                    errorResponse('Invalid username or password', 401);
                }
            } elseif ($action === 'logout') {
                // Logout
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
                if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
                    destroySession($matches[1]);
                }
                jsonResponse(['message' => 'Logged out successfully']);
            } else {
                errorResponse('Invalid action', 404);
            }
            break;

        case 'GET':
            if ($action === 'profile' || $action === '') {
                // Get profile
                $userId = requireAuth();
                $userData = $user->findById($userId);

                if ($userData) {
                    jsonResponse([
                        'id' => $userData['id'],
                        'username' => $userData['username'],
                        'created_at' => $userData['created_at'],
                    ]);
                } else {
                    errorResponse('User not found', 404);
                }
            } else {
                errorResponse('Invalid action', 404);
            }
            break;

        case 'PUT':
            if ($action === 'password') {
                // Update password
                $userId = requireAuth();
                $data = getJsonBody();
                validateRequired($data, ['current_password', 'new_password']);

                $userData = $user->findById($userId);
                if ($userData && $user->authenticate($userData['username'], $data['current_password'])) {
                    $user->updatePassword($data['current_password'], $data['new_password']);
                    jsonResponse(['message' => 'Password updated successfully']);
                } else {
                    errorResponse('Current password is incorrect', 401);
                }
            } else {
                errorResponse('Invalid action', 404);
            }
            break;

        case 'DELETE':
            if ($action === '' || $action === 'account') {
                // Delete account
                $userId = requireAuth();
                $data = getJsonBody();
                validateRequired($data, ['password']);

                $userData = $user->findById($userId);
                if ($userData && $user->authenticate($userData['username'], $data['password'])) {
                    $user->delete($data['password']);
                    jsonResponse(['message' => 'Account deleted successfully']);
                } else {
                    errorResponse('Password verification failed', 401);
                }
            } else {
                errorResponse('Invalid action', 404);
            }
            break;

        default:
            errorResponse('Method not allowed', 405);
    }
} catch (InvalidArgumentException $e) {
    errorResponse($e->getMessage(), 400);
} catch (RuntimeException $e) {
    errorResponse($e->getMessage(), 400);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    errorResponse('Database error occurred', 500);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    errorResponse('An error occurred', 500);
}
