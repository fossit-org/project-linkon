<?php
/**
 * Public Link Access
 * 
 * Handles public access to shortened links via URL route parameters.
 * Route: /l/{short_code}
 */

require_once __DIR__ . '/../src/bootstrap.php';

use Linkon\Models\Link;

// Get short code from URL
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Extract short code from path like /l/abc123
$shortCode = null;
if (preg_match('/\/l\/([a-zA-Z0-9]+)/', $path, $matches)) {
    $shortCode = $matches[1];
}

if (empty($shortCode)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>Not Found</title></head><body><h1>Link not found</h1></body></html>";
    exit;
}

try {
    $db = getDatabase();
    $encryption = getEncryption();
    $linkGenerator = getLinkGenerator();
    $link = new Link($db, $encryption, $linkGenerator);

    // Access the public link (this increments view count)
    $linkData = $link->accessPublicLink($shortCode);

    if ($linkData === null) {
        http_response_code(404);
        echo "<!DOCTYPE html><html><head><title>Not Found</title></head><body><h1>Link not found or has expired</h1></body></html>";
        exit;
    }

    // Check if JSON response is requested
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (strpos($acceptHeader, 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'title' => $linkData['title'],
            'content' => $linkData['content'],
            'created_at' => $linkData['created_at'],
            'view_count' => $linkData['view_count'] + 1,  // Already incremented
        ]);
        exit;
    }

    // Render HTML page
    $title = htmlspecialchars($linkData['title'], ENT_QUOTES, 'UTF-8');
    $content = htmlspecialchars($linkData['content'], ENT_QUOTES, 'UTF-8');
    $content = nl2br($content);  // Convert newlines to <br>
    $viewCount = $linkData['view_count'] + 1;
    $createdAt = date('F j, Y', strtotime($linkData['created_at']));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Linkon</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: #f8f9fa;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
        }
        h1 {
            font-size: 1.75rem;
            color: #212529;
        }
        .meta {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #6c757d;
        }
        .content {
            padding: 2rem;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .footer {
            background: #f8f9fa;
            padding: 1rem 2rem;
            border-top: 1px solid #e9ecef;
            text-align: center;
            font-size: 0.875rem;
            color: #6c757d;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $title; ?></h1>
            <div class="meta">
                Created on <?php echo $createdAt; ?> • <?php echo $viewCount; ?> views
            </div>
        </div>
        <div class="content">
            <?php echo $content; ?>
        </div>
        <div class="footer">
            Powered by <a href="/">Linkon</a>
        </div>
    </div>
</body>
</html>
<?php
} catch (Exception $e) {
    error_log("Error accessing link: " . $e->getMessage());
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body><h1>An error occurred</h1></body></html>";
}
