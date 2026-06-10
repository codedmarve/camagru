<?php
$title = 'Editor - Camagru';

ob_start();
?>

<style>
    /* Prevent page scroll on editor - only sidebar scrolls */
    body {
        overflow: hidden;
        height: 100vh;
    }
    main {
        height: calc(100vh - 64px - 32px); /* Subtract nav height and top padding */
        overflow: hidden;
        padding-top: 1rem !important;
        padding-bottom: 0 !important;
        flex-grow: 0 !important; /* opt out of the global sticky-footer growth on this fixed-height page */
    }
</style>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-full">
    <!-- Main Editor Area -->
    <div class="lg:col-span-3 overflow-hidden">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-4">Create a Photo</h1>

            <!-- Webcam/Preview Container -->
            <div id="preview-container" class="relative bg-black rounded-lg overflow-hidden mb-4" style="aspect-ratio: 4/3;">
                <!-- Webcam Video (mirrored for natural selfie view) -->
                <video id="webcam" class="w-full h-full object-cover" style="transform: scaleX(-1);" autoplay playsinline></video>

                <!-- Upload Drop Zone (shown in upload mode) - z-10 -->
                <div id="upload-dropzone" class="absolute inset-0 bg-white flex flex-col items-center justify-center transition-all duration-200 hidden z-10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="text-gray-600 text-lg font-medium mb-2">Drag an image here</p>
                    <p class="text-gray-400 text-sm">or use the file picker below</p>
                </div>

                <!-- Upload Preview (when using file upload instead of webcam) - z-20 -->
                <img id="upload-preview" class="absolute top-0 left-0 hidden z-20 select-none" style="transform-origin: 0 0; max-width: none;" draggable="false" alt="Upload preview">

                <!-- Overlay Preview (positioned on top of everything) - z-30 -->
                <img id="overlay-preview" class="absolute inset-0 w-full h-full object-contain pointer-events-none hidden z-30" style="transform: scaleX(-1);" alt="Overlay">

                <!-- Canvas for capturing (hidden) -->
                <canvas id="capture-canvas" class="hidden"></canvas>

                <!-- No webcam message -->
                <div id="no-webcam" class="absolute inset-0 flex items-center justify-center text-white bg-gray-800 hidden z-10">
                    <div class="text-center">
                        <p class="mb-4">Webcam not available</p>
                        <p class="text-sm text-gray-400">Use the upload option below</p>
                    </div>
                </div>
            </div>

            <!-- Image Position Controls (shown in upload mode when image loaded) -->
            <div id="image-controls" class="hidden mb-4 p-3 bg-gray-100 rounded-lg">
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-700">Zoom:</label>
                    <input type="range" id="zoom-slider" min="10" max="300" value="100" class="flex-1">
                    <span id="zoom-value" class="text-sm text-gray-600 w-12">100%</span>
                    <button id="btn-reset-position" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded transition">Reset</button>
                </div>
                <p class="text-xs text-gray-500 mt-2">Drag the image to reposition. Scroll or use slider to zoom.</p>
            </div>

            <!-- Controls -->
            <div class="space-y-4">
                <!-- Mode Toggle -->
                <div class="flex gap-4 mb-4">
                    <button id="btn-webcam-mode" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                        Webcam
                    </button>
                    <button id="btn-upload-mode" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
                        Upload Photo
                    </button>
                </div>

                <!-- Upload Input (hidden by default) -->
                <div id="upload-section" class="hidden">
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Choose an image</span>
                        <input type="file" id="file-input" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </label>
                </div>

                <!-- Overlay Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select an Overlay</label>
                    <div id="overlay-grid" class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                        <?php foreach ($overlays as $overlay): ?>
                            <button
                                type="button"
                                class="overlay-btn p-2 border-2 border-transparent rounded-lg hover:border-indigo-300 transition"
                                data-overlay-id="<?= htmlspecialchars($overlay['id']) ?>"
                                data-overlay-url="<?= htmlspecialchars($overlay['url']) ?>"
                            >
                                <img src="<?= htmlspecialchars($overlay['url']) ?>" alt="<?= htmlspecialchars($overlay['name']) ?>" class="w-full h-auto bg-gray-100 rounded">
                            </button>
                        <?php endforeach; ?>

                        <?php if (empty($overlays)): ?>
                            <p class="col-span-full text-gray-500">No overlays available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Capture Button -->
                <button
                    id="btn-capture"
                    class="w-full py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                    disabled
                >
                    Select an overlay to capture
                </button>

                <!-- Status Message -->
                <div id="status-message" class="text-center text-sm hidden"></div>
            </div>
        </div>
    </div>

    <!-- Sidebar: User's Recent Photos -->
    <div class="lg:col-span-1 h-full overflow-hidden">
        <div class="bg-white rounded-lg shadow-md p-4 h-full flex flex-col">
            <h2 class="text-lg font-semibold mb-4 flex-shrink-0">Your Photos</h2>
            <div id="user-photos" class="space-y-2 overflow-y-auto flex-1 pr-1">
                <?php if (empty($userImages)): ?>
                    <p class="text-gray-500 text-sm">No photos yet</p>
                <?php else: ?>
                    <?php foreach ($userImages as $img): ?>
                        <div class="relative group" data-image-id="<?= $img['id'] ?>">
                            <img src="/uploads/<?= htmlspecialchars($img['filename']) ?>" alt="Your photo" class="w-full rounded-lg">
                            <button
                                class="delete-btn absolute top-1 right-1 bg-red-600 text-white p-1 rounded opacity-0 group-hover:opacity-100 transition"
                                data-image-id="<?= $img['id'] ?>"
                                title="Delete"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const webcamVideo = document.getElementById('webcam');
    const overlayPreview = document.getElementById('overlay-preview');
    const uploadPreview = document.getElementById('upload-preview');
    const uploadDropzone = document.getElementById('upload-dropzone');
    const previewContainer = document.getElementById('preview-container');
    const captureCanvas = document.getElementById('capture-canvas');
    const noWebcamMsg = document.getElementById('no-webcam');
    const btnCapture = document.getElementById('btn-capture');
    const btnWebcamMode = document.getElementById('btn-webcam-mode');
    const btnUploadMode = document.getElementById('btn-upload-mode');
    const uploadSection = document.getElementById('upload-section');
    const fileInput = document.getElementById('file-input');
    const overlayBtns = document.querySelectorAll('.overlay-btn');
    const statusMessage = document.getElementById('status-message');
    const userPhotos = document.getElementById('user-photos');
    const imageControls = document.getElementById('image-controls');
    const zoomSlider = document.getElementById('zoom-slider');
    const zoomValue = document.getElementById('zoom-value');
    const btnResetPosition = document.getElementById('btn-reset-position');

    // State
    let currentMode = 'webcam'; // 'webcam' or 'upload'
    let selectedOverlay = null;
    let webcamStream = null;
    let uploadedImageData = null;
    let uploadedImage = null; // Store the Image object for dimensions

    // Image transform state
    let imgScale = 1;
    let imgX = 0;
    let imgY = 0;
    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;

    // Initialize webcam
    async function initWebcam() {
        try {
            webcamStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false
            });
            webcamVideo.srcObject = webcamStream;
            webcamVideo.classList.remove('hidden');
            noWebcamMsg.classList.add('hidden');
        } catch (error) {
            console.error('Webcam error:', error);
            webcamVideo.classList.add('hidden');
            noWebcamMsg.classList.remove('hidden');
            // Auto-switch to upload mode
            switchToUploadMode();
        }
    }

    // Stop webcam
    function stopWebcam() {
        if (webcamStream) {
            webcamStream.getTracks().forEach(track => track.stop());
            webcamStream = null;
        }
    }

    // Update uploaded image transform
    function updateImageTransform() {
        uploadPreview.style.transform = `translate(${imgX}px, ${imgY}px) scale(${imgScale})`;
    }

    // Reset image position to fit container
    function resetImagePosition() {
        if (!uploadedImage) return;

        const containerRect = previewContainer.getBoundingClientRect();
        const containerAspect = containerRect.width / containerRect.height;
        const imageAspect = uploadedImage.width / uploadedImage.height;

        // Scale to cover the container (like object-cover)
        if (imageAspect > containerAspect) {
            // Image is wider - fit by height
            imgScale = containerRect.height / uploadedImage.height;
        } else {
            // Image is taller - fit by width
            imgScale = containerRect.width / uploadedImage.width;
        }

        // Center the image
        const scaledWidth = uploadedImage.width * imgScale;
        const scaledHeight = uploadedImage.height * imgScale;
        imgX = (containerRect.width - scaledWidth) / 2;
        imgY = (containerRect.height - scaledHeight) / 2;

        // Update slider
        zoomSlider.value = Math.round(imgScale * 100);
        zoomValue.textContent = Math.round(imgScale * 100) + '%';

        updateImageTransform();
    }

    // Handle drag start
    function handleDragStart(e) {
        if (currentMode !== 'upload' || !uploadedImageData) return;

        // Prevent default to stop text selection and native image drag
        if (e.type === 'mousedown') {
            e.preventDefault();
        }

        isDragging = true;
        previewContainer.classList.add('cursor-grabbing');
        previewContainer.classList.remove('cursor-grab');

        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        dragStartX = clientX - imgX;
        dragStartY = clientY - imgY;
    }

    // Handle drag move
    function handleDragMove(e) {
        if (!isDragging) return;

        e.preventDefault();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        imgX = clientX - dragStartX;
        imgY = clientY - dragStartY;
        updateImageTransform();
    }

    // Handle drag end
    function handleDragEnd() {
        if (!isDragging) return;

        isDragging = false;
        previewContainer.classList.remove('cursor-grabbing');
        if (currentMode === 'upload') {
            previewContainer.classList.add('cursor-grab');
        }
    }

    // Handle zoom (from slider or wheel)
    function handleZoom(newScale, centerX, centerY) {
        const containerRect = previewContainer.getBoundingClientRect();

        // Clamp scale
        newScale = Math.max(0.1, Math.min(3, newScale));

        // If center point provided, zoom towards that point
        if (centerX !== undefined && centerY !== undefined) {
            const scaleRatio = newScale / imgScale;
            imgX = centerX - (centerX - imgX) * scaleRatio;
            imgY = centerY - (centerY - imgY) * scaleRatio;
        }

        imgScale = newScale;
        zoomSlider.value = Math.round(imgScale * 100);
        zoomValue.textContent = Math.round(imgScale * 100) + '%';
        updateImageTransform();
    }

    // Prevent native image dragging (the ghost image effect)
    uploadPreview.addEventListener('dragstart', function(e) {
        e.preventDefault();
    });

    // Mouse/touch events for dragging
    previewContainer.addEventListener('mousedown', handleDragStart);
    previewContainer.addEventListener('touchstart', handleDragStart, { passive: true });
    document.addEventListener('mousemove', handleDragMove);
    document.addEventListener('touchmove', handleDragMove, { passive: false });
    document.addEventListener('mouseup', handleDragEnd);
    document.addEventListener('touchend', handleDragEnd);

    // Scroll wheel zoom
    previewContainer.addEventListener('wheel', function(e) {
        if (currentMode !== 'upload' || !uploadedImageData) return;

        e.preventDefault();
        const rect = previewContainer.getBoundingClientRect();
        const centerX = e.clientX - rect.left;
        const centerY = e.clientY - rect.top;

        const delta = e.deltaY > 0 ? -0.1 : 0.1;
        handleZoom(imgScale + delta, centerX, centerY);
    }, { passive: false });

    // Zoom slider
    zoomSlider.addEventListener('input', function() {
        const containerRect = previewContainer.getBoundingClientRect();
        handleZoom(parseInt(this.value) / 100, containerRect.width / 2, containerRect.height / 2);
    });

    // Reset button
    btnResetPosition.addEventListener('click', resetImagePosition);

    // Switch to webcam mode
    function switchToWebcamMode() {
        currentMode = 'webcam';
        btnWebcamMode.classList.remove('bg-gray-200', 'text-gray-700');
        btnWebcamMode.classList.add('bg-indigo-600', 'text-white');
        btnUploadMode.classList.remove('bg-indigo-600', 'text-white');
        btnUploadMode.classList.add('bg-gray-200', 'text-gray-700');
        uploadSection.classList.add('hidden');
        uploadPreview.classList.add('hidden');
        uploadDropzone.classList.add('hidden');
        imageControls.classList.add('hidden');
        webcamVideo.classList.remove('hidden');
        previewContainer.classList.remove('bg-white');
        previewContainer.classList.add('bg-black');
        previewContainer.classList.remove('cursor-grab');
        // Mirror the overlay in webcam mode
        overlayPreview.style.transform = 'scaleX(-1)';
        uploadedImageData = null;
        uploadedImage = null;
        initWebcam();
        updateCaptureButton();
    }

    // Switch to upload mode
    function switchToUploadMode() {
        currentMode = 'upload';
        btnUploadMode.classList.remove('bg-gray-200', 'text-gray-700');
        btnUploadMode.classList.add('bg-indigo-600', 'text-white');
        btnWebcamMode.classList.remove('bg-indigo-600', 'text-white');
        btnWebcamMode.classList.add('bg-gray-200', 'text-gray-700');
        uploadSection.classList.remove('hidden');
        webcamVideo.classList.add('hidden');
        noWebcamMsg.classList.add('hidden');
        previewContainer.classList.remove('bg-black');
        previewContainer.classList.add('bg-white');
        previewContainer.classList.add('cursor-grab');
        // Don't mirror the overlay in upload mode
        overlayPreview.style.transform = 'none';
        // Show dropzone only if no image uploaded yet
        if (uploadedImageData) {
            uploadDropzone.classList.add('hidden');
            uploadPreview.classList.remove('hidden');
            imageControls.classList.remove('hidden');
        } else {
            uploadDropzone.classList.remove('hidden');
            uploadPreview.classList.add('hidden');
            imageControls.classList.add('hidden');
        }
        stopWebcam();
        updateCaptureButton();
    }

    // Handle file (from input or drag-drop)
    function handleFile(file) {
        if (!file || !file.type.match(/^image\/(jpeg|png)$/)) {
            showStatus('Please select a JPEG or PNG image', 'error');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            uploadedImageData = e.target.result;

            // Create Image object to get dimensions
            uploadedImage = new Image();
            uploadedImage.onload = function() {
                // Set the image source and show it
                uploadPreview.src = uploadedImageData;
                uploadPreview.style.width = uploadedImage.width + 'px';
                uploadPreview.style.height = uploadedImage.height + 'px';
                uploadPreview.classList.remove('hidden');
                uploadDropzone.classList.add('hidden');
                imageControls.classList.remove('hidden');

                // Reset position to fit the container
                resetImagePosition();
                updateCaptureButton();
            };
            uploadedImage.src = uploadedImageData;
        };
        reader.readAsDataURL(file);
    }

    // Handle file selection from input
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) handleFile(file);
    });

    // Drag and drop functionality
    previewContainer.addEventListener('dragover', function(e) {
        e.preventDefault();
        if (currentMode === 'upload') {
            if (!uploadedImageData) {
                uploadDropzone.classList.add('bg-indigo-50', 'border-2', 'border-dashed', 'border-indigo-400');
            } else {
                // Show visual feedback on the container when replacing an image
                previewContainer.classList.add('ring-4', 'ring-indigo-400');
            }
        }
    });

    previewContainer.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadDropzone.classList.remove('bg-indigo-50', 'border-2', 'border-dashed', 'border-indigo-400');
        previewContainer.classList.remove('ring-4', 'ring-indigo-400');
    });

    previewContainer.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadDropzone.classList.remove('bg-indigo-50', 'border-2', 'border-dashed', 'border-indigo-400');
        previewContainer.classList.remove('ring-4', 'ring-indigo-400');

        if (currentMode !== 'upload') {
            // Auto-switch to upload mode if user drops an image while in webcam mode
            switchToUploadMode();
        }

        const file = e.dataTransfer.files[0];
        if (file) handleFile(file);
    });

    // Handle overlay selection
    overlayBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove selection from all
            overlayBtns.forEach(b => b.classList.remove('border-indigo-600'));
            // Select this one
            this.classList.add('border-indigo-600');
            selectedOverlay = {
                id: this.dataset.overlayId,
                url: this.dataset.overlayUrl
            };
            // Show overlay preview
            overlayPreview.src = selectedOverlay.url;
            overlayPreview.classList.remove('hidden');
            updateCaptureButton();
        });
    });

    // Update capture button state
    function updateCaptureButton() {
        const hasImage = currentMode === 'webcam' ? !!webcamStream : !!uploadedImageData;
        const canCapture = selectedOverlay && hasImage;

        btnCapture.disabled = !canCapture;
        if (canCapture) {
            btnCapture.textContent = 'Capture Photo';
        } else if (!selectedOverlay) {
            btnCapture.textContent = 'Select an overlay to capture';
        } else {
            btnCapture.textContent = currentMode === 'webcam' ? 'Waiting for webcam...' : 'Upload an image first';
        }
    }

    // Capture photo
    btnCapture.addEventListener('click', async function() {
        if (!selectedOverlay) return;

        let imageData;

        if (currentMode === 'webcam') {
            // Capture from webcam (mirrored to match what user sees)
            captureCanvas.width = webcamVideo.videoWidth;
            captureCanvas.height = webcamVideo.videoHeight;
            const ctx = captureCanvas.getContext('2d');
            // Mirror horizontally to match the mirrored video display
            ctx.translate(captureCanvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(webcamVideo, 0, 0);
            // Reset transform for future operations
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            imageData = captureCanvas.toDataURL('image/png');
        } else {
            // Capture the visible portion of the uploaded image
            const containerRect = previewContainer.getBoundingClientRect();

            // Use a standard size for the output (4:3 aspect ratio)
            captureCanvas.width = 640;
            captureCanvas.height = 480;
            const ctx = captureCanvas.getContext('2d');

            // Calculate scale factor from container to canvas
            const scaleFactorX = captureCanvas.width / containerRect.width;
            const scaleFactorY = captureCanvas.height / containerRect.height;

            // Clear canvas with white background
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, captureCanvas.width, captureCanvas.height);

            // Draw the image with the same transform as displayed
            ctx.save();
            ctx.translate(imgX * scaleFactorX, imgY * scaleFactorY);
            ctx.scale(imgScale * scaleFactorX, imgScale * scaleFactorY);
            ctx.drawImage(uploadedImage, 0, 0);
            ctx.restore();

            imageData = captureCanvas.toDataURL('image/png');
        }

        if (!imageData) {
            showStatus('No image to capture', 'error');
            return;
        }

        // Show loading state
        btnCapture.disabled = true;
        btnCapture.textContent = 'Processing...';

        try {
            const response = await fetch('/editor/capture', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.csrfToken
                },
                body: JSON.stringify({
                    image: imageData,
                    overlay: selectedOverlay.id
                })
            });

            const result = await response.json();

            if (result.success) {
                showStatus('Photo saved!', 'success');
                addPhotoToSidebar(result.image);
            } else {
                showStatus(result.error || 'Failed to save photo', 'error');
            }
        } catch (error) {
            console.error('Capture error:', error);
            showStatus('An error occurred', 'error');
        }

        updateCaptureButton();
    });

    // Show status message
    function showStatus(message, type) {
        statusMessage.textContent = message;
        statusMessage.className = 'text-center text-sm py-2 rounded ' +
            (type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
        statusMessage.classList.remove('hidden');

        setTimeout(() => {
            statusMessage.classList.add('hidden');
        }, 3000);
    }

    // Add new photo to sidebar
    function addPhotoToSidebar(image) {
        const noPhotosMsg = userPhotos.querySelector('p');
        if (noPhotosMsg) noPhotosMsg.remove();

        const photoDiv = document.createElement('div');
        photoDiv.className = 'relative group';
        photoDiv.dataset.imageId = image.id;
        photoDiv.innerHTML = `
            <img src="${image.url}" alt="Your photo" class="w-full rounded-lg">
            <button
                class="delete-btn absolute top-1 right-1 bg-red-600 text-white p-1 rounded opacity-0 group-hover:opacity-100 transition"
                data-image-id="${image.id}"
                title="Delete"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        `;
        userPhotos.insertBefore(photoDiv, userPhotos.firstChild);

        // Attach delete handler
        photoDiv.querySelector('.delete-btn').addEventListener('click', handleDelete);
    }

    // Handle delete
    async function handleDelete(e) {
        const imageId = e.currentTarget.dataset.imageId;
        if (!confirm('Delete this photo?')) return;

        try {
            const response = await fetch('/editor/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.csrfToken
                },
                body: JSON.stringify({ id: imageId })
            });

            const result = await response.json();

            if (result.success) {
                const photoDiv = document.querySelector(`[data-image-id="${imageId}"]`);
                if (photoDiv) photoDiv.remove();
                showStatus('Photo deleted', 'success');
            } else {
                showStatus(result.error || 'Failed to delete', 'error');
            }
        } catch (error) {
            showStatus('An error occurred', 'error');
        }
    }

    // Attach delete handlers to existing photos
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', handleDelete);
    });

    // Mode toggle buttons
    btnWebcamMode.addEventListener('click', switchToWebcamMode);
    btnUploadMode.addEventListener('click', switchToUploadMode);

    // Initialize
    initWebcam();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
