<?php
/**
 * Single gallery image card.
 *
 * Expects these variables in scope (set by the gallery index loop and by
 * GalleryController::feed): $image, $comments, $userLikes, $isLoggedIn, $currentUserId.
 * Rendering the card server-side keeps all escaping (htmlspecialchars) in one place,
 * so the infinite-scroll endpoint reuses the exact same markup as the first page.
 */
$imageId = $image['id'];
$imageComments = $comments[$imageId] ?? [];
$isLiked = in_array($imageId, $userLikes);
$likeCount = $image['like_count'] ?? 0;
?>
<article class="bg-white rounded-lg shadow-md overflow-hidden" data-image-id="<?= $imageId ?>">
    <!-- Image Header -->
    <div class="px-4 py-3 border-b flex items-center">
        <span class="font-semibold"><?= htmlspecialchars($image['username']) ?></span>
        <span class="text-gray-400 text-sm ml-auto">
            <?= date('M j, Y', strtotime($image['created_at'])) ?>
        </span>
        <?php if ($currentUserId !== null && (int)$image['user_id'] === (int)$currentUserId): ?>
            <!-- Owner-only: delete your own photo from anywhere in the gallery -->
            <button class="delete-image ml-2 text-gray-400 hover:text-red-500 transition" data-image-id="<?= $imageId ?>" title="Delete photo">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        <?php endif; ?>
    </div>

    <!-- Image -->
    <div class="bg-gray-100">
        <img
            src="/uploads/<?= htmlspecialchars($image['filename']) ?>"
            alt="Photo by <?= htmlspecialchars($image['username']) ?>"
            class="w-full"
            loading="lazy"
        >
    </div>

    <!-- Actions -->
    <div class="px-4 py-3">
        <!-- Like Button -->
        <div class="flex items-center gap-4 mb-2">
            <button
                class="like-btn flex items-center gap-1 <?= $isLoggedIn ? 'hover:text-red-500' : 'cursor-not-allowed' ?> transition"
                data-image-id="<?= $imageId ?>"
                <?= !$isLoggedIn ? 'disabled title="Log in to like"' : '' ?>
            >
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="h-6 w-6 <?= $isLiked ? 'text-red-500 fill-current' : '' ?>"
                     fill="<?= $isLiked ? 'currentColor' : 'none' ?>"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span class="like-count font-semibold"><?= $likeCount ?></span>
            </button>

            <button class="toggle-comments flex items-center gap-1 hover:text-indigo-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span><?= count($imageComments) ?></span>
            </button>
        </div>

        <!-- Comments Section (collapsible) -->
        <div class="comments-section">
            <!-- Existing Comments -->
            <div class="comments-list space-y-2 mb-3">
                <?php foreach ($imageComments as $comment): ?>
                    <div class="comment flex items-start gap-2" data-comment-id="<?= $comment['id'] ?>">
                        <div class="flex-1">
                            <span class="font-semibold text-sm"><?= htmlspecialchars($comment['username']) ?></span>
                            <span class="text-sm"><?= htmlspecialchars($comment['content']) ?></span>
                        </div>
                        <?php if ($currentUserId === $comment['user_id']): ?>
                            <button
                                class="delete-comment text-gray-400 hover:text-red-500 text-xs"
                                data-comment-id="<?= $comment['id'] ?>"
                                title="Delete comment"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Add Comment Form -->
            <?php if ($isLoggedIn): ?>
                <form class="comment-form flex gap-2" data-image-id="<?= $imageId ?>">
                    <input
                        type="text"
                        class="comment-input flex-1 px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Add a comment..."
                        maxlength="1000"
                        required
                    >
                    <button
                        type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 transition"
                    >
                        Post
                    </button>
                </form>
            <?php else: ?>
                <p class="text-sm text-gray-500">
                    <a href="/auth/login" class="text-indigo-600 hover:underline">Log in</a> to comment
                </p>
            <?php endif; ?>
        </div>
    </div>
</article>
