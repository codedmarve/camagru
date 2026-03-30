<?php
$title = 'Reset Password - Camagru';

$errors = $_SESSION['errors'] ?? [];
$token = $_GET['token'] ?? '';
unset($_SESSION['errors']);

ob_start();
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
    <h1 class="text-2xl font-bold text-center mb-6">Reset Password</h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/auth/reset-password" class="space-y-4">
        <?= Csrf::input() ?>
        <!-- Hidden token field -->
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <!-- New Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                New Password
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
                <p class="text-gray-500 text-sm mt-1">Minimum 8 characters</p>
            <?php endif; ?>
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">
                Confirm New Password
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
            Reset Password
        </button>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
