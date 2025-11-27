/**
 * Ba Dere Exchange - Cart JavaScript
 * Handles shopping cart functionality
 */

(function() {
    'use strict';

    // Cart state
    let cart = {};
    let isUpdating = false;

    /**
     * Initialize cart functionality
     */
    function init() {
        setupQuantityControls();
        setupRemoveButtons();
        setupUpdateForm();
        setupAddToCartForms();
        updateCartBadge();
    }

    /**
     * Setup quantity increment/decrement controls
     */
    function setupQuantityControls() {
        // Generic quantity controls
        document.querySelectorAll('[data-qty-wrapper]').forEach(wrapper => {
            const minusBtn = wrapper.querySelector('[data-qty-minus]');
            const plusBtn = wrapper.querySelector('[data-qty-plus]');
            const input = wrapper.querySelector('input[type="number"]');

            if (minusBtn && input) {
                minusBtn.addEventListener('click', () => {
                    const min = parseInt(input.min) || 1;
                    const current = parseInt(input.value) || 1;
                    if (current > min) {
                        input.value = current - 1;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }

            if (plusBtn && input) {
                plusBtn.addEventListener('click', () => {
                    const max = parseInt(input.max) || 99;
                    const current = parseInt(input.value) || 1;
                    if (current < max) {
                        input.value = current + 1;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }
        });

        // Cart page quantity inputs
        document.querySelectorAll('.cart-table input[type="number"]').forEach(input => {
            input.addEventListener('change', debounce(handleQuantityChange, 500));
        });
    }

    /**
     * Handle quantity change in cart
     * @param {Event} e - Change event
     */
    function handleQuantityChange(e) {
        const input = e.target;
        const row = input.closest('tr');
        const bookId = input.name.match(/quantities\[(\d+)\]/)?.[1];

        if (!bookId || !row) return;

        const quantity = parseInt(input.value) || 0;
        const priceCell = row.querySelector('td:nth-child(2)');
        const totalCell = row.querySelector('td:nth-child(4)');

        if (priceCell && totalCell) {
            const price = parseFloat(priceCell.textContent.replace(/[^0-9.]/g, ''));
            const lineTotal = price * quantity;
            totalCell.textContent = BaDere.formatCurrency(lineTotal);
        }

        // Update grand total
        updateGrandTotal();

        // Show update reminder
        showUpdateReminder();
    }

    /**
     * Calculate and update grand total
     */
    function updateGrandTotal() {
        const totalEl = document.querySelector('.cart-total');
        if (!totalEl) return;

        let grandTotal = 0;
        document.querySelectorAll('.cart-table tbody tr').forEach(row => {
            const totalCell = row.querySelector('td:nth-child(4)');
            if (totalCell) {
                grandTotal += parseFloat(totalCell.textContent.replace(/[^0-9.]/g, '')) || 0;
            }
        });

        totalEl.textContent = `Grand Total: ${BaDere.formatCurrency(grandTotal)}`;
    }

    /**
     * Show update cart reminder
     */
    function showUpdateReminder() {
        let reminder = document.querySelector('.cart-update-reminder');
        if (!reminder) {
            reminder = document.createElement('div');
            reminder.className = 'cart-update-reminder';
            reminder.innerHTML = '<span>You have unsaved changes.</span> <button type="submit" form="cartUpdateForm">Update Cart</button>';
            reminder.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: #2d3436;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 15px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 1000;
            `;
            reminder.querySelector('button').style.cssText = `
                background: #c96f4c;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                cursor: pointer;
            `;
            document.body.appendChild(reminder);
        }
    }

    /**
     * Setup remove buttons
     */
    function setupRemoveButtons() {
        BaDere.on('a[href*="cart_remove"]', 'click', async function(e) {
            e.preventDefault();

            const confirmed = await BaDere.confirm('Remove this item from cart?', {
                title: 'Remove Item',
                confirmText: 'Remove',
                confirmClass: 'btn-danger',
            });

            if (confirmed) {
                window.location.href = this.href;
            }
        });
    }

    /**
     * Setup cart update form
     */
    function setupUpdateForm() {
        const form = document.getElementById('cartUpdateForm');
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (isUpdating) return;
            isUpdating = true;

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Updating...';
            submitBtn.disabled = true;

            // Submit form normally (page reload)
            form.submit();
        });
    }

    /**
     * Setup add to cart forms (for AJAX submission)
     */
    function setupAddToCartForms() {
        document.querySelectorAll('.add-to-cart').forEach(form => {
            form.addEventListener('submit', async function(e) {
                // Check if we want to use AJAX
                if (!form.dataset.ajax) return; // Use normal form submission

                e.preventDefault();

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;

                try {
                    submitBtn.textContent = 'Adding...';
                    submitBtn.disabled = true;

                    const formData = new FormData(form);

                    // Get current page URL for redirect
                    formData.append('redirect', window.location.pathname + window.location.search);

                    const response = await BaDere.post(form.action, formData);

                    BaDere.toast('Added to cart!', 'success');
                    updateCartBadge();

                } catch (error) {
                    BaDere.toast(error.message || 'Could not add to cart', 'error');
                } finally {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            });
        });
    }

    /**
     * Add item to cart via AJAX
     * @param {number} bookId - Book ID
     * @param {number} quantity - Quantity to add
     * @returns {Promise}
     */
    async function addToCart(bookId, quantity = 1) {
        try {
            const response = await BaDere.post('../actions/cart_add.php', {
                book_id: bookId,
                quantity: quantity,
            });

            BaDere.toast('Added to cart!', 'success');
            updateCartBadge();
            return response;

        } catch (error) {
            BaDere.toast(error.message || 'Could not add to cart', 'error');
            throw error;
        }
    }

    /**
     * Remove item from cart
     * @param {number} bookId - Book ID
     * @returns {Promise}
     */
    async function removeFromCart(bookId) {
        try {
            const response = await BaDere.get(`../actions/cart_remove.php?book_id=${bookId}`);
            BaDere.toast('Removed from cart', 'success');
            updateCartBadge();
            return response;
        } catch (error) {
            BaDere.toast(error.message || 'Could not remove item', 'error');
            throw error;
        }
    }

    /**
     * Update cart badge count in header
     */
    async function updateCartBadge() {
        const badge = document.querySelector('.cart-badge, [data-cart-count]');
        if (!badge) return;

        try {
            const response = await BaDere.get('../actions/cart_count.php');
            const count = response.count || 0;

            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';

        } catch (error) {
            // Silently fail - not critical
            console.log('Could not update cart badge');
        }
    }

    /**
     * Get cart total
     * @returns {number} Cart total
     */
    function getCartTotal() {
        let total = 0;
        document.querySelectorAll('.cart-table tbody tr').forEach(row => {
            const totalCell = row.querySelector('td:nth-child(4)');
            if (totalCell) {
                total += parseFloat(totalCell.textContent.replace(/[^0-9.]/g, '')) || 0;
            }
        });
        return total;
    }

    /**
     * Get cart item count
     * @returns {number} Item count
     */
    function getCartItemCount() {
        let count = 0;
        document.querySelectorAll('.cart-table tbody input[type="number"]').forEach(input => {
            count += parseInt(input.value) || 0;
        });
        return count;
    }

    /**
     * Debounce helper
     */
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Initialize when DOM is ready
    if (typeof BaDere !== 'undefined') {
        BaDere.ready(init);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }

    // Expose public API
    window.Cart = {
        addToCart,
        removeFromCart,
        updateCartBadge,
        getCartTotal,
        getCartItemCount,
    };
})();
