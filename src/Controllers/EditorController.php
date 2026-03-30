<?php

require_once __DIR__ . '/../Models/Image.php';
require_once __DIR__ . '/../Helpers/Csrf.php';

class EditorController
{
    private Image $image;

    public function __construct()
    {
        $this->image = new Image();
    }

    /**
     * Show the editor page
     */
    public function index(): void
    {
        // Must be logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/login');
            exit;
        }

        // Get user's recent images for sidebar
        $userImages = $this->image->getByUserId($_SESSION['user_id'], 10);

        // Get available overlays
        $overlays = $this->getOverlays();

        require __DIR__ . '/../Views/editor/index.php';
    }

    /**
     * Handle image capture and save
     */
    public function capture(): void
    {
        // Must be logged in
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        // Must be POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method'], 405);
            return;
        }

        // Validate CSRF token
        Csrf::validateOrFail(true);

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->jsonResponse(['error' => 'Invalid JSON input'], 400);
            return;
        }

        $imageData = $input['image'] ?? '';
        $overlayId = $input['overlay'] ?? '';

        // Validate image data (base64 encoded)
        if (empty($imageData) || !preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $imageData)) {
            $this->jsonResponse(['error' => 'Invalid image data'], 400);
            return;
        }

        // Validate overlay
        $overlays = $this->getOverlays();
        $overlayFile = null;
        foreach ($overlays as $overlay) {
            if ($overlay['id'] === $overlayId) {
                $overlayFile = $overlay['path'];
                break;
            }
        }

        if (!$overlayFile || !file_exists($overlayFile)) {
            $this->jsonResponse(['error' => 'Invalid overlay selected'], 400);
            return;
        }

        // Process and save the image
        $result = $this->processImage($imageData, $overlayFile);

        if ($result === false) {
            $this->jsonResponse(['error' => 'Failed to process image'], 500);
            return;
        }

        // Save to database
        $imageId = $this->image->create($_SESSION['user_id'], $result['filename']);

        if ($imageId === false) {
            // Clean up the file if database insert fails
            unlink($result['path']);
            $this->jsonResponse(['error' => 'Failed to save image'], 500);
            return;
        }

        $this->jsonResponse([
            'success' => true,
            'image' => [
                'id' => $imageId,
                'filename' => $result['filename'],
                'url' => '/uploads/' . $result['filename'],
            ],
        ]);
    }

    /**
     * Handle image upload (alternative to webcam)
     */
    public function upload(): void
    {
        // Must be logged in
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method'], 405);
            return;
        }

        // Validate CSRF token
        Csrf::validateOrFail(true);

        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'No image uploaded'], 400);
            return;
        }

        $overlayId = $_POST['overlay'] ?? '';

        // Validate overlay
        $overlays = $this->getOverlays();
        $overlayFile = null;
        foreach ($overlays as $overlay) {
            if ($overlay['id'] === $overlayId) {
                $overlayFile = $overlay['path'];
                break;
            }
        }

        if (!$overlayFile || !file_exists($overlayFile)) {
            $this->jsonResponse(['error' => 'Invalid overlay selected'], 400);
            return;
        }

        // Validate uploaded file
        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $this->jsonResponse(['error' => 'Invalid file type. Only JPEG and PNG allowed.'], 400);
            return;
        }

        // Max file size: 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            $this->jsonResponse(['error' => 'File too large. Maximum 5MB allowed.'], 400);
            return;
        }

        // Read uploaded file as base64
        $imageData = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($file['tmp_name']));

        // Process and save the image
        $result = $this->processImage($imageData, $overlayFile);

        if ($result === false) {
            $this->jsonResponse(['error' => 'Failed to process image'], 500);
            return;
        }

        // Save to database
        $imageId = $this->image->create($_SESSION['user_id'], $result['filename']);

        if ($imageId === false) {
            unlink($result['path']);
            $this->jsonResponse(['error' => 'Failed to save image'], 500);
            return;
        }

        $this->jsonResponse([
            'success' => true,
            'image' => [
                'id' => $imageId,
                'filename' => $result['filename'],
                'url' => '/uploads/' . $result['filename'],
            ],
        ]);
    }

    /**
     * Delete an image
     */
    public function delete(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method'], 405);
            return;
        }

        // Validate CSRF token
        Csrf::validateOrFail(true);

        $input = json_decode(file_get_contents('php://input'), true);
        $imageId = (int)($input['id'] ?? 0);

        if ($imageId <= 0) {
            $this->jsonResponse(['error' => 'Invalid image ID'], 400);
            return;
        }

        // Check ownership
        if (!$this->image->isOwner($imageId, $_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Not authorized'], 403);
            return;
        }

        // Get image info for file deletion
        $image = $this->image->findById($imageId);

        if (!$image) {
            $this->jsonResponse(['error' => 'Image not found'], 404);
            return;
        }

        // Delete from database
        if ($this->image->delete($imageId)) {
            // Delete file
            $filePath = __DIR__ . '/../../uploads/' . $image['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['error' => 'Failed to delete image'], 500);
        }
    }

    /**
     * Get available overlay images
     */
    private function getOverlays(): array
    {
        $overlayDir = __DIR__ . '/../../overlays/';
        $overlays = [];
        $noneOverlay = null;

        if (is_dir($overlayDir)) {
            $files = glob($overlayDir . '*.png');
            foreach ($files as $file) {
                $filename = basename($file);
                $id = pathinfo($filename, PATHINFO_FILENAME);

                $overlay = [
                    'id' => $id,
                    'name' => $id === 'none' ? 'None (No Overlay)' : ucwords(str_replace(['_', '-'], ' ', $id)),
                    'path' => $file,
                    'url' => '/overlays/' . $filename,
                ];

                // Keep "none" separate to put it first
                if ($id === 'none') {
                    $noneOverlay = $overlay;
                } else {
                    $overlays[] = $overlay;
                }
            }
        }

        // Put "none" first if it exists
        if ($noneOverlay) {
            array_unshift($overlays, $noneOverlay);
        }

        return $overlays;
    }

    /**
     * Process image: combine webcam capture with overlay
     */
    private function processImage(string $imageData, string $overlayPath): array|false
    {
        // Remove data URL prefix
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
        $imageData = base64_decode($imageData);

        if ($imageData === false) {
            return false;
        }

        // Create image from webcam capture
        $webcamImage = imagecreatefromstring($imageData);
        if ($webcamImage === false) {
            return false;
        }

        // Load overlay (PNG with transparency)
        $overlay = imagecreatefrompng($overlayPath);
        if ($overlay === false) {
            imagedestroy($webcamImage);
            return false;
        }

        // Get dimensions
        $webcamWidth = imagesx($webcamImage);
        $webcamHeight = imagesy($webcamImage);
        $overlayWidth = imagesx($overlay);
        $overlayHeight = imagesy($overlay);

        // Create final image canvas
        $finalImage = imagecreatetruecolor($webcamWidth, $webcamHeight);

        // Preserve transparency
        imagealphablending($finalImage, true);
        imagesavealpha($finalImage, true);

        // Copy webcam image to canvas
        imagecopy($finalImage, $webcamImage, 0, 0, 0, 0, $webcamWidth, $webcamHeight);

        // Scale overlay to fit webcam image while maintaining aspect ratio
        $scale = min($webcamWidth / $overlayWidth, $webcamHeight / $overlayHeight);
        $newOverlayWidth = (int)($overlayWidth * $scale);
        $newOverlayHeight = (int)($overlayHeight * $scale);

        // Center the overlay
        $overlayX = (int)(($webcamWidth - $newOverlayWidth) / 2);
        $overlayY = (int)(($webcamHeight - $newOverlayHeight) / 2);

        // Composite overlay onto final image with transparency
        imagecopyresampled(
            $finalImage,
            $overlay,
            $overlayX,
            $overlayY,
            0,
            0,
            $newOverlayWidth,
            $newOverlayHeight,
            $overlayWidth,
            $overlayHeight
        );

        // Generate unique filename
        $filename = uniqid('img_', true) . '.png';
        $filepath = __DIR__ . '/../../uploads/' . $filename;

        // Save the final image
        $success = imagepng($finalImage, $filepath);

        // Clean up
        imagedestroy($webcamImage);
        imagedestroy($overlay);
        imagedestroy($finalImage);

        if (!$success) {
            return false;
        }

        return [
            'filename' => $filename,
            'path' => $filepath,
        ];
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
