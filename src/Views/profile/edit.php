<?php
$title = 'Edit Profile - Camagru';

// Get messages from session
$errors = $_SESSION['errors'] ?? [];
$passwordErrors = $_SESSION['password_errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$success = $_SESSION['success'] ?? '';
$info = $_SESSION['info'] ?? '';
unset($_SESSION['errors'], $_SESSION['password_errors'], $_SESSION['old'], $_SESSION['success'], $_SESSION['info']);

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Edit Profile</h1>

    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($info)): ?>
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($info) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($user['pending_email'])): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
            Pending email change to: <strong><?= htmlspecialchars($user['pending_email']) ?></strong>
            <br><small>Check your inbox to confirm the change.</small>
        </div>
    <?php endif; ?>

    <!-- Profile Information Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Profile Information</h2>

        <form method="POST" action="/profile/update" class="space-y-4">
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
                    value="<?= htmlspecialchars($old['username'] ?? $user['username']) ?>"
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
                    value="<?= htmlspecialchars($old['email'] ?? $user['email']) ?>"
                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?>"
                    required
                >
                <?php if (!empty($errors['email'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['email']) ?></p>
                <?php else: ?>
                    <p class="text-gray-500 text-sm mt-1">Changing email requires verification</p>
                <?php endif; ?>
            </div>

            <!-- Email Notifications -->
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="notify_comments"
                    name="notify_comments"
                    <?= $user['notify_comments'] ? 'checked' : '' ?>
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <label for="notify_comments" class="ml-2 text-sm text-gray-700">
                    Email me when someone comments on my photos
                </label>
            </div>

            <button
                type="submit"
                class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                Save Changes
            </button>
        </form>
    </div>

    <!-- Change Password Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Change Password</h2>

        <form method="POST" action="/profile/password" class="space-y-4">
            <?= Csrf::input() ?>
            <!-- Current Password -->
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                    Current Password
                </label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 <?= isset($passwordErrors['current_password']) ? 'border-red-500' : 'border-gray-300' ?>"
                    required
                >
                <?php if (!empty($passwordErrors['current_password'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($passwordErrors['current_password']) ?></p>
                <?php endif; ?>
            </div>

            <!-- New Password -->
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                    New Password
                </label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 <?= isset($passwordErrors['new_password']) ? 'border-red-500' : 'border-gray-300' ?>"
                    required
                >
                <?php if (!empty($passwordErrors['new_password'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($passwordErrors['new_password']) ?></p>
                <?php else: ?>
                    <p class="text-gray-500 text-sm mt-1">Minimum 8 characters</p>
                <?php endif; ?>
            </div>

            <!-- Confirm New Password -->
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                    Confirm New Password
                </label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 <?= isset($passwordErrors['confirm_password']) ? 'border-red-500' : 'border-gray-300' ?>"
                    required
                >
                <?php if (!empty($passwordErrors['confirm_password'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($passwordErrors['confirm_password']) ?></p>
                <?php endif; ?>
            </div>

            <button
                type="submit"
                class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                Change Password
            </button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
