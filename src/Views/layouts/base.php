<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::getToken()) ?>">
    <title><?= htmlspecialchars($title ?? 'Camagru') ?></title>
    <!-- Inline SVG favicon: stops the browser's automatic /favicon.ico request (avoids a 404 in the console) -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📷</text></svg>">
    <!-- Tailwind CSS compiled to a static asset (no runtime JS, works offline) -->
    <link rel="stylesheet" href="/css/app.css">
    <script>
        // Global CSRF helper for AJAX requests
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        window.csrfHeaders = { 'X-CSRF-Token': window.csrfToken };
    </script>
</head>
<body class="bg-gray-100 min-h-screen pt-16 flex flex-col">
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
    <main class="max-w-6xl mx-auto px-4 py-8 w-full grow">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-8 py-4">
        <div class="max-w-6xl mx-auto px-4 text-center text-sm text-gray-500">
            &copy; <?= date('Y') ?> Camagru
        </div>
    </footer>
</body>
</html>
