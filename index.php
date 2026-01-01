<?php
/**
 * Project Linkon - Main Entry Point
 * 
 * This is the main index.php file that runs the application.
 * Simply upload this project to any PHP server and access this file.
 * 
 * Requirements:
 * - PHP 8.0+
 * - PDO extension
 * - OpenSSL extension
 * - MySQL/MariaDB database
 * 
 * Setup:
 * 1. Access this file and follow the setup wizard
 * 2. Or manually: Import database/schema.sql and edit config/config.php
 */

// Get request path
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Remove script name from URI if present (for non-rewrite setups)
$basePath = dirname($scriptName);
if ($basePath !== '/' && $basePath !== '\\') {
    $path = substr($requestUri, strlen($basePath));
} else {
    $path = $requestUri;
}
$path = parse_url($path, PHP_URL_PATH);
$path = '/' . ltrim($path, '/');

// Check if setup is needed
$installedFile = __DIR__ . '/config/.installed';
$needsSetup = !file_exists($installedFile);

// Route based on path
if ($path === '/setup' || ($needsSetup && $path === '/')) {
    // Setup wizard
    require __DIR__ . '/src/setup.php';
} elseif ($needsSetup) {
    // Redirect to setup if not configured
    header('Location: /setup');
    exit;
} elseif (preg_match('/^\/api\/user/', $path)) {
    // User API
    require __DIR__ . '/src/api/user.php';
} elseif (preg_match('/^\/api\/links/', $path)) {
    // Links API
    require __DIR__ . '/src/api/links.php';
} elseif (preg_match('/^\/l\/([a-zA-Z0-9]+)/', $path)) {
    // Public link access
    require __DIR__ . '/public/link.php';
} elseif ($path === '/' || $path === '/index.php') {
    // Homepage - Link generation interface
    require __DIR__ . '/src/app.php';
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
