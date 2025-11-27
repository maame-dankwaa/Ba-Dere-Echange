/**
 * Ba Dere Exchange - Single Book Page JavaScript
 * Handles book page interactions
 */

(function() {
    'use strict';

    /**
     * Initialize book page functionality
     */
    function init() {
        setupQuantitySelector();
        setupWishlistButton();
        setupImageGallery();
        setupReviewSection();
        setupShareButtons();
    }

    /**
     * Setup quantity selector (+/- buttons)
     */
    function setupQuantitySelector() {
        const wrapper = document.querySelector('[data-qty-wrapper]');
        if (!wrapper) return;

        const minusBtn = wrapper.querySelector('[data-qty-minus], #qtyMinus');
        const plusBtn = wrapper.querySelector('[data-qty-plus], #qtyPlus');
        const input = wrapper.querySelector('input[type="number"], #quantity');

        if (!input) return;

        const min = parseInt(input.min) || 1;
        const max = parseInt(input.max) || 99;

        // Minus button
        if (minusBtn) {
            minusBtn.addEventListener('click', () => {
                const current = parseInt(input.value) || 1;
                if (current > min) {
                    input.value = current - 1;
                    updateButtonStates();
                }
            });
        }

        // Plus button
        if (plusBtn) {
            plusBtn.addEventListener('click', () => {
                const current = parseInt(input.value) || 1;
                if (current < max) {
                    input.value = current + 1;
                    updateButtonStates();
                }
            });
        }

        // Direct input
        input.addEventListener('change', () => {
            let value = parseInt(input.value) || min;
            value = Math.max(min, Math.min(max, value));
            input.value = value;
            updateButtonStates();
        });

        // Update button disabled states
        function updateButtonStates() {
            const current = parseInt(input.value) || 1;
            if (minusBtn) {
                minusBtn.disabled = current <= min;
                minusBtn.style.opacity = current <= min ? '0.5' : '1';
            }
            if (plusBtn) {
                plusBtn.disabled = current >= max;
                plusBtn.style.opacity = current >= max ? '0.5' : '1';
            }
        }

        updateButtonStates();
    }

    /**
     * Setup wishlist button with AJAX
     */
    function setupWishlistButton() {
        const wishlistForm = document.querySelector('form[action*="wishlist_add"]');
        if (!wishlistForm) return;

        const button = wishlistForm.querySelector('button[type="submit"]');
        const bookId = wishlistForm.querySelector('input[name="book_id"]')?.value;

        if (!button || !bookId) return;

        // Check initial wishlist status
        checkWishlistStatus(bookId, button);

        wishlistForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const isInWishlist = button.dataset.inWishlist === 'true';
            const action = isInWishlist ? 'remove' : 'add';
            const url = isInWishlist
                ? `../actions/wishlist_remove.php?book_id=${bookId}`
                : '../actions/wishlist_add.php';

            try {
                button.disabled = true;

                if (isInWishlist) {
                    await fetch(url);
                    updateWishlistButton(button, false);
                    if (typeof BaDere !== 'undefined') {
                        BaDere.toast('Removed from wishlist', 'success');
                    }
                } else {
                    const formData = new FormData(wishlistForm);
                    formData.append('redirect', window.location.pathname);
                    await fetch(url, {
                        method: 'POST',
                        body: formData,
                    });
                    updateWishlistButton(button, true);
                    if (typeof BaDere !== 'undefined') {
                        BaDere.toast('Added to wishlist!', 'success');
                    }
                }

            } catch (error) {
                if (typeof BaDere !== 'undefined') {
                    BaDere.toast('Could not update wishlist', 'error');
                }
            } finally {
                button.disabled = false;
            }
        });
    }

    /**
     * Check if book is in wishlist
     */
    async function checkWishlistStatus(bookId, button) {
        try {
            const response = await fetch(`../actions/wishlist_check.php?book_id=${bookId}`);
            const data = await response.json();
            updateWishlistButton(button, data.in_wishlist);
        } catch (error) {
            // Silently fail
        }
    }

    /**
     * Update wishlist button appearance
     */
    function updateWishlistButton(button, inWishlist) {
        button.dataset.inWishlist = inWishlist;
        if (inWishlist) {
            button.textContent = '❤️ In Wishlist';
            button.classList.add('in-wishlist');
        } else {
            button.textContent = '🤍 Add to Wishlist';
            button.classList.remove('in-wishlist');
        }
    }

    /**
     * Setup image gallery (if multiple images)
     */
    function setupImageGallery() {
        const mainImage = document.querySelector('.book-main-image');
        const thumbnails = document.querySelectorAll('.book-thumbnail');

        if (!mainImage || thumbnails.length === 0) return;

        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                // Update main image
                mainImage.src = this.dataset.fullSrc || this.src;
                mainImage.alt = this.alt;

                // Update active state
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Lightbox on main image click
        mainImage.style.cursor = 'zoom-in';
        mainImage.addEventListener('click', function() {
            openLightbox(this.src);
        });
    }

    /**
     * Open image lightbox
     */
    function openLightbox(src) {
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox';
        lightbox.innerHTML = `
            <div class="lightbox-backdrop"></div>
            <div class="lightbox-content">
                <img src="${src}" alt="Book image">
                <button class="lightbox-close">&times;</button>
            </div>
        `;

        // Add styles
        if (!document.getElementById('lightbox-styles')) {
            const style = document.createElement('style');
            style.id = 'lightbox-styles';
            style.textContent = `
                .lightbox {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .lightbox-backdrop {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.9);
                }
                .lightbox-content {
                    position: relative;
                    max-width: 90vw;
                    max-height: 90vh;
                }
                .lightbox-content img {
                    max-width: 100%;
                    max-height: 90vh;
                    border-radius: 4px;
                }
                .lightbox-close {
                    position: absolute;
                    top: -40px;
                    right: 0;
                    background: none;
                    border: none;
                    color: white;
                    font-size: 32px;
                    cursor: pointer;
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(lightbox);

        // Close on backdrop click or close button
        lightbox.querySelector('.lightbox-backdrop').addEventListener('click', () => lightbox.remove());
        lightbox.querySelector('.lightbox-close').addEventListener('click', () => lightbox.remove());

        // Close on Escape key
        document.addEventListener('keydown', function handler(e) {
            if (e.key === 'Escape') {
                lightbox.remove();
                document.removeEventListener('keydown', handler);
            }
        });
    }

    /**
     * Setup review section
     */
    function setupReviewSection() {
        // Star rating input
        const ratingInputs = document.querySelectorAll('.star-rating input');
        const ratingDisplay = document.querySelector('.star-rating-display');

        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (ratingDisplay) {
                    ratingDisplay.textContent = `${this.value}/5`;
                }
            });
        });

        // Review form submission
        const reviewForm = document.querySelector('.review-form');
        if (reviewForm) {
            reviewForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';

                try {
                    const formData = new FormData(this);
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData,
                    });

                    if (response.ok) {
                        if (typeof BaDere !== 'undefined') {
                            BaDere.toast('Review submitted!', 'success');
                        }
                        // Reload to show new review
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        throw new Error('Failed to submit review');
                    }

                } catch (error) {
                    if (typeof BaDere !== 'undefined') {
                        BaDere.toast('Could not submit review', 'error');
                    }
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Review';
                }
            });
        }

        // Helpful buttons on reviews
        document.querySelectorAll('.review-helpful-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const reviewId = this.dataset.reviewId;
                try {
                    await fetch(`../actions/review_helpful.php?id=${reviewId}`);
                    const count = parseInt(this.textContent.match(/\d+/)?.[0] || 0) + 1;
                    this.textContent = `👍 Helpful (${count})`;
                    this.disabled = true;
                } catch (error) {
                    // Silently fail
                }
            });
        });
    }

    /**
     * Setup share buttons
     */
    function setupShareButtons() {
        const shareBtn = document.querySelector('.share-btn');
        if (!shareBtn) return;

        shareBtn.addEventListener('click', async function() {
            const title = document.querySelector('h1')?.textContent || 'Book';
            const url = window.location.href;

            // Try native share API first
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: title,
                        url: url,
                    });
                    return;
                } catch (error) {
                    // Fall through to fallback
                }
            }

            // Fallback: copy to clipboard
            try {
                await navigator.clipboard.writeText(url);
                if (typeof BaDere !== 'undefined') {
                    BaDere.toast('Link copied to clipboard!', 'success');
                }
            } catch (error) {
                // Show share modal
                showShareModal(title, url);
            }
        });
    }

    /**
     * Show share modal with links
     */
    function showShareModal(title, url) {
        const encodedUrl = encodeURIComponent(url);
        const encodedTitle = encodeURIComponent(title);

        const modal = document.createElement('div');
        modal.className = 'share-modal-overlay';
        modal.innerHTML = `
            <div class="share-modal">
                <h3>Share this book</h3>
                <div class="share-links">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}" target="_blank" class="share-link facebook">
                        Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}" target="_blank" class="share-link twitter">
                        Twitter
                    </a>
                    <a href="https://wa.me/?text=${encodedTitle}%20${encodedUrl}" target="_blank" class="share-link whatsapp">
                        WhatsApp
                    </a>
                </div>
                <div class="share-url">
                    <input type="text" value="${url}" readonly>
                    <button class="copy-btn">Copy</button>
                </div>
                <button class="share-close">&times;</button>
            </div>
        `;

        document.body.appendChild(modal);

        // Copy button
        modal.querySelector('.copy-btn').addEventListener('click', async function() {
            const input = modal.querySelector('input');
            input.select();
            try {
                await navigator.clipboard.writeText(url);
                this.textContent = 'Copied!';
            } catch (error) {
                document.execCommand('copy');
                this.textContent = 'Copied!';
            }
        });

        // Close
        modal.querySelector('.share-close').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }

    // Initialize when DOM is ready
    if (typeof BaDere !== 'undefined') {
        BaDere.ready(init);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
