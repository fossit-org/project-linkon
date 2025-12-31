<?php
/**
 * Front Controller
 * 
 * Routes all requests to appropriate handlers.
 * Place this in the web root of your Apache server.
 */

// Get request path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Route based on path
if (preg_match('/^\/api\/user/', $path)) {
    // User API
    require __DIR__ . '/../src/api/user.php';
} elseif (preg_match('/^\/api\/links/', $path)) {
    // Links API
    require __DIR__ . '/../src/api/links.php';
} elseif (preg_match('/^\/l\/[a-zA-Z0-9]+/', $path)) {
    // Public link access
    require __DIR__ . '/link.php';
} elseif ($path === '/' || $path === '/index.php') {
    // Homepage
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linkon - Secure Link Sharing</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            max-width: 600px;
            text-align: center;
            color: white;
        }
        h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-top: 3rem;
        }
        .feature {
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        .feature h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .feature p {
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        .api-docs {
            margin-top: 3rem;
            background: rgba(255,255,255,0.15);
            padding: 2rem;
            border-radius: 12px;
            text-align: left;
        }
        .api-docs h2 {
            margin-bottom: 1rem;
            text-align: center;
        }
        .endpoint {
            background: rgba(0,0,0,0.2);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .method {
            display: inline-block;
            padding: 0.1rem 0.4rem;
            border-radius: 3px;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        .post { background: #49cc90; color: #1b4332; }
        .get { background: #61affe; color: #0d47a1; }
        .put { background: #fca130; color: #5d4037; }
        .delete { background: #f93e3e; color: #4a0404; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 Linkon</h1>
        <p>Securely store data online, create archives, and share content with publicly accessible short links.</p>
        
        <div class="features">
            <div class="feature">
                <h3>🔒 Secure</h3>
                <p>AES-256-GCM encryption for all stored content</p>
            </div>
            <div class="feature">
                <h3>🔑 Private</h3>
                <p>Only username and password required</p>
            </div>
            <div class="feature">
                <h3>🔗 Short Links</h3>
                <p>Easy to share shortened URLs</p>
            </div>
        </div>

        <div class="api-docs">
            <h2>API Endpoints</h2>
            <div class="endpoint"><span class="method post">POST</span> /api/user/register</div>
            <div class="endpoint"><span class="method post">POST</span> /api/user/login</div>
            <div class="endpoint"><span class="method post">POST</span> /api/user/logout</div>
            <div class="endpoint"><span class="method get">GET</span> /api/user/profile</div>
            <div class="endpoint"><span class="method post">POST</span> /api/links</div>
            <div class="endpoint"><span class="method get">GET</span> /api/links</div>
            <div class="endpoint"><span class="method get">GET</span> /api/links/{id}</div>
            <div class="endpoint"><span class="method put">PUT</span> /api/links/{id}</div>
            <div class="endpoint"><span class="method delete">DELETE</span> /api/links/{id}</div>
            <div class="endpoint"><span class="method get">GET</span> /l/{short_code}</div>
        </div>
    </div>
</body>
</html>
    <?php
} else {
    // 404 Not Found
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Not Found</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .container { text-align: center; }
        h1 { font-size: 6rem; color: #667eea; margin: 0; }
        p { color: #666; font-size: 1.25rem; }
        a { color: #667eea; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <p>Page not found. <a href="/">Go home</a></p>
    </div>
</body>
</html>
    <?php
}
