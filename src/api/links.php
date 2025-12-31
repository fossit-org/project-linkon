<?php
/**
 * Links API Endpoint
 * 
 * Handles link creation, retrieval, update, and deletion.
 * 
 * Endpoints:
 * POST /api/links - Create a new link
 * GET /api/links - Get all links for authenticated user
 * GET /api/links/{id} - Get a specific link
 * PUT /api/links/{id} - Update a link
 * DELETE /api/links/{id} - Delete a link
 */

require_once __DIR__ . '/../bootstrap.php';

use Linkon\Models\Link;

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Get request path and extract link ID if present
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Extract ID from path like /api/links/123
$linkId = null;
if (preg_match('/\/api\/links\/(\d+)/', $path, $matches)) {
    $linkId = (int) $matches[1];
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDatabase();
    $encryption = getEncryption();
    $linkGenerator = getLinkGenerator();
    $link = new Link($db, $encryption, $linkGenerator);

    global $config;

    switch ($method) {
        case 'POST':
            // Create new link
            $userId = requireAuth();
            $data = getJsonBody();
            validateRequired($data, ['title', 'content']);

            $result = $link->create(
                $userId,
                $data['title'],
                $data['content'],
                $data['is_public'] ?? true,
                $data['expires_at'] ?? null
            );

            // Add full URL to response
            $result['url'] = $linkGenerator->buildUrl($result['short_code'], $config['app']['base_url']);

            jsonResponse($result, 201);
            break;

        case 'GET':
            if ($linkId !== null) {
                // Get specific link
                $userId = requireAuth();
                $linkData = $link->findById($linkId);

                if ($linkData === null) {
                    errorResponse('Link not found', 404);
                }

                // Verify ownership
                if ((int) $linkData['user_id'] !== $userId) {
                    errorResponse('Not authorized', 403);
                }

                $linkData['url'] = $linkGenerator->buildUrl($linkData['short_code'], $config['app']['base_url']);
                jsonResponse($linkData);
            } else {
                // Get all links for user
                $userId = requireAuth();

                $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
                $perPage = isset($_GET['per_page']) ? min(100, max(1, (int) $_GET['per_page'])) : 20;

                $links = $link->findByUserId($userId, $page, $perPage);
                $total = $link->countByUserId($userId);

                // Add URLs to each link
                foreach ($links as &$l) {
                    $l['url'] = $linkGenerator->buildUrl($l['short_code'], $config['app']['base_url']);
                }

                jsonResponse([
                    'links' => $links,
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => ceil($total / $perPage),
                    ],
                ]);
            }
            break;

        case 'PUT':
            if ($linkId === null) {
                errorResponse('Link ID required', 400);
            }

            $userId = requireAuth();
            $data = getJsonBody();

            $link->update($linkId, $userId, $data);
            $linkData = $link->findById($linkId);
            $linkData['url'] = $linkGenerator->buildUrl($linkData['short_code'], $config['app']['base_url']);

            jsonResponse($linkData);
            break;

        case 'DELETE':
            if ($linkId === null) {
                errorResponse('Link ID required', 400);
            }

            $userId = requireAuth();
            $link->delete($linkId, $userId);

            jsonResponse(['message' => 'Link deleted successfully']);
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
