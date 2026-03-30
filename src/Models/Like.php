<?php

require_once __DIR__ . '/../../config/database.php';

class Like
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDbConnection();
    }

    /**
     * Add a like
     * Returns true if created, false if already exists
     */
    public function add(int $userId, int $imageId): bool
    {
        // Use INSERT IGNORE to handle duplicate gracefully
        // (The unique_like constraint prevents duplicates)
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO likes (user_id, image_id) VALUES (?, ?)"
        );
        $stmt->execute([$userId, $imageId]);

        // rowCount() returns 1 if inserted, 0 if ignored (duplicate)
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove a like
     */
    public function remove(int $userId, int $imageId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM likes WHERE user_id = ? AND image_id = ?"
        );
        $stmt->execute([$userId, $imageId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Toggle like status
     * Returns ['liked' => true/false, 'count' => newCount]
     */
    public function toggle(int $userId, int $imageId): array
    {
        if ($this->hasLiked($userId, $imageId)) {
            $this->remove($userId, $imageId);
            $liked = false;
        } else {
            $this->add($userId, $imageId);
            $liked = true;
        }

        return [
            'liked' => $liked,
            'count' => $this->countForImage($imageId)
        ];
    }

    /**
     * Check if user has liked an image
     */
    public function hasLiked(int $userId, int $imageId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM likes WHERE user_id = ? AND image_id = ? LIMIT 1"
        );
        $stmt->execute([$userId, $imageId]);

        return $stmt->fetch() !== false;
    }

    /**
     * Count total likes for an image
     */
    public function countForImage(int $imageId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM likes WHERE image_id = ?"
        );
        $stmt->execute([$imageId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get like counts for multiple images at once (efficient for gallery)
     * Returns array: [imageId => count, ...]
     */
    public function countForImages(array $imageIds): array
    {
        if (empty($imageIds)) {
            return [];
        }

        // Create placeholders: ?, ?, ?
        $placeholders = implode(',', array_fill(0, count($imageIds), '?'));

        $stmt = $this->db->prepare(
            "SELECT image_id, COUNT(*) as count
             FROM likes
             WHERE image_id IN ($placeholders)
             GROUP BY image_id"
        );
        $stmt->execute($imageIds);

        // Build result array
        $counts = [];
        foreach ($imageIds as $id) {
            $counts[$id] = 0;  // Default to 0
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $counts[$row['image_id']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Get which images a user has liked (from a list)
     * Returns array of image IDs the user has liked
     */
    public function getUserLikes(int $userId, array $imageIds): array
    {
        if (empty($imageIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($imageIds), '?'));

        $stmt = $this->db->prepare(
            "SELECT image_id FROM likes
             WHERE user_id = ? AND image_id IN ($placeholders)"
        );
        $stmt->execute(array_merge([$userId], $imageIds));

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
