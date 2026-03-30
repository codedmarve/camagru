<?php
$title = 'Login - Camagru';

// Get errors, old input, and success message from session
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['success']);

ob_start();
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
    <h1 class="text-2xl font-bold text-center mb-6">Login</h1>

    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/auth/login" class="space-y-4">
        <?= Csrf::input() ?>
        <!-- Email or Username -->
        <div>
            <label for="login" class="block text-sm font-medium text-gray-700 mb-1">
                Email or Username
            </label>
            <input
                type="text"
                id="login"
                name="login"
                value="<?= htmlspecialchars($old['login'] ?? '') ?>"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
            >
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                Password
            </label>
            <input
                type="password"
                id="password"
                name="password"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
            >
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
            Login
        </button>
    </form>

    <div class="text-center mt-4 space-y-2">
        <p>
            <a href="/auth/forgot-password" class="text-indigo-600 hover:text-indigo-800">
                Forgot your password?
            </a>
        </p>
        <p class="text-gray-600">
            Don't have an account?
            <a href="/auth/register" class="text-indigo-600 hover:text-indigo-800">Register</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
