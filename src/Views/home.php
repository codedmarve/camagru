<?php
$title = 'Camagru - Photo Gallery';

$userImages = $data['userImages'] ?? [];
$isLoggedIn = $data['isLoggedIn'] ?? false;

ob_start();
?>

<?php if ($isLoggedIn): ?>
    <!-- Logged In: Show User's Images -->
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">My Photos</h1>
            <a href="/editor" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                Create New
            </a>
        </div>

        <?php if (empty($userImages)): ?>
            <div class="text-center py-12 bg-white rounded-lg shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-gray-600 text-lg mb-4">You haven't created any photos yet</p>
                <a href="/editor" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                    Create Your First Photo
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($userImages as $image): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden group">
                        <div class="aspect-square">
                            <img
                                src="/uploads/<?= htmlspecialchars($image['filename']) ?>"
                                alt="Your photo"
                                class="w-full h-full object-cover"
                            >
                        </div>
                        <div class="p-2 text-xs text-gray-500">
                            <?= date('M j, Y', strtotime($image['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-8 text-center">
            <a href="/gallery" class="text-indigo-600 hover:text-indigo-800 font-medium">
                Browse the public gallery →
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- Logged Out: Welcome Screen -->
    <div class="text-center max-w-2xl mx-auto">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Welcome to Camagru</h1>
        <p class="text-xl text-gray-600 mb-8">Capture, create, and share your photos with fun overlays</p>

        <div class="space-x-4 mb-12">
            <a href="/auth/login" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                Login
            </a>
            <a href="/auth/register" class="inline-block bg-white text-indigo-600 border border-indigo-600 px-6 py-3 rounded-lg hover:bg-indigo-50 transition">
                Register
            </a>
        </div>

        <div class="text-center">
            <a href="/gallery" class="text-indigo-600 hover:text-indigo-800 font-medium">
                Or browse the public gallery →
            </a>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/base.php';
?>
