<?php

// Start session for all requests
session_start();

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
