<?php
$title = 'Gallery - Camagru';

// Extract data
$images = $data['images'] ?? [];
$comments = $data['comments'] ?? [];
$userLikes = $data['userLikes'] ?? [];
$currentPage = $data['currentPage'] ?? 1;
$totalPages = $data['totalPages'] ?? 1;
$isLoggedIn = $data['isLoggedIn'] ?? false;
$currentUserId = $data['currentUserId'] ?? null;

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-center mb-8">Gallery</h1>

    <?php if (empty($images)): ?>
        <div class="text-center py-12">
            <p class="text-gray-500 text-lg">No photos yet.</p>
            <?php if ($isLoggedIn): ?>
                <a href="/editor" class="inline-block mt-4 px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Create the first one!
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Image Feed -->
        <div class="space-y-8">
            <?php foreach ($images as $image): ?>
                <?php
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
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="flex justify-center items-center gap-2 mt-8">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>"
                       class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300 transition">
                        Previous
                    </a>
                <?php endif; ?>

                <span class="px-4 py-2 text-gray-600">
                    Page <?= $currentPage ?> of <?= $totalPages ?>
                </span>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>"
                       class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300 transition">
                        Next
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Like buttons
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (this.disabled) return;

            const imageId = this.dataset.imageId;
            const icon = this.querySelector('svg');
            const countEl = this.querySelector('.like-count');

            try {
                const response = await fetch('/gallery/like', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.csrfToken
                    },
                    body: JSON.stringify({ image_id: imageId })
                });

                const result = await response.json();

                if (result.success) {
                    // Update UI
                    countEl.textContent = result.count;

                    if (result.liked) {
                        icon.classList.add('text-red-500', 'fill-current');
                        icon.setAttribute('fill', 'currentColor');
                    } else {
                        icon.classList.remove('text-red-500', 'fill-current');
                        icon.setAttribute('fill', 'none');
                    }
                } else if (response.status === 401) {
                    window.location.href = '/auth/login';
                }
            } catch (error) {
                console.error('Like error:', error);
            }
        });
    });

    // Comment forms
    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const imageId = this.dataset.imageId;
            const input = this.querySelector('.comment-input');
            const content = input.value.trim();

            if (!content) return;

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            try {
                const response = await fetch('/gallery/comment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.csrfToken
                    },
                    body: JSON.stringify({ image_id: imageId, content: content })
                });

                const result = await response.json();

                if (result.success) {
                    // Add comment to list
                    const commentsList = this.closest('.comments-section').querySelector('.comments-list');
                    const commentEl = document.createElement('div');
                    commentEl.className = 'comment flex items-start gap-2';
                    commentEl.dataset.commentId = result.comment.id;
                    commentEl.innerHTML = `
                        <div class="flex-1">
                            <span class="font-semibold text-sm">${escapeHtml(result.comment.username)}</span>
                            <span class="text-sm">${escapeHtml(result.comment.content)}</span>
                        </div>
                        <button class="delete-comment text-gray-400 hover:text-red-500 text-xs" data-comment-id="${result.comment.id}" title="Delete comment">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    `;
                    commentsList.appendChild(commentEl);

                    // Attach delete handler
                    commentEl.querySelector('.delete-comment').addEventListener('click', handleDeleteComment);

                    // Update comment count
                    const article = this.closest('article');
                    const countEl = article.querySelector('.toggle-comments span');
                    countEl.textContent = parseInt(countEl.textContent) + 1;

                    // Clear input
                    input.value = '';
                } else {
                    alert(result.error || 'Failed to add comment');
                }
            } catch (error) {
                console.error('Comment error:', error);
            }

            submitBtn.disabled = false;
        });
    });

    // Delete comment handlers
    document.querySelectorAll('.delete-comment').forEach(btn => {
        btn.addEventListener('click', handleDeleteComment);
    });

    async function handleDeleteComment(e) {
        const btn = e.currentTarget;
        const commentId = btn.dataset.commentId;

        if (!confirm('Delete this comment?')) return;

        try {
            const response = await fetch('/gallery/comment/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.csrfToken
                },
                body: JSON.stringify({ comment_id: commentId })
            });

            const result = await response.json();

            if (result.success) {
                const commentEl = btn.closest('.comment');
                const article = commentEl.closest('article');

                // Update count
                const countEl = article.querySelector('.toggle-comments span');
                countEl.textContent = Math.max(0, parseInt(countEl.textContent) - 1);

                // Remove element
                commentEl.remove();
            } else {
                alert(result.error || 'Failed to delete comment');
            }
        } catch (error) {
            console.error('Delete error:', error);
        }
    }

    // Toggle comments visibility
    document.querySelectorAll('.toggle-comments').forEach(btn => {
        btn.addEventListener('click', function() {
            const article = this.closest('article');
            const section = article.querySelector('.comments-section');
            section.classList.toggle('hidden');
        });
    });

    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
