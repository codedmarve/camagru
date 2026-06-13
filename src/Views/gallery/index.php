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
        <!-- Image Feed (cards rendered server-side via _card.php; the same partial
             is reused by GalleryController::feed for infinite scroll) -->
        <div id="image-feed" class="space-y-8"
             data-current-page="<?= (int)$currentPage ?>"
             data-total-pages="<?= (int)$totalPages ?>">
            <?php foreach ($images as $image): require __DIR__ . '/_card.php'; endforeach; ?>
        </div>

        <!-- Infinite-scroll sentinel + loading indicator (observed by IntersectionObserver) -->
        <div id="scroll-sentinel" class="py-8 text-center text-gray-400">
            <span id="feed-loading" class="hidden">Loading…</span>
        </div>

        <!-- Pagination (no-JS fallback; hidden by JS when infinite scroll is active) -->
        <?php if ($totalPages > 1): ?>
            <nav id="pagination-nav" class="flex justify-center items-center gap-2 mt-8">
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
    const feed = document.getElementById('image-feed');

    // ---- Helpers ----
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

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

    // ---- Bind every interactive handler inside one card ----
    // Called for cards present on load AND for cards appended by infinite scroll,
    // so newly loaded photos behave exactly like the first page.
    function bindCard(article) {
        // Like button
        const likeBtn = article.querySelector('.like-btn');
        if (likeBtn) {
            likeBtn.addEventListener('click', async function() {
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
        }

        // Comment form
        const form = article.querySelector('.comment-form');
        if (form) {
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
        }

        // Existing delete-comment buttons within this card
        article.querySelectorAll('.delete-comment').forEach(btn => {
            btn.addEventListener('click', handleDeleteComment);
        });

        // Toggle comments visibility
        const toggle = article.querySelector('.toggle-comments');
        if (toggle) {
            toggle.addEventListener('click', function() {
                const section = article.querySelector('.comments-section');
                section.classList.toggle('hidden');
            });
        }
    }

    // Bind cards rendered on the initial page load
    if (feed) {
        feed.querySelectorAll('article[data-image-id]').forEach(bindCard);
    }

    // ---- Infinite scroll ----
    const sentinel = document.getElementById('scroll-sentinel');
    const loadingEl = document.getElementById('feed-loading');
    const paginationNav = document.getElementById('pagination-nav');

    if (feed && sentinel) {
        let currentPage = parseInt(feed.dataset.currentPage) || 1;
        const totalPages = parseInt(feed.dataset.totalPages) || 1;
        let loading = false;

        if (totalPages > currentPage) {
            // JS works and there is more to load: drop the no-JS Prev/Next links
            if (paginationNav) paginationNav.classList.add('hidden');

            const observer = new IntersectionObserver(async (entries) => {
                if (!entries[0].isIntersecting || loading) return;
                loading = true;
                if (loadingEl) loadingEl.classList.remove('hidden');

                try {
                    const response = await fetch(`/gallery/feed?page=${currentPage + 1}`);
                    const html = await response.text();

                    if (html.trim()) {
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        temp.querySelectorAll('article[data-image-id]').forEach(article => {
                            feed.appendChild(article);
                            bindCard(article);
                        });
                        currentPage++;
                    }

                    // Stop once we've reached the last page or got nothing back
                    if (!html.trim() || currentPage >= totalPages) {
                        observer.disconnect();
                        sentinel.remove();
                    }
                } catch (error) {
                    console.error('Feed load error:', error);
                } finally {
                    loading = false;
                    if (loadingEl) loadingEl.classList.add('hidden');
                }
            }, { rootMargin: '200px' });

            observer.observe(sentinel);
        } else {
            // Everything already fits on the first page
            sentinel.remove();
        }
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
