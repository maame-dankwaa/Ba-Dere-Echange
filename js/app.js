/**
 * Ba Dere Exchange - Main Application JavaScript
 * Common utilities and functions used across the platform
 */

const BaDere = (function() {
    'use strict';

    // Configuration
    const config = {
        currency: 'GH₵',
        currencyCode: 'GHS',
        apiBasePath: '/actions',
        debounceDelay: 300,
        toastDuration: 3000,
    };

    /**
     * Format currency value
     * @param {number} amount - Amount to format
     * @returns {string} Formatted currency string
     */
    function formatCurrency(amount) {
        return config.currency + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Format number with commas
     * @param {number} num - Number to format
     * @returns {string} Formatted number string
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Debounce function execution
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in ms
     * @returns {Function} Debounced function
     */
    function debounce(func, wait = config.debounceDelay) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle function execution
     * @param {Function} func - Function to throttle
     * @param {number} limit - Limit in ms
     * @returns {Function} Throttled function
     */
    function throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Make an AJAX request
     * @param {string} url - Request URL
     * @param {object} options - Request options
     * @returns {Promise} Response promise
     */
    async function ajax(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        };

        const settings = { ...defaults, ...options };

        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            settings.headers['X-CSRF-Token'] = csrfToken;
        }

        // Handle FormData
        if (settings.body instanceof FormData) {
            // Don't set Content-Type for FormData - browser will set it with boundary
        } else if (settings.body && typeof settings.body === 'object') {
            settings.headers['Content-Type'] = 'application/x-www-form-urlencoded';
            settings.body = new URLSearchParams(settings.body).toString();
        }

        try {
            const response = await fetch(url, settings);

            // Try to parse as JSON, fall back to text
            const contentType = response.headers.get('content-type');
            let data;
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = await response.text();
            }

            if (!response.ok) {
                throw new Error(data.error || data || 'Request failed');
            }

            return data;
        } catch (error) {
            console.error('Ajax error:', error);
            throw error;
        }
    }

    /**
     * POST request helper
     * @param {string} url - Request URL
     * @param {object} data - Data to send
     * @returns {Promise} Response promise
     */
    function post(url, data) {
        return ajax(url, {
            method: 'POST',
            body: data,
        });
    }

    /**
     * GET request helper
     * @param {string} url - Request URL
     * @returns {Promise} Response promise
     */
    function get(url) {
        return ajax(url, { method: 'GET' });
    }

    /**
     * Show a toast notification
     * @param {string} message - Message to display
     * @param {string} type - Toast type (success, error, warning, info)
     * @param {number} duration - Duration in ms
     */
    function toast(message, type = 'info', duration = config.toastDuration) {
        // Remove existing toasts
        document.querySelectorAll('.toast').forEach(t => t.remove());

        const toastEl = document.createElement('div');
        toastEl.className = `toast toast-${type}`;
        toastEl.innerHTML = `
            <span class="toast-message">${escapeHtml(message)}</span>
            <button class="toast-close" aria-label="Close">&times;</button>
        `;

        // Add styles if not already present
        if (!document.getElementById('toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                .toast {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    padding: 12px 20px;
                    border-radius: 4px;
                    color: white;
                    font-size: 14px;
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    animation: slideIn 0.3s ease;
                }
                .toast-success { background-color: #27ae60; }
                .toast-error { background-color: #e74c3c; }
                .toast-warning { background-color: #f39c12; }
                .toast-info { background-color: #3498db; }
                .toast-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 18px;
                    cursor: pointer;
                    padding: 0;
                    margin-left: 10px;
                }
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(toastEl);

        // Close button
        toastEl.querySelector('.toast-close').addEventListener('click', () => {
            toastEl.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toastEl.remove(), 300);
        });

        // Auto-remove
        setTimeout(() => {
            if (toastEl.parentNode) {
                toastEl.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toastEl.remove(), 300);
            }
        }, duration);
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Parse query string to object
     * @param {string} queryString - Query string to parse
     * @returns {object} Parsed object
     */
    function parseQueryString(queryString = window.location.search) {
        const params = new URLSearchParams(queryString);
        const result = {};
        for (const [key, value] of params) {
            result[key] = value;
        }
        return result;
    }

    /**
     * Build query string from object
     * @param {object} params - Parameters object
     * @returns {string} Query string
     */
    function buildQueryString(params) {
        return new URLSearchParams(params).toString();
    }

    /**
     * Show loading spinner
     * @param {HTMLElement} element - Element to show spinner in
     * @param {string} size - Spinner size (small, medium, large)
     */
    function showSpinner(element, size = 'medium') {
        const spinner = document.createElement('div');
        spinner.className = `spinner spinner-${size}`;
        spinner.innerHTML = '<div class="spinner-inner"></div>';

        if (!document.getElementById('spinner-styles')) {
            const style = document.createElement('style');
            style.id = 'spinner-styles';
            style.textContent = `
                .spinner {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }
                .spinner-inner {
                    border: 3px solid #f3f3f3;
                    border-top: 3px solid #c96f4c;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }
                .spinner-small .spinner-inner { width: 16px; height: 16px; }
                .spinner-medium .spinner-inner { width: 24px; height: 24px; }
                .spinner-large .spinner-inner { width: 40px; height: 40px; }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }

        element.innerHTML = '';
        element.appendChild(spinner);
    }

    /**
     * Confirm dialog
     * @param {string} message - Confirmation message
     * @param {object} options - Dialog options
     * @returns {Promise<boolean>} User confirmation
     */
    function confirm(message, options = {}) {
        const {
            title = 'Confirm',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            confirmClass = 'btn-primary',
            cancelClass = 'btn-secondary',
        } = options;

        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'modal-overlay';
            overlay.innerHTML = `
                <div class="modal-dialog">
                    <h3 class="modal-title">${escapeHtml(title)}</h3>
                    <p class="modal-message">${escapeHtml(message)}</p>
                    <div class="modal-actions">
                        <button class="btn ${cancelClass}" data-action="cancel">${escapeHtml(cancelText)}</button>
                        <button class="btn ${confirmClass}" data-action="confirm">${escapeHtml(confirmText)}</button>
                    </div>
                </div>
            `;

            if (!document.getElementById('modal-styles')) {
                const style = document.createElement('style');
                style.id = 'modal-styles';
                style.textContent = `
                    .modal-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(0,0,0,0.5);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 10001;
                    }
                    .modal-dialog {
                        background: white;
                        padding: 24px;
                        border-radius: 8px;
                        max-width: 400px;
                        width: 90%;
                        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                    }
                    .modal-title {
                        margin: 0 0 12px;
                        font-size: 18px;
                    }
                    .modal-message {
                        margin: 0 0 20px;
                        color: #636e72;
                    }
                    .modal-actions {
                        display: flex;
                        gap: 10px;
                        justify-content: flex-end;
                    }
                `;
                document.head.appendChild(style);
            }

            document.body.appendChild(overlay);

            overlay.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                if (action === 'confirm') {
                    resolve(true);
                    overlay.remove();
                } else if (action === 'cancel' || e.target === overlay) {
                    resolve(false);
                    overlay.remove();
                }
            });
        });
    }

    /**
     * Validate form
     * @param {HTMLFormElement} form - Form to validate
     * @returns {object} Validation result
     */
    function validateForm(form) {
        const errors = {};
        const formData = new FormData(form);

        form.querySelectorAll('[required]').forEach(field => {
            const name = field.name;
            const value = formData.get(name);

            if (!value || value.trim() === '') {
                const label = form.querySelector(`label[for="${field.id}"]`)?.textContent
                    || field.placeholder
                    || name;
                errors[name] = `${label} is required`;
            }
        });

        // Email validation
        form.querySelectorAll('input[type="email"]').forEach(field => {
            const value = field.value;
            if (value && !isValidEmail(value)) {
                errors[field.name] = 'Please enter a valid email address';
            }
        });

        // Min length validation
        form.querySelectorAll('[minlength]').forEach(field => {
            const minLength = parseInt(field.getAttribute('minlength'));
            if (field.value && field.value.length < minLength) {
                errors[field.name] = `Must be at least ${minLength} characters`;
            }
        });

        return {
            isValid: Object.keys(errors).length === 0,
            errors,
        };
    }

    /**
     * Check if email is valid
     * @param {string} email - Email to validate
     * @returns {boolean} Is valid
     */
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    /**
     * Show form errors
     * @param {HTMLFormElement} form - Form element
     * @param {object} errors - Errors object
     */
    function showFormErrors(form, errors) {
        // Clear existing errors
        form.querySelectorAll('.field-error').forEach(el => el.remove());
        form.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));

        // Show new errors
        Object.entries(errors).forEach(([name, message]) => {
            const field = form.querySelector(`[name="${name}"]`);
            if (field) {
                field.classList.add('has-error');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                errorDiv.textContent = message;
                field.parentNode.appendChild(errorDiv);
            }
        });
    }

    /**
     * Handle local storage
     */
    const storage = {
        get(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (e) {
                return defaultValue;
            }
        },
        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch (e) {
                console.error('Storage error:', e);
            }
        },
        remove(key) {
            localStorage.removeItem(key);
        },
    };

    /**
     * Event delegation helper
     * @param {string} selector - Event target selector
     * @param {string} eventType - Event type
     * @param {Function} handler - Event handler
     */
    function on(selector, eventType, handler) {
        document.addEventListener(eventType, (e) => {
            const target = e.target.closest(selector);
            if (target) {
                handler.call(target, e, target);
            }
        });
    }

    /**
     * Initialize components when DOM is ready
     */
    function ready(callback) {
        if (document.readyState !== 'loading') {
            callback();
        } else {
            document.addEventListener('DOMContentLoaded', callback);
        }
    }

    // Public API
    return {
        config,
        formatCurrency,
        formatNumber,
        debounce,
        throttle,
        ajax,
        post,
        get,
        toast,
        escapeHtml,
        parseQueryString,
        buildQueryString,
        showSpinner,
        confirm,
        validateForm,
        showFormErrors,
        isValidEmail,
        storage,
        on,
        ready,
    };
})();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BaDere;
}
