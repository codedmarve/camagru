# Editor Flow Documentation

This document explains the complete flow from loading the Editor to saving a photo.

---

## Step 1: Page Load

When you visit `/editor`, the router calls `EditorController::index()`:

```php
// EditorController.php lines 17-32
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
```

**What happens:**
1. Checks if user is logged in (redirects to login if not)
2. Fetches user's existing photos for the sidebar
3. Scans the `/overlays` directory for PNG files
4. Passes `$userImages` and `$overlays` to the view

---

## Step 2: Frontend Initialization

When the page loads, JavaScript runs after `DOMContentLoaded`:

```javascript
// editor/index.php lines 121-141
document.addEventListener('DOMContentLoaded', function() {
    // Grab all DOM elements we'll need
    const webcamVideo = document.getElementById('webcam');
    const overlayPreview = document.getElementById('overlay-preview');
    // ... more elements

    // State variables
    let currentMode = 'webcam';      // 'webcam' or 'upload'
    let selectedOverlay = null;       // Which overlay is selected
    let webcamStream = null;          // MediaStream object
    let uploadedImageData = null;     // Base64 data from file upload

    // Try to start webcam
    initWebcam();
});
```

---

## Step 3: Webcam Initialization

```javascript
// lines 144-160
async function initWebcam() {
    try {
        // Request camera access from browser
        webcamStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
            audio: false
        });
        // Attach stream to <video> element
        webcamVideo.srcObject = webcamStream;
        webcamVideo.classList.remove('hidden');
    } catch (error) {
        // Camera failed - switch to upload mode
        console.error('Webcam error:', error);
        webcamVideo.classList.add('hidden');
        noWebcamMsg.classList.remove('hidden');
        switchToUploadMode();  // Fallback!
    }
}
```

**Key concepts:**
- `navigator.mediaDevices.getUserMedia()` - Browser API to access camera
- `facingMode: 'user'` - Front camera on mobile (selfie mode)
- The video stream is assigned to `<video>` element's `srcObject`
- If it fails, we gracefully fall back to upload mode

### HTTPS Requirements for Camera Access

The `getUserMedia()` API requires a **secure context**:

| Access Method | Desktop | Mobile |
|---------------|---------|--------|
| `http://localhost:8080` | Works | N/A |
| `http://127.0.0.1:8080` | Works | N/A |
| `http://192.168.x.x:8080` | Fails | Fails |
| `https://...` | Works | Works |

When accessing from a mobile phone via IP address, camera access is blocked. This is why the upload fallback exists.

---

## Step 4: Mode Switching

Users can toggle between webcam and upload:

```javascript
// lines 186-197
function switchToUploadMode() {
    currentMode = 'upload';

    // Update button styles (visual feedback)
    btnUploadMode.classList.add('bg-indigo-600', 'text-white');
    btnWebcamMode.classList.add('bg-gray-200', 'text-gray-700');

    // Show file input, hide webcam
    uploadSection.classList.remove('hidden');
    webcamVideo.classList.add('hidden');

    stopWebcam();  // Release camera resource
    updateCaptureButton();
}
```

**For file uploads**, when user selects a file:

```javascript
// lines 200-212
fileInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        // Convert file to base64 data URL
        uploadedImageData = e.target.result;  // "data:image/png;base64,..."
        uploadPreview.src = uploadedImageData;
        uploadPreview.classList.remove('hidden');
        updateCaptureButton();
    };
    reader.readAsDataURL(file);  // Triggers onload when done
});
```

**Key concept:** `FileReader.readAsDataURL()` converts the file to a base64-encoded string that can be:
- Displayed in an `<img>` tag immediately
- Sent to the server as text (no multipart form needed)

---

## Step 5: Overlay Selection

When user clicks an overlay thumbnail:

```javascript
// lines 215-230
overlayBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        // Remove selection from all overlays
        overlayBtns.forEach(b => b.classList.remove('border-indigo-600'));

        // Select this one (visual highlight)
        this.classList.add('border-indigo-600');

        // Store selection
        selectedOverlay = {
            id: this.dataset.overlayId,    // e.g., "hearts"
            url: this.dataset.overlayUrl   // e.g., "/overlays/hearts.png"
        };

        // Show overlay preview on top of webcam/image
        overlayPreview.src = selectedOverlay.url;
        overlayPreview.classList.remove('hidden');

        updateCaptureButton();  // Enable capture if ready
    });
});
```

**The overlay preview** is positioned absolutely on top of the video:

```html
<!-- line 19 -->
<img id="overlay-preview"
     class="absolute inset-0 w-full h-full object-contain pointer-events-none hidden">
```

- `absolute inset-0` - Covers the entire parent container
- `pointer-events-none` - Clicks pass through to elements below
- This gives a "live preview" effect

---

## Step 6: Capture Button Click

When user clicks "Capture Photo":

