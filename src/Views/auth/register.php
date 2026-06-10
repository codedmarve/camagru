<?php
$title = 'Register - Camagru';

// Get errors and old input from session, then clear them
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

// Start output buffering to capture content
ob_start();
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
    <h1 class="text-2xl font-bold text-center mb-6">Create Account</h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/auth/register" class="space-y-4">
        <?= Csrf::input() ?>
        <!-- Username -->
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                Username
            </label>
            <input
                type="text"
                id="username"
                name="username"
                value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 <?= isset($errors['username']) ? 'border-red-500' : 'border-gray-300' ?>"
                required
            >
            <?php if (!empty($errors['username'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['username']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                Email
            </label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?>"
                required
            >
            <?php if (!empty($errors['email'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['email']) ?></p>
            <?php endif; ?>
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
                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 <?= isset($errors['password']) ? 'border-red-500' : 'border-gray-300' ?>"
                required
            >
            <?php if (!empty($errors['password'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['password']) ?></p>
            <?php else: ?>
                <p class="text-gray-500 text-sm mt-1">At least 8 characters, including a letter and a number</p>
            <?php endif; ?>
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">
                Confirm Password
            </label>
            <input
                type="password"
                id="password_confirm"
                name="password_confirm"
                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 <?= isset($errors['password_confirm']) ? 'border-red-500' : 'border-gray-300' ?>"
                required
            >
            <?php if (!empty($errors['password_confirm'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['password_confirm']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
            Register
        </button>
    </form>

    <p class="text-center text-gray-600 mt-4">
        Already have an account?
        <a href="/auth/login" class="text-indigo-600 hover:text-indigo-800">Login</a>
    </p>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
