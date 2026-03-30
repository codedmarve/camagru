<?php

/**
 * CSRF (Cross-Site Request Forgery) Protection Helper
 *
 * Generates and validates tokens to prevent CSRF attacks.
 * Tokens are stored in the session and must match on POST requests.
 */
class Csrf
{
    private const TOKEN_NAME = 'csrf_token';

    /**
     * Generate a new CSRF token and store it in the session
     * If a token already exists, return the existing one
     */
    public static function generate(): string
    {
        if (empty($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_NAME];
    }

    /**
     * Get the current CSRF token
     */
    public static function getToken(): string
    {
        return self::generate();
    }

    /**
     * Generate a hidden input field with the CSRF token
     */
    public static function input(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Validate a CSRF token from POST data or headers
     * Checks both form data and X-CSRF-Token header (for AJAX)
     */
    public static function validate(): bool
    {
        $sessionToken = $_SESSION[self::TOKEN_NAME] ?? '';

        if (empty($sessionToken)) {
            return false;
        }

        // Check form data first
        $formToken = $_POST[self::TOKEN_NAME] ?? '';
        if (!empty($formToken) && hash_equals($sessionToken, $formToken)) {
            return true;
        }

        // Check header (for AJAX requests)
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!empty($headerToken) && hash_equals($sessionToken, $headerToken)) {
            return true;
        }

        return false;
    }

    /**
     * Validate and abort with error if invalid
     * For use in controllers - returns JSON error for AJAX, redirects for forms
     */
    public static function validateOrFail(bool $isAjax = false): void
    {
        if (!self::validate()) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid CSRF token. Please refresh and try again.']);
                exit;
            } else {
                $_SESSION['errors'] = ['general' => 'Session expired. Please try again.'];
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
                exit;
            }
        }
    }

    /**
     * Regenerate the CSRF token (call after login to prevent session fixation)
     */
    public static function regenerate(): string
    {
        $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
        return $_SESSION[self::TOKEN_NAME];
    }
}
