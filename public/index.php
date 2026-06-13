<?php

// Start session for all requests
session_start();

// --- Session expiry (idle + absolute timeout) ---
// PHP's built-in session garbage collection is probabilistic and often disabled
// on Debian-based images, so the login session is expired here at the app level.
// It ends after some inactivity (idle) or a hard maximum since login (absolute),
// not only when the user clicks logout.
$sessionIdleTimeout     = 1800;   // 30 minutes since the last request
$sessionAbsoluteTimeout = 43200;  // 12 hours since login, even while active

if (isset($_SESSION['user_id'])) {
    $now = time();
    $idleExpired = isset($_SESSION['last_activity'])
        && ($now - $_SESSION['last_activity']) > $sessionIdleTimeout;
    $absoluteExpired = isset($_SESSION['created_at'])
        && ($now - $_SESSION['created_at']) > $sessionAbsoluteTimeout;

    if ($idleExpired || $absoluteExpired) {
        // Clear all data, then rotate to a fresh session id so the expired one
        // can't be reused. Regenerating (rather than destroying) keeps a valid
        // cookie, so the "session expired" message survives to the login page.
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['errors'] = ['general' => 'Your session expired. Please log in again.'];
    } else {
        // Active session: record this request's time (and backfill timestamps
        // for sessions that predate this feature).
        $_SESSION['last_activity'] = $now;
        $_SESSION['created_at'] ??= $now;
    }
}

// Load CSRF helper and generate token for all requests
require_once __DIR__ . '/../src/Helpers/Csrf.php';
Csrf::generate();

// Get the request URI and method
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove trailing slash (except for root)
$uri = $uri !== '/' ? rtrim($uri, '/') : $uri;

// Define routes: [HTTP_METHOD][URI] => [Controller, Method]
$routes = [
    'GET' => [
        '/' => ['HomeController', 'index'],
        '/gallery' => ['GalleryController', 'index'],
        '/gallery/feed' => ['GalleryController', 'feed'],
        '/auth/login' => ['AuthController', 'showLogin'],
        '/auth/register' => ['AuthController', 'showRegister'],
        '/auth/logout' => ['AuthController', 'logout'],
        '/auth/verify' => ['AuthController', 'verify'],
        '/auth/forgot-password' => ['AuthController', 'showForgotPassword'],
        '/auth/reset-password' => ['AuthController', 'showResetPassword'],
        '/profile' => ['ProfileController', 'show'],
        '/profile/verify-email' => ['ProfileController', 'verifyEmailChange'],
        '/editor' => ['EditorController', 'index'],
    ],
    'POST' => [
        '/auth/login' => ['AuthController', 'login'],
        '/auth/register' => ['AuthController', 'register'],
        '/auth/forgot-password' => ['AuthController', 'forgotPassword'],
        '/auth/reset-password' => ['AuthController', 'resetPassword'],
        '/profile/update' => ['ProfileController', 'update'],
        '/profile/password' => ['ProfileController', 'changePassword'],
        '/editor/capture' => ['EditorController', 'capture'],
        '/editor/upload' => ['EditorController', 'upload'],
        '/editor/delete' => ['EditorController', 'delete'],
        '/gallery/like' => ['GalleryController', 'like'],
        '/gallery/comment' => ['GalleryController', 'comment'],
        '/gallery/comment/delete' => ['GalleryController', 'deleteComment'],
    ],
];

// Find matching route
if (isset($routes[$method][$uri])) {
    [$controller, $action] = $routes[$method][$uri];

    // Load the controller
    $controllerFile = __DIR__ . "/../src/Controllers/{$controller}.php";

    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        $controllerInstance = new $controller();
        $controllerInstance->$action();
    } else {
        http_response_code(500);
        echo "Controller not found: {$controller}";
    }
} else {
    // No route found - 404
    http_response_code(404);
    echo "<h1>404 - Page Not Found</h1>";
    echo "<p>The page <code>" . htmlspecialchars($uri) . "</code> does not exist.</p>";
    echo "<p><a href='/'>Go to homepage</a></p>";
}
