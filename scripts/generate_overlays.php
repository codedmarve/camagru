<?php
/**
 * Generate sample overlay images for testing
 * Run: php scripts/generate_overlays.php
 */

$overlayDir = __DIR__ . '/../overlays/';

// Ensure directory exists
if (!is_dir($overlayDir)) {
    mkdir($overlayDir, 0755, true);
}

// Create a simple frame overlay
function createFrameOverlay(string $path, int $width, int $height, array $color, int $borderWidth = 20): void
{
    $img = imagecreatetruecolor($width, $height);

    // Enable alpha blending
    imagesavealpha($img, true);
    imagealphablending($img, false);

    // Transparent background
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    // Frame color
    $frameColor = imagecolorallocate($img, $color[0], $color[1], $color[2]);

    // Draw frame (4 rectangles for borders)
    // Top
    imagefilledrectangle($img, 0, 0, $width, $borderWidth, $frameColor);
    // Bottom
    imagefilledrectangle($img, 0, $height - $borderWidth, $width, $height, $frameColor);
    // Left
    imagefilledrectangle($img, 0, 0, $borderWidth, $height, $frameColor);
    // Right
    imagefilledrectangle($img, $width - $borderWidth, 0, $width, $height, $frameColor);

    imagepng($img, $path);
    imagedestroy($img);

    echo "Created: $path\n";
}

// Create a corner decoration overlay
function createCornerOverlay(string $path, int $width, int $height, array $color): void
{
    $img = imagecreatetruecolor($width, $height);

    imagesavealpha($img, true);
    imagealphablending($img, false);

    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    $cornerColor = imagecolorallocate($img, $color[0], $color[1], $color[2]);

    $cornerSize = 60;

    // Top-left corner
    imagefilledellipse($img, 0, 0, $cornerSize * 2, $cornerSize * 2, $cornerColor);

    // Top-right corner
    imagefilledellipse($img, $width, 0, $cornerSize * 2, $cornerSize * 2, $cornerColor);

    // Bottom-left corner
    imagefilledellipse($img, 0, $height, $cornerSize * 2, $cornerSize * 2, $cornerColor);

    // Bottom-right corner
    imagefilledellipse($img, $width, $height, $cornerSize * 2, $cornerSize * 2, $cornerColor);

    imagepng($img, $path);
    imagedestroy($img);

    echo "Created: $path\n";
}

// Create a stars overlay
function createStarsOverlay(string $path, int $width, int $height): void
{
    $img = imagecreatetruecolor($width, $height);

    imagesavealpha($img, true);
    imagealphablending($img, true);

    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    $yellow = imagecolorallocate($img, 255, 215, 0);

    // Draw several stars at random positions around the edges
    $starPositions = [
        [30, 30], [100, 20], [$width - 30, 40], [$width - 80, 25],
        [25, $height - 30], [90, $height - 40], [$width - 40, $height - 30], [$width - 100, $height - 20],
        [20, 100], [15, $height - 100], [$width - 20, 90], [$width - 25, $height - 110],
    ];

    foreach ($starPositions as $pos) {
        $size = rand(8, 15);
        // Simple star shape using filled polygon
        imagefilledellipse($img, $pos[0], $pos[1], $size, $size, $yellow);
    }

    imagepng($img, $path);
    imagedestroy($img);

    echo "Created: $path\n";
}

// Create a speech bubble overlay
function createSpeechBubbleOverlay(string $path, int $width, int $height): void
{
    $img = imagecreatetruecolor($width, $height);

    imagesavealpha($img, true);
    imagealphablending($img, true);

    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);

    // Draw speech bubble in top-right corner
    $bubbleX = $width - 150;
    $bubbleY = 30;
    $bubbleW = 130;
    $bubbleH = 60;

    // Bubble outline
    imagefilledellipse($img, $bubbleX + $bubbleW/2, $bubbleY + $bubbleH/2, $bubbleW, $bubbleH, $white);
    imageellipse($img, $bubbleX + $bubbleW/2, $bubbleY + $bubbleH/2, $bubbleW, $bubbleH, $black);

    // Bubble tail
    $points = [
        $bubbleX + 20, $bubbleY + $bubbleH - 10,
        $bubbleX - 10, $bubbleY + $bubbleH + 20,
        $bubbleX + 40, $bubbleY + $bubbleH - 5,
    ];
    imagefilledpolygon($img, $points, 3, $white);

    imagepng($img, $path);
    imagedestroy($img);

    echo "Created: $path\n";
}

// Create a heart frame overlay
function createHeartOverlay(string $path, int $width, int $height): void
{
    $img = imagecreatetruecolor($width, $height);

    imagesavealpha($img, true);
    imagealphablending($img, true);

    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    $red = imagecolorallocate($img, 255, 0, 100);

    // Draw hearts in corners
    $heartPositions = [
        [40, 40], [$width - 40, 40],
        [40, $height - 40], [$width - 40, $height - 40],
        [$width / 2, 30], [$width / 2, $height - 30],
    ];

    foreach ($heartPositions as $pos) {
        // Simple heart using two circles and a triangle
        $size = 15;
        imagefilledellipse($img, $pos[0] - $size/2, $pos[1], $size, $size, $red);
        imagefilledellipse($img, $pos[0] + $size/2, $pos[1], $size, $size, $red);
        $points = [
            $pos[0] - $size, $pos[1],
            $pos[0] + $size, $pos[1],
            $pos[0], $pos[1] + $size * 1.2,
        ];
        imagefilledpolygon($img, $points, 3, $red);
    }

    imagepng($img, $path);
    imagedestroy($img);

    echo "Created: $path\n";
}

// Generate overlays at 640x480 (standard webcam resolution)
$width = 640;
$height = 480;

echo "Generating overlay images...\n\n";

createFrameOverlay($overlayDir . 'frame_gold.png', $width, $height, [218, 165, 32], 25);
createFrameOverlay($overlayDir . 'frame_blue.png', $width, $height, [65, 105, 225], 20);
createFrameOverlay($overlayDir . 'frame_pink.png', $width, $height, [255, 105, 180], 15);
createCornerOverlay($overlayDir . 'corners_purple.png', $width, $height, [138, 43, 226]);
createCornerOverlay($overlayDir . 'corners_green.png', $width, $height, [34, 139, 34]);
createStarsOverlay($overlayDir . 'stars.png', $width, $height);
createSpeechBubbleOverlay($overlayDir . 'speech_bubble.png', $width, $height);
createHeartOverlay($overlayDir . 'hearts.png', $width, $height);

echo "\nDone! Generated " . count(glob($overlayDir . '*.png')) . " overlay images.\n";