```javascript
// lines 248-298
btnCapture.addEventListener('click', async function() {
    if (!selectedOverlay) return;

    let imageData;

    if (currentMode === 'webcam') {
        // CAPTURE FROM WEBCAM using Canvas
        captureCanvas.width = webcamVideo.videoWidth;
        captureCanvas.height = webcamVideo.videoHeight;
        const ctx = captureCanvas.getContext('2d');
        ctx.drawImage(webcamVideo, 0, 0);  // Draw current video frame
        imageData = captureCanvas.toDataURL('image/png');  // Convert to base64
    } else {
        // USE UPLOADED IMAGE (already base64)
        imageData = uploadedImageData;
    }

    // Show loading state
    btnCapture.disabled = true;
    btnCapture.textContent = 'Processing...';

    // SEND TO SERVER
    const response = await fetch('/editor/capture', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            image: imageData,        // Base64 image data
            overlay: selectedOverlay.id  // e.g., "hearts"
        })
    });

    const result = await response.json();

    if (result.success) {
        showStatus('Photo saved!', 'success');
        addPhotoToSidebar(result.image);  // Update UI immediately
    } else {
        showStatus(result.error || 'Failed to save photo', 'error');
    }
});
```

**Key concepts:**
- **Canvas capture:** `ctx.drawImage(video, 0, 0)` draws the current video frame onto a hidden canvas
- **toDataURL():** Converts canvas content to base64 string
- **fetch() with JSON:** Sends data as JSON body, not form data
- The overlay is NOT composited client-side - only the ID is sent

---

## Step 7: Server-Side Processing

The server receives the request at `EditorController::capture()`:

```php
// EditorController.php lines 37-109
public function capture(): void
{
    // Validate authentication
    if (!isset($_SESSION['user_id'])) {
        $this->jsonResponse(['error' => 'Not authenticated'], 401);
        return;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    $imageData = $input['image'] ?? '';    // Base64 image
    $overlayId = $input['overlay'] ?? '';  // e.g., "hearts"

    // Validate image format
    if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $imageData)) {
        $this->jsonResponse(['error' => 'Invalid image data'], 400);
        return;
    }

    // Find the overlay file path
    $overlays = $this->getOverlays();
    $overlayFile = null;
    foreach ($overlays as $overlay) {
        if ($overlay['id'] === $overlayId) {
            $overlayFile = $overlay['path'];  // e.g., "/var/www/overlays/hearts.png"
            break;
        }
    }

    // Process image (THIS IS WHERE THE MAGIC HAPPENS)
    $result = $this->processImage($imageData, $overlayFile);

    // Save to database
    $imageId = $this->image->create($_SESSION['user_id'], $result['filename']);

    // Return success with image info
    $this->jsonResponse([
        'success' => true,
        'image' => [
            'id' => $imageId,
            'url' => '/uploads/' . $result['filename'],
        ],
    ]);
}
```

**Key concept:** `php://input` reads raw POST body (since we sent JSON, not form data).

---

## Step 8: Image Composition (GD Library)

This is the core requirement - **server-side image composition**:

```php
// EditorController.php lines 275-357
private function processImage(string $imageData, string $overlayPath): array|false
{
    // 1. DECODE BASE64 IMAGE
    $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
    $imageData = base64_decode($imageData);

    // 2. CREATE GD IMAGE FROM USER'S PHOTO
    $webcamImage = imagecreatefromstring($imageData);

    // 3. LOAD OVERLAY PNG (with transparency)
    $overlay = imagecreatefrompng($overlayPath);

    // 4. GET DIMENSIONS
    $webcamWidth = imagesx($webcamImage);
    $webcamHeight = imagesy($webcamImage);
    $overlayWidth = imagesx($overlay);
    $overlayHeight = imagesy($overlay);

    // 5. CREATE FINAL CANVAS
    $finalImage = imagecreatetruecolor($webcamWidth, $webcamHeight);
    imagealphablending($finalImage, true);   // Enable alpha blending
    imagesavealpha($finalImage, true);       // Preserve transparency

    // 6. DRAW USER'S PHOTO AS BASE LAYER
    imagecopy($finalImage, $webcamImage, 0, 0, 0, 0, $webcamWidth, $webcamHeight);

    // 7. SCALE OVERLAY TO FIT (maintain aspect ratio)
    $scale = min($webcamWidth / $overlayWidth, $webcamHeight / $overlayHeight);
    $newOverlayWidth = (int)($overlayWidth * $scale);
    $newOverlayHeight = (int)($overlayHeight * $scale);

    // 8. CENTER THE OVERLAY
    $overlayX = (int)(($webcamWidth - $newOverlayWidth) / 2);
    $overlayY = (int)(($webcamHeight - $newOverlayHeight) / 2);

    // 9. COMPOSITE OVERLAY ON TOP (with transparency!)
    imagecopyresampled(
        $finalImage, $overlay,
        $overlayX, $overlayY,           // Destination position
        0, 0,                            // Source position
        $newOverlayWidth, $newOverlayHeight,  // Destination size
        $overlayWidth, $overlayHeight         // Source size
    );

    // 10. SAVE TO FILE
    $filename = uniqid('img_', true) . '.png';
    $filepath = __DIR__ . '/../../uploads/' . $filename;
    imagepng($finalImage, $filepath);

    // 11. CLEANUP MEMORY
    imagedestroy($webcamImage);
    imagedestroy($overlay);
    imagedestroy($finalImage);

    return ['filename' => $filename, 'path' => $filepath];
}
```

