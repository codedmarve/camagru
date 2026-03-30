<?php

require_once __DIR__ . '/../../config/database.php';

class Image
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDbConnection();
    }

    /**
     * Create a new image record
     */
    public function create(int $userId, string $filename): int|false
    {
        $sql = "INSERT INTO images (user_id, filename) VALUES (:user_id, :filename)";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':user_id' => $userId,
            ':filename' => $filename,
        ]);

        return $success ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Find image by ID
     */
    public function findById(int $id): array|false
    {
        $sql = "SELECT i.*, u.username
                FROM images i
                JOIN users u ON i.user_id = u.id
                WHERE i.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    /**
     * Get all images for gallery (paginated)
     */
    public function getAll(int $limit = 5, int $offset = 0): array
    {
        $sql = "SELECT i.*, u.username,
                (SELECT COUNT(*) FROM likes WHERE image_id = i.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE image_id = i.id) as comment_count
                FROM images i
                JOIN users u ON i.user_id = u.id
                ORDER BY i.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get images by user ID
     */
    public function getByUserId(int $userId, int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT * FROM images
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count total images (for pagination)
     */
    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) FROM images";
        $stmt = $this->db->query($sql);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Count user's images
     */
    public function countByUserId(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM images WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Delete an image
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM images WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Check if user owns the image
     */
    public function isOwner(int $imageId, int $userId): bool
    {
        $sql = "SELECT COUNT(*) FROM images WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $imageId, ':user_id' => $userId]);

        return (int)$stmt->fetchColumn() > 0;
    }
}
