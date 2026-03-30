<?php

require_once __DIR__ . '/../../config/database.php';

class Comment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDbConnection();
    }

    /**
     * Create a comment
     * Returns the new comment ID or false on failure
     */
    public function create(int $userId, int $imageId, string $content): int|false
    {
        $stmt = $this->db->prepare(
            "INSERT INTO comments (user_id, image_id, content, created_at)
             VALUES (?, ?, ?, NOW())"
        );

        if ($stmt->execute([$userId, $imageId, $content])) {
            return (int) $this->db->lastInsertId();
        }

        return false;
    }

    /**
     * Delete a comment
     */
    public function delete(int $commentId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Find comment by ID
     */
    public function findById(int $commentId): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.username
             FROM comments c
             JOIN users u ON c.user_id = u.id
             WHERE c.id = ?"
        );
        $stmt->execute([$commentId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user owns a comment
     */
    public function isOwner(int $commentId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM comments WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$commentId, $userId]);

        return $stmt->fetch() !== false;
    }

    /**
     * Get all comments for an image (with usernames)
     * Returns array of comments, newest first
     */
    public function getForImage(int $imageId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.username
             FROM comments c
             JOIN users u ON c.user_id = u.id
             WHERE c.image_id = ?
             ORDER BY c.created_at DESC"
        );
        $stmt->execute([$imageId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get comments for multiple images (efficient for gallery)
     * Returns array: [imageId => [comments...], ...]
     */
    public function getForImages(array $imageIds): array
    {
        if (empty($imageIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($imageIds), '?'));

        $stmt = $this->db->prepare(
            "SELECT c.*, u.username
             FROM comments c
             JOIN users u ON c.user_id = u.id
             WHERE c.image_id IN ($placeholders)
             ORDER BY c.created_at DESC"
        );
        $stmt->execute($imageIds);

        // Group by image_id
        $comments = [];
        foreach ($imageIds as $id) {
            $comments[$id] = [];
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $comments[$row['image_id']][] = $row;
        }

        return $comments;
    }

    /**
     * Count comments for an image
     */
    public function countForImage(int $imageId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM comments WHERE image_id = ?"
        );
        $stmt->execute([$imageId]);

        return (int) $stmt->fetchColumn();
    }
}
