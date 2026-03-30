<?php
$title = 'Forgot Password - Camagru';

$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

ob_start();
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
    <h1 class="text-2xl font-bold text-center mb-6">Forgot Password</h1>

    <p class="text-gray-600 text-center mb-6">
        Enter your email address and we'll send you a link to reset your password.
    </p>

    <?php if (!empty($errors['general'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/auth/forgot-password" class="space-y-4">
        <?= Csrf::input() ?>
        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                Email
            </label>
            <input
                type="email"
                id="email"
                name="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
            >
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
            Send Reset Link
        </button>
    </form>

    <p class="text-center text-gray-600 mt-4">
        <a href="/auth/login" class="text-indigo-600 hover:text-indigo-800">Back to Login</a>
    </p>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
