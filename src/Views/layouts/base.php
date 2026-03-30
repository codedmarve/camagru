<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::getToken()) ?>">
    <title><?= htmlspecialchars($title ?? 'Camagru') ?></title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Global CSRF helper for AJAX requests
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        window.csrfHeaders = { 'X-CSRF-Token': window.csrfToken };
    </script>
</head>
<body class="bg-gray-100 min-h-screen pt-16">
    <!-- Navigation (Fixed) -->
    <nav class="bg-white shadow-md fixed top-0 left-0 right-0 z-50">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/" class="text-xl font-bold text-indigo-600"><?= htmlspecialchars($_SESSION['username']) ?></a>
                <?php else: ?>
                    <a href="/" class="text-xl font-bold text-indigo-600">Camagru</a>
                <?php endif; ?>
                <div class="space-x-4">
                    <a href="/gallery" class="text-indigo-600 hover:text-indigo-800">Gallery</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/editor" class="text-indigo-600 hover:text-indigo-800">Editor</a>
                        <a href="/profile" class="text-indigo-600 hover:text-indigo-800">Profile</a>
                        <a href="/auth/logout" class="text-red-600 hover:text-red-800">Logout</a>
                    <?php else: ?>
                        <a href="/auth/login" class="text-indigo-600 hover:text-indigo-800">Login</a>
                        <a href="/auth/register" class="text-indigo-600 hover:text-indigo-800">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-8">
        <?= $content ?>
    </main>
</body>
</html>
