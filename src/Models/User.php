<?php

require_once __DIR__ . '/../../config/database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDbConnection();
    }

    /**
     * Create a new user
     * Returns the new user's ID, or false on failure
     */
    public function create(string $username, string $email, string $password): int|false
    {
        // Hash the password (NEVER store plain text!)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Generate verification token for email confirmation
        $verificationToken = bin2hex(random_bytes(32));

        $sql = "INSERT INTO users (username, email, password, verification_token)
                VALUES (:username, :email, :password, :token)";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':token' => $verificationToken,
        ]);

        return $success ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Find user by email (for login)
     */
    public function findByEmail(string $email): array|false
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch();
    }

    /**
     * Find user by username (to check if taken)
     */
    public function findByUsername(string $username): array|false
    {
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);

        return $stmt->fetch();
    }

    /**
     * Find user by verification token
     */
    public function findByVerificationToken(string $token): array|false
    {
        $sql = "SELECT * FROM users WHERE verification_token = :token";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);

        return $stmt->fetch();
    }

    /**
     * Mark user as verified and clear the token
     */
    public function verify(int $userId): bool
    {
        $sql = "UPDATE users SET is_verified = TRUE, verification_token = NULL
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $userId]);
    }

    /**
     * Set password reset token (expires in 1 hour)
     */
    public function setResetToken(int $userId): string|false
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sql = "UPDATE users SET reset_token = :token, reset_token_expires = :expires
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':token' => $token,
            ':expires' => $expires,
            ':id' => $userId,
        ]);

        return $success ? $token : false;
    }

    /**
     * Find user by valid (non-expired) reset token
     */
    public function findByResetToken(string $token): array|false
    {
        $sql = "SELECT * FROM users
                WHERE reset_token = :token
                AND reset_token_expires > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);

        return $stmt->fetch();
    }

    /**
     * Update password and clear reset token
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE users SET password = :password,
                reset_token = NULL, reset_token_expires = NULL
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId,
        ]);
    }

    /**
     * Update user profile
     */
    public function update(int $userId, array $data): bool
    {
        $allowed = ['username', 'email', 'notify_comments'];
        $fields = [];
        $params = [':id' => $userId];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): array|false
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    /**
     * Set pending email change (requires verification)
     */
    public function setPendingEmail(int $userId, string $newEmail): string|false
    {
        $token = bin2hex(random_bytes(32));

        $sql = "UPDATE users SET pending_email = :email, email_change_token = :token
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':email' => $newEmail,
            ':token' => $token,
            ':id' => $userId,
        ]);

        return $success ? $token : false;
    }

    /**
     * Find user by email change token
     */
    public function findByEmailChangeToken(string $token): array|false
    {
        $sql = "SELECT * FROM users WHERE email_change_token = :token";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);

        return $stmt->fetch();
    }

    /**
     * Confirm email change (move pending_email to email)
     */
    public function confirmEmailChange(int $userId): bool
    {
        $sql = "UPDATE users SET email = pending_email,
                pending_email = NULL, email_change_token = NULL
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $userId]);
    }

    /**
     * Cancel pending email change
     */
    public function cancelPendingEmail(int $userId): bool
    {
        $sql = "UPDATE users SET pending_email = NULL, email_change_token = NULL
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $userId]);
    }
}
