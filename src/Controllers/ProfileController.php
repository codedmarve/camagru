<?php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/Csrf.php';

class ProfileController
{
    private User $user;

    public function __construct()
    {
        $this->user = new User();
    }

    /**
     * Show profile edit form
     */
    public function show(): void
    {
        // Must be logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/login');
            exit;
        }

        // Get current user data
        $user = $this->user->findById($_SESSION['user_id']);

        if (!$user) {
            // User not found (shouldn't happen, but handle it)
            session_destroy();
            header('Location: /auth/login');
            exit;
        }

        require __DIR__ . '/../Views/profile/edit.php';
    }

    /**
     * Handle profile update
     */
    public function update(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /profile');
            exit;
        }

        // Validate CSRF token
        Csrf::validateOrFail();

        $userId = $_SESSION['user_id'];
        $currentUser = $this->user->findById($userId);

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $notifyComments = isset($_POST['notify_comments']) ? 1 : 0;

        $errors = [];

        // Validate username
        if (empty($username)) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors['username'] = 'Username must be 3-50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        } elseif ($username !== $currentUser['username']) {
            // Check if new username is taken
            if ($this->user->findByUsername($username)) {
                $errors['username'] = 'Username is already taken';
            }
        }

        // Validate email
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } elseif ($email !== $currentUser['email']) {
            // Check if new email is taken
            if ($this->user->findByEmail($email)) {
                $errors['email'] = 'Email is already registered';
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['username' => $username, 'email' => $email];
            header('Location: /profile');
            exit;
        }

        // Check if email is being changed
        $emailChanged = strtolower($email) !== strtolower($currentUser['email']);

        // Update username and notification settings (not email)
        $this->user->update($userId, [
            'username' => $username,
            'notify_comments' => $notifyComments,
        ]);

        // Update session username if changed
        $_SESSION['username'] = $username;

        if ($emailChanged) {
            // Set pending email and send verification
            $token = $this->user->setPendingEmail($userId, $email);
            if ($token) {
                $this->sendEmailChangeVerification($email, $token);
                $_SESSION['info'] = 'A verification link has been sent to ' . $email . '. Please check your inbox to confirm the change.';
            } else {
                $_SESSION['errors'] = ['email' => 'Failed to initiate email change. Please try again.'];
            }
        } else {
            $_SESSION['success'] = 'Profile updated successfully';
        }

        header('Location: /profile');
        exit;
    }

    /**
     * Verify email change with token from URL
     */
    public function verifyEmailChange(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $_SESSION['errors'] = ['general' => 'Invalid verification link'];
            header('Location: /profile');
            exit;
        }

        $user = $this->user->findByEmailChangeToken($token);

        if (!$user) {
            $_SESSION['errors'] = ['general' => 'Invalid or expired verification link'];
            header('Location: /auth/login');
            exit;
        }

        // Check if new email is still available
        $existingUser = $this->user->findByEmail($user['pending_email']);
        if ($existingUser && $existingUser['id'] !== $user['id']) {
            $_SESSION['errors'] = ['general' => 'This email is now taken by another user'];
            $this->user->cancelPendingEmail($user['id']);
            header('Location: /profile');
            exit;
        }

        // Confirm the email change
        $this->user->confirmEmailChange($user['id']);

        $_SESSION['success'] = 'Email changed successfully!';
        header('Location: /profile');
        exit;
    }

    /**
     * Send email change verification
     */
    private function sendEmailChangeVerification(string $newEmail, string $token): void
    {
        $verifyUrl = "http://localhost:8080/profile/verify-email?token=" . urlencode($token);

        $subject = "Confirm your new email address";
        $message = "You requested to change your email on Camagru.\n\n";
        $message .= "Click the link below to confirm your new email:\n";
        $message .= $verifyUrl . "\n\n";
        $message .= "If you didn't request this, you can ignore this email.";

        $headers = "From: noreply@camagru.local\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($newEmail, $subject, $message, $headers);
    }

    /**
     * Handle password change
     */
    public function changePassword(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /profile');
            exit;
        }

        // Validate CSRF token
        Csrf::validateOrFail();

        $userId = $_SESSION['user_id'];
        $currentUser = $this->user->findById($userId);

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        // Verify current password
        if (!password_verify($currentPassword, $currentUser['password'])) {
            $errors['current_password'] = 'Current password is incorrect';
        }

        // Validate new password
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $newPassword)) {
            $errors['new_password'] = 'New password must be at least 8 characters and include a letter and a number';
        }

        // Confirm passwords match
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            $_SESSION['password_errors'] = $errors;
            header('Location: /profile');
            exit;
        }

        // Update password
        $this->user->updatePassword($userId, $newPassword);

        $_SESSION['success'] = 'Password changed successfully';
        header('Location: /profile');
        exit;
    }
}
