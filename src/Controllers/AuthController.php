<?php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/Csrf.php';

class AuthController
{
    private User $user;

    public function __construct()
    {
        $this->user = new User();
    }

    /**
     * Show registration form
     */
    public function showRegister(): void
    {
        require __DIR__ . '/../Views/auth/register.php';
    }

    /**
     * Handle registration form submission
     */
    public function register(): void
    {
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /auth/register');
            exit;
        }

        // Validate CSRF token
        Csrf::validateOrFail();

        // Get form data and trim whitespace
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validate inputs
        $errors = $this->validateRegistration($username, $email, $password, $passwordConfirm);

        if (!empty($errors)) {
            // Store errors and old input in session to display on form
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['username' => $username, 'email' => $email];
            header('Location: /auth/register');
            exit;
        }

        // Check if username or email already exists
        if ($this->user->findByUsername($username)) {
            $_SESSION['errors'] = ['username' => 'Username is already taken'];
            $_SESSION['old'] = ['username' => $username, 'email' => $email];
            header('Location: /auth/register');
            exit;
        }

        if ($this->user->findByEmail($email)) {
            $_SESSION['errors'] = ['email' => 'Email is already registered'];
            $_SESSION['old'] = ['username' => $username, 'email' => $email];
            header('Location: /auth/register');
            exit;
        }

        // Create the user
        $userId = $this->user->create($username, $email, $password);

        if ($userId === false) {
            $_SESSION['errors'] = ['general' => 'Registration failed. Please try again.'];
            $_SESSION['old'] = ['username' => $username, 'email' => $email];
            header('Location: /auth/register');
            exit;
        }

        // Get the user to retrieve verification token
        $newUser = $this->user->findById($userId);

        // Send verification email
        $this->sendVerificationEmail($email, $newUser['verification_token']);

        // Show success message
        $_SESSION['success'] = 'Registration successful! Please check your email to verify your account.';
        header('Location: /auth/login');
        exit;
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        require __DIR__ . '/../Views/auth/login.php';
    }

    /**
     * Handle login form submission
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /auth/login');
            exit;
        }

        // Validate CSRF token
        Csrf::validateOrFail();

        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        // Find user by email or username
        $user = $this->user->findByEmail($login);
        if (!$user) {
            $user = $this->user->findByUsername($login);
        }

        // Verify password using password_verify (compares against hash)
        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['errors'] = ['general' => 'Invalid credentials'];
            $_SESSION['old'] = ['login' => $login];
            header('Location: /auth/login');
            exit;
        }

        // Check if email is verified
        if (!$user['is_verified']) {
            $_SESSION['errors'] = ['general' => 'Please verify your email before logging in'];
            $_SESSION['old'] = ['login' => $login];
            header('Location: /auth/login');
            exit;
        }

        // Start session and store user info
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Regenerate CSRF token after login to prevent session fixation
        Csrf::regenerate();

        header('Location: /');
        exit;
    }

    /**
     * Log out the user
     */
    public function logout(): void
    {
        // Clear all session data
        $_SESSION = [];

        // Destroy the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy the session
        session_destroy();

        header('Location: /');
        exit;
    }

    /**
     * Verify email with token from URL
     */
    public function verify(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $_SESSION['errors'] = ['general' => 'Invalid verification link'];
            header('Location: /auth/login');
            exit;
        }

        $user = $this->user->findByVerificationToken($token);

        if (!$user) {
            $_SESSION['errors'] = ['general' => 'Invalid or expired verification link'];
            header('Location: /auth/login');
            exit;
        }

        // Mark user as verified
        $this->user->verify($user['id']);

        $_SESSION['success'] = 'Email verified successfully! You can now log in.';
        header('Location: /auth/login');
        exit;
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword(): void
    {
        require __DIR__ . '/../Views/auth/forgot-password.php';
    }

    /**
     * Handle forgot password form submission
     */
    public function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /auth/forgot-password');
            exit;
        }

        // Validate CSRF token
        Csrf::validateOrFail();

        $email = trim($_POST['email'] ?? '');

        // Always show success message (don't reveal if email exists)
        $_SESSION['success'] = 'If that email exists, a reset link has been sent.';

        $user = $this->user->findByEmail($email);

        if ($user) {
            $token = $this->user->setResetToken($user['id']);
            if ($token) {
                $this->sendResetEmail($email, $token);
            }
        }

        header('Location: /auth/login');
        exit;
    }

    /**
     * Show reset password form
     */
    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token) || !$this->user->findByResetToken($token)) {
            $_SESSION['errors'] = ['general' => 'Invalid or expired reset link'];
            header('Location: /auth/login');
            exit;
        }

        require __DIR__ . '/../Views/auth/reset-password.php';
    }

    /**
     * Handle reset password form submission
     */
    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /auth/login');
            exit;
        }

        // Validate CSRF token
        Csrf::validateOrFail();

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $user = $this->user->findByResetToken($token);

        if (!$user) {
            $_SESSION['errors'] = ['general' => 'Invalid or expired reset link'];
            header('Location: /auth/login');
            exit;
        }

        // Validate password
        $errors = [];
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)) {
            $errors['password'] = 'Password must be at least 8 characters and include a letter and a number';
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /auth/reset-password?token=' . urlencode($token));
            exit;
        }

        // Update password
        $this->user->updatePassword($user['id'], $password);

        $_SESSION['success'] = 'Password reset successful! You can now log in.';
        header('Location: /auth/login');
        exit;
    }

    /**
     * Validate registration form data
     */
    private function validateRegistration(
        string $username,
        string $email,
        string $password,
        string $passwordConfirm
    ): array {
        $errors = [];

        // Username validation
        if (empty($username)) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors['username'] = 'Username must be 3-50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        }

        // Email validation
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }

        // Password validation
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)) {
            $errors['password'] = 'Password must be at least 8 characters and include a letter and a number';
        }

        // Password confirmation
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match';
        }

        return $errors;
    }

    /**
     * Send verification email to new user
     */
    private function sendVerificationEmail(string $email, string $token): void
    {
        $verifyUrl = "http://localhost:8080/auth/verify?token=" . urlencode($token);

        $subject = "Verify your Camagru account";
        $message = "Welcome to Camagru!\n\n";
        $message .= "Please click the link below to verify your email:\n";
        $message .= $verifyUrl . "\n\n";
        $message .= "If you didn't create this account, you can ignore this email.";

        $headers = "From: noreply@camagru.local\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($email, $subject, $message, $headers);
    }

    /**
     * Send password reset email
     */
    private function sendResetEmail(string $email, string $token): void
    {
        $resetUrl = "http://localhost:8080/auth/reset-password?token=" . urlencode($token);

        $subject = "Reset your Camagru password";
        $message = "You requested a password reset.\n\n";
        $message .= "Click the link below to reset your password:\n";
        $message .= $resetUrl . "\n\n";
        $message .= "This link expires in 1 hour.\n\n";
        $message .= "If you didn't request this, you can ignore this email.";

        $headers = "From: noreply@camagru.local\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($email, $subject, $message, $headers);
    }
}