### Key GD Functions Reference

| Function | Purpose |
|----------|---------|
| `imagecreatefromstring()` | Create image from binary data |
| `imagecreatefrompng()` | Load PNG with transparency |
| `imagecreatetruecolor()` | Create blank canvas |
| `imagealphablending()` | Enable transparency blending |
| `imagecopy()` | Copy one image onto another |
| `imagecopyresampled()` | Copy with resizing (better quality) |
| `imagepng()` | Save as PNG file |
| `imagedestroy()` | Free memory |

---

## Step 9: Database Save

```php
// Image.php (Model)
public function create(int $userId, string $filename): int|false
{
    $stmt = $this->db->prepare(
        "INSERT INTO images (user_id, filename, created_at) VALUES (?, ?, NOW())"
    );
    $stmt->execute([$userId, $filename]);
    return $this->db->lastInsertId();
}
```

---

## Step 10: Update UI

Back in JavaScript, when the server responds successfully:

```javascript
// lines 312-336
function addPhotoToSidebar(image) {
    // Remove "No photos yet" message if present
    const noPhotosMsg = userPhotos.querySelector('p');
    if (noPhotosMsg) noPhotosMsg.remove();

    // Create new photo element
    const photoDiv = document.createElement('div');
    photoDiv.className = 'relative group';
    photoDiv.innerHTML = `
        <img src="${image.url}" alt="Your photo" class="w-full rounded-lg">
        <button class="delete-btn ...">...</button>
    `;

    // Add to TOP of sidebar (newest first)
    userPhotos.insertBefore(photoDiv, userPhotos.firstChild);

    // Attach delete handler to new button
    photoDiv.querySelector('.delete-btn').addEventListener('click', handleDelete);
}
```

---

## Visual Flow Summary

```
┌─────────────────────────────────────────────────────────────────┐
│                        BROWSER (Client)                         │
├─────────────────────────────────────────────────────────────────┤
│  1. User sees webcam feed OR uploads image                      │
│  2. User selects overlay (preview shown on top)                 │
│  3. User clicks "Capture"                                       │
│  4. Canvas captures current frame → base64                      │
│  5. fetch() sends { image: base64, overlay: "hearts" }          │
└────────────────────────────┬────────────────────────────────────┘
                             │ POST /editor/capture
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                        SERVER (PHP)                             │
├─────────────────────────────────────────────────────────────────┤
│  6. Decode base64 → binary image data                           │
│  7. Load overlay PNG from /overlays/                            │
│  8. Create canvas, draw user photo                              │
│  9. Scale & composite overlay WITH TRANSPARENCY                 │
│  10. Save final image to /uploads/img_xxx.png                   │
│  11. Insert record into `images` table                          │
│  12. Return { success: true, image: { id, url } }               │
└────────────────────────────┬────────────────────────────────────┘
                             │ JSON Response
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                        BROWSER (Client)                         │
├─────────────────────────────────────────────────────────────────┤
│  13. Show "Photo saved!" message                                │
│  14. Add thumbnail to sidebar dynamically                       │
└─────────────────────────────────────────────────────────────────┘
```

---

## Delete Flow

When user clicks delete on a photo:

```javascript
async function handleDelete(e) {
    const imageId = e.currentTarget.dataset.imageId;
    if (!confirm('Delete this photo?')) return;

    const response = await fetch('/editor/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: imageId })
    });

    const result = await response.json();

    if (result.success) {
        // Remove from DOM
        const photoDiv = document.querySelector(`[data-image-id="${imageId}"]`);
        if (photoDiv) photoDiv.remove();
    }
}
```

Server-side (`EditorController::delete()`):
1. Verify user owns the image
2. Delete from database
3. Delete file from `/uploads/`
4. Return success

---

## Security Considerations

1. **Authentication**: All editor endpoints check `$_SESSION['user_id']`
2. **Ownership**: Delete checks `isOwner()` before allowing deletion
3. **File Validation**: Only PNG/JPEG images accepted
4. **Overlay Validation**: Only predefined overlays from server allowed
5. **Base64 Validation**: Regex check for valid data URL format

---

## File Structure

```
src/
├── Controllers/
│   └── EditorController.php    # Handles capture, upload, delete
├── Models/
│   └── Image.php               # Database operations
└── Views/
    └── editor/
        └── index.php           # HTML + JavaScript

overlays/                       # PNG overlay images
uploads/                        # User-generated images
public/
├── overlays -> ../overlays     # Symlink for web access
└── uploads -> ../uploads       # Symlink for web access
```
