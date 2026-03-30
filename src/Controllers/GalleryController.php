<?php

require_once __DIR__ . '/../Models/Image.php';
require_once __DIR__ . '/../Models/Like.php';
require_once __DIR__ . '/../Models/Comment.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/Csrf.php';

class GalleryController
{
    private Image $image;
    private Like $like;
    private Comment $comment;
    private User $user;

    private const IMAGES_PER_PAGE = 5;

    public function __construct()
    {
        $this->image = new Image();
        $this->like = new Like();
        $this->comment = new Comment();
        $this->user = new User();
    }

    /**
     * Show the gallery (public page)
     */
    public function index(): void
    {
        // Get current page from query string
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::IMAGES_PER_PAGE;

        // Get paginated images
        $images = $this->image->getAll(self::IMAGES_PER_PAGE, $offset);
        $totalImages = $this->image->countAll();
        $totalPages = ceil($totalImages / self::IMAGES_PER_PAGE);

        // Get image IDs for batch queries
        $imageIds = array_column($images, 'id');

        // Get comments for all images
        $comments = $this->comment->getForImages($imageIds);

        // Get user's likes if logged in
        $userLikes = [];
        if (isset($_SESSION['user_id'])) {
            $userLikes = $this->like->getUserLikes($_SESSION['user_id'], $imageIds);
        }

        // Pass data to view
        $data = [
            'images' => $images,
            'comments' => $comments,
            'userLikes' => $userLikes,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'isLoggedIn' => isset($_SESSION['user_id']),
            'currentUserId' => $_SESSION['user_id'] ?? null,
        ];

        require __DIR__ . '/../Views/gallery/index.php';
    }

    /**
     * Toggle like on an image (AJAX)
     */
    public function like(): void
    {
        // Must be logged in
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Please log in to like photos'], 401);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request'], 405);
            return;
        }

        // Validate CSRF token
        Csrf::validateOrFail(true);

        $input = json_decode(file_get_contents('php://input'), true);
        $imageId = (int)($input['image_id'] ?? 0);

        if ($imageId <= 0) {
            $this->jsonResponse(['error' => 'Invalid image'], 400);
            return;
        }

        // Verify image exists
        if (!$this->image->findById($imageId)) {
            $this->jsonResponse(['error' => 'Image not found'], 404);
            return;
        }

        // Toggle the like
        $result = $this->like->toggle($_SESSION['user_id'], $imageId);

        $this->jsonResponse([
            'success' => true,
            'liked' => $result['liked'],
            'count' => $result['count']
        ]);
    }

    /**
     * Add a comment (AJAX)
     */
    public function comment(): void
    {
        // Must be logged in
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Please log in to comment'], 401);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request'], 405);
            return;
        }

        // Validate CSRF token
        Csrf::validateOrFail(true);

        $input = json_decode(file_get_contents('php://input'), true);
        $imageId = (int)($input['image_id'] ?? 0);
        $content = trim($input['content'] ?? '');

        // Validate
        if ($imageId <= 0) {
            $this->jsonResponse(['error' => 'Invalid image'], 400);
            return;
        }

        if (empty($content)) {
            $this->jsonResponse(['error' => 'Comment cannot be empty'], 400);
            return;
        }

        if (strlen($content) > 1000) {
            $this->jsonResponse(['error' => 'Comment is too long (max 1000 characters)'], 400);
            return;
        }

        // Verify image exists and get owner info
        $image = $this->image->findById($imageId);
        if (!$image) {
            $this->jsonResponse(['error' => 'Image not found'], 404);
            return;
        }

        // Create the comment
        $commentId = $this->comment->create($_SESSION['user_id'], $imageId, $content);

        if ($commentId === false) {
            $this->jsonResponse(['error' => 'Failed to add comment'], 500);
            return;
        }

        // Send email notification to image owner (if enabled and not self-comment)
        if ($image['user_id'] !== $_SESSION['user_id']) {
            $this->sendCommentNotification($image, $content);
        }

        $this->jsonResponse([
            'success' => true,
            'comment' => [
                'id' => $commentId,
                'content' => htmlspecialchars($content),
                'username' => $_SESSION['username'],
                'user_id' => $_SESSION['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Delete a comment (AJAX)
     */
    public function deleteComment(): void
    {
        // Must be logged in
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request'], 405);
            return;
        }

        // Validate CSRF token
        Csrf::validateOrFail(true);

        $input = json_decode(file_get_contents('php://input'), true);
        $commentId = (int)($input['comment_id'] ?? 0);

        if ($commentId <= 0) {
            $this->jsonResponse(['error' => 'Invalid comment'], 400);
            return;
        }

        // Check ownership
        if (!$this->comment->isOwner($commentId, $_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Not authorized'], 403);
            return;
        }

        // Delete
        if ($this->comment->delete($commentId)) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['error' => 'Failed to delete comment'], 500);
        }
    }

    /**
     * Send email notification for new comment
     */
    private function sendCommentNotification(array $image, string $commentContent): void
    {
        // Get image owner
        $owner = $this->user->findById($image['user_id']);

        // Check if notifications are enabled (default: on)
        // notify_comments defaults to 1 (true) in the database
        if (!$owner || !$owner['notify_comments']) {
            return;
        }

        $commenterName = $_SESSION['username'];
        $subject = "New comment on your Camagru photo";
        $message = "Hi {$owner['username']},\n\n";
        $message .= "{$commenterName} commented on your photo:\n\n";
        $message .= "\"{$commentContent}\"\n\n";
        $message .= "View it at: http://localhost:8080/gallery\n\n";
        $message .= "To disable notifications, update your profile settings.";

        $headers = "From: noreply@camagru.local\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($owner['email'], $subject, $message, $headers);
    }

    /**
     * Send JSON response
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
