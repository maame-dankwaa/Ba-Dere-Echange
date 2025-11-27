/**
 * Ba Dere Exchange - Book Search & Filter JavaScript
 * Handles search, filtering, and browsing functionality
 */

(function() {
    'use strict';

    // Debounce delay for search
    const SEARCH_DELAY = 300;

    /**
     * Initialize search and filter functionality
     */
    function init() {
        setupLiveSearch();
        setupFilters();
        setupSorting();
        setupPriceRange();
        setupCategoryFilter();
        setupViewToggle();
        setupInfiniteScroll();
        setupFilterCollapse();
    }

    /**
     * Setup live search with debouncing
     */
    function setupLiveSearch() {
        const searchInput = document.querySelector('.search-input, [name="q"], #bookSearch');
        const searchForm = searchInput?.closest('form');
        const resultsContainer = document.querySelector('.search-results, .books-grid, [data-results]');

        if (!searchInput) return;

        let searchTimeout = null;
        let lastQuery = searchInput.value;

        // Live search on input
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            // Debounce
            searchTimeout = setTimeout(() => {
                if (query !== lastQuery) {
                    lastQuery = query;

                    if (query.length === 0) {
                        // Clear search - reload without query
                        if (searchForm) {
                            updateUrlParams({ q: null });
                            loadBooks(null, resultsContainer);
                        }
                    } else if (query.length >= 2) {
                        // Perform search
                        performSearch(query, resultsContainer);
                    }
                }
            }, SEARCH_DELAY);
        });

        // Submit form on Enter
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const query = searchInput.value.trim();
                if (query.length >= 2) {
                    performSearch(query, resultsContainer);
                    updateUrlParams({ q: query });
                }
            });
        }

        // Clear search button
        const clearBtn = document.querySelector('.search-clear, [data-search-clear]');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                lastQuery = '';
                updateUrlParams({ q: null });
                loadBooks(null, resultsContainer);
            });
        }
    }

    /**
     * Perform search via AJAX
     */
    async function performSearch(query, container) {
        if (!container) return;

        // Show loading state
        showLoading(container);

        try {
            const params = new URLSearchParams(window.location.search);
            params.set('q', query);
            params.set('ajax', '1');

            const response = await fetch(`/actions/browse_books.php?${params.toString()}`);

            if (!response.ok) throw new Error('Search failed');

            const html = await response.text();
            updateResults(container, html);
            updateUrlParams({ q: query });

        } catch (error) {
            console.error('Search error:', error);
            showError(container, 'Search failed. Please try again.');
        }
    }

    /**
     * Setup filter controls
     */
    function setupFilters() {
        const filterForm = document.querySelector('.filter-form, [data-filters]');
        const resultsContainer = document.querySelector('.books-grid, [data-results]');

        if (!filterForm) return;

        // Handle checkbox and radio filters
        filterForm.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(input => {
            input.addEventListener('change', () => {
                applyFilters(filterForm, resultsContainer);
            });
        });

        // Handle select filters
        filterForm.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => {
                applyFilters(filterForm, resultsContainer);
            });
        });

        // Clear filters button
        const clearBtn = filterForm.querySelector('.clear-filters, [data-clear-filters]');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                clearFilters(filterForm, resultsContainer);
            });
        }

        // Apply filters button (for mobile)
        const applyBtn = filterForm.querySelector('.apply-filters');
        if (applyBtn) {
            applyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                applyFilters(filterForm, resultsContainer);
                closeFilterSidebar();
            });
        }
    }

    /**
     * Apply filters
     */
    async function applyFilters(form, container) {
        if (!container) return;

        showLoading(container);

        const formData = new FormData(form);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value);
            }
        }

        // Preserve search query
        const searchQuery = document.querySelector('[name="q"]')?.value;
        if (searchQuery) {
            params.set('q', searchQuery);
        }

        params.set('ajax', '1');

        try {
            const response = await fetch(`/actions/browse_books.php?${params.toString()}`);
            if (!response.ok) throw new Error('Filter failed');

            const html = await response.text();
            updateResults(container, html);
            updateUrlParams(Object.fromEntries(params));

        } catch (error) {
            console.error('Filter error:', error);
            showError(container, 'Failed to apply filters.');
        }
    }

    /**
     * Clear all filters
     */
    function clearFilters(form, container) {
        // Reset form
        form.reset();

        // Clear URL params
        updateUrlParams({}, true);

        // Reload books
        loadBooks(null, container);
    }

    /**
     * Setup sorting dropdown
     */
    function setupSorting() {
        const sortSelect = document.querySelector('.sort-select, [name="sort"]');
        const resultsContainer = document.querySelector('.books-grid, [data-results]');

        if (!sortSelect) return;

        sortSelect.addEventListener('change', function() {
            const params = new URLSearchParams(window.location.search);
            params.set('sort', this.value);
            params.set('ajax', '1');

            showLoading(resultsContainer);

            fetch(`/actions/browse_books.php?${params.toString()}`)
                .then(response => response.text())
                .then(html => {
                    updateResults(resultsContainer, html);
                    updateUrlParams({ sort: this.value });
                })
                .catch(error => {
                    console.error('Sort error:', error);
                    showError(resultsContainer, 'Failed to sort results.');
                });
        });
    }

    /**
     * Setup price range slider
     */
    function setupPriceRange() {
        const minInput = document.querySelector('[name="min_price"]');
        const maxInput = document.querySelector('[name="max_price"]');
        const rangeSlider = document.querySelector('.price-range-slider');
        const displayMin = document.querySelector('.price-display-min');
        const displayMax = document.querySelector('.price-display-max');

        if (!minInput || !maxInput) return;

        // Debounced price filter
        let priceTimeout = null;

        const updatePriceFilter = () => {
            if (priceTimeout) clearTimeout(priceTimeout);
            priceTimeout = setTimeout(() => {
                const filterForm = document.querySelector('.filter-form, [data-filters]');
                const resultsContainer = document.querySelector('.books-grid, [data-results]');
                if (filterForm) {
                    applyFilters(filterForm, resultsContainer);
                }
            }, 500);
        };

        [minInput, maxInput].forEach(input => {
            input.addEventListener('input', function() {
                // Update display
                if (displayMin && input === minInput) {
                    displayMin.textContent = formatCurrency(this.value || 0);
                }
                if (displayMax && input === maxInput) {
                    displayMax.textContent = formatCurrency(this.value || 0);
                }

                updatePriceFilter();
            });
        });

        // Handle range slider if present
        if (rangeSlider) {
            setupDualRangeSlider(rangeSlider, minInput, maxInput);
        }
    }

    /**
     * Setup dual range slider
     */
    function setupDualRangeSlider(container, minInput, maxInput) {
        const minSlider = container.querySelector('.range-min');
        const maxSlider = container.querySelector('.range-max');
        const track = container.querySelector('.range-track');

        if (!minSlider || !maxSlider) return;

        const updateTrack = () => {
            const min = parseInt(minSlider.value);
            const max = parseInt(maxSlider.value);
            const range = maxSlider.max - maxSlider.min;

            const minPercent = ((min - maxSlider.min) / range) * 100;
            const maxPercent = ((max - maxSlider.min) / range) * 100;

            if (track) {
                track.style.left = minPercent + '%';
                track.style.width = (maxPercent - minPercent) + '%';
            }
        };

        minSlider.addEventListener('input', function() {
            const max = parseInt(maxSlider.value);
            if (parseInt(this.value) > max) {
                this.value = max;
            }
            minInput.value = this.value;
            minInput.dispatchEvent(new Event('input'));
            updateTrack();
        });

        maxSlider.addEventListener('input', function() {
            const min = parseInt(minSlider.value);
            if (parseInt(this.value) < min) {
                this.value = min;
            }
            maxInput.value = this.value;
            maxInput.dispatchEvent(new Event('input'));
            updateTrack();
        });

        updateTrack();
    }

    /**
     * Setup category filter with hierarchy
     */
    function setupCategoryFilter() {
        const categoryLinks = document.querySelectorAll('.category-filter a, [data-category]');
        const resultsContainer = document.querySelector('.books-grid, [data-results]');

        categoryLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                const categoryId = this.dataset.categoryId || this.href.split('category_id=')[1];

                // Update active state
                categoryLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                // Apply filter
                const params = new URLSearchParams(window.location.search);
                if (categoryId && categoryId !== 'all') {
                    params.set('category_id', categoryId);
                } else {
                    params.delete('category_id');
                }
                params.set('ajax', '1');

                showLoading(resultsContainer);

                fetch(`/actions/browse_books.php?${params.toString()}`)
                    .then(response => response.text())
                    .then(html => {
                        updateResults(resultsContainer, html);
                        updateUrlParams({ category_id: categoryId !== 'all' ? categoryId : null });
                    })
                    .catch(error => {
                        console.error('Category filter error:', error);
                    });
            });
        });
    }

    /**
     * Setup grid/list view toggle
     */
    function setupViewToggle() {
        const viewButtons = document.querySelectorAll('.view-toggle button, [data-view]');
        const container = document.querySelector('.books-grid, [data-results]');

        if (viewButtons.length === 0 || !container) return;

        // Load saved preference
        const savedView = localStorage.getItem('badere_book_view') || 'grid';
        container.classList.add(`view-${savedView}`);
        viewButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === savedView);
        });

        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;

                // Update buttons
                viewButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Update container
                container.classList.remove('view-grid', 'view-list');
                container.classList.add(`view-${view}`);

                // Save preference
                localStorage.setItem('badere_book_view', view);
            });
        });
    }

    /**
     * Setup infinite scroll for book listings
     */
    function setupInfiniteScroll() {
        const container = document.querySelector('.books-grid, [data-results]');
        const loadMoreBtn = document.querySelector('.load-more, [data-load-more]');

        if (!container) return;

        let currentPage = parseInt(container.dataset.page) || 1;
        let totalPages = parseInt(container.dataset.totalPages) || 1;
        let isLoading = false;

        // Load more button click
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                if (!isLoading && currentPage < totalPages) {
                    loadMoreBooks();
                }
            });
        }

        // Infinite scroll observer
        const sentinel = document.querySelector('.scroll-sentinel');
        if (sentinel) {
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && !isLoading && currentPage < totalPages) {
                    loadMoreBooks();
                }
            }, { rootMargin: '100px' });

            observer.observe(sentinel);
        }

        async function loadMoreBooks() {
            isLoading = true;

            if (loadMoreBtn) {
                loadMoreBtn.disabled = true;
                loadMoreBtn.textContent = 'Loading...';
            }

            const params = new URLSearchParams(window.location.search);
            params.set('page', currentPage + 1);
            params.set('ajax', '1');
            params.set('append', '1');

            try {
                const response = await fetch(`/actions/browse_books.php?${params.toString()}`);
                const html = await response.text();

                // Append new books
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const newBooks = temp.querySelectorAll('.book-card');

                newBooks.forEach(book => {
                    container.insertBefore(book, sentinel || null);
                });

                currentPage++;
                container.dataset.page = currentPage;

                // Check if more pages
                if (currentPage >= totalPages) {
                    if (loadMoreBtn) loadMoreBtn.style.display = 'none';
                    if (sentinel) sentinel.style.display = 'none';
                }

            } catch (error) {
                console.error('Load more error:', error);
            } finally {
                isLoading = false;
                if (loadMoreBtn && currentPage < totalPages) {
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Load More';
                }
            }
        }
    }

    /**
     * Setup filter sidebar collapse on mobile
     */
    function setupFilterCollapse() {
        const filterToggle = document.querySelector('.filter-toggle, [data-filter-toggle]');
        const filterSidebar = document.querySelector('.filter-sidebar, [data-filters]');

        if (!filterToggle || !filterSidebar) return;

        filterToggle.addEventListener('click', () => {
            filterSidebar.classList.toggle('open');
            document.body.classList.toggle('filters-open');
        });

        // Close on overlay click
        const overlay = document.querySelector('.filter-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeFilterSidebar);
        }

        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeFilterSidebar();
            }
        });
    }

    /**
     * Close filter sidebar
     */
    function closeFilterSidebar() {
        const filterSidebar = document.querySelector('.filter-sidebar, [data-filters]');
        if (filterSidebar) {
            filterSidebar.classList.remove('open');
            document.body.classList.remove('filters-open');
        }
    }

    /**
     * Load books
     */
    async function loadBooks(params, container) {
        if (!container) return;

        showLoading(container);

        const urlParams = new URLSearchParams(params || window.location.search);
        urlParams.set('ajax', '1');

        try {
            const response = await fetch(`/actions/browse_books.php?${urlParams.toString()}`);
            const html = await response.text();
            updateResults(container, html);

        } catch (error) {
            console.error('Load books error:', error);
            showError(container, 'Failed to load books.');
        }
    }

    /**
     * Update results container
     */
    function updateResults(container, html) {
        if (!container) return;

        container.innerHTML = html;
        container.classList.remove('loading');

        // Re-initialize any interactive elements in results
        initResultCards();
    }

    /**
     * Initialize interactive elements in result cards
     */
    function initResultCards() {
        // Quick add to cart
        document.querySelectorAll('.quick-add-cart').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();

                const bookId = this.dataset.bookId;
                if (!bookId) return;

                this.disabled = true;
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="spinner-small"></span>';

                try {
                    const formData = new FormData();
                    formData.append('book_id', bookId);
                    formData.append('quantity', '1');

                    const response = await fetch('../actions/cart_add.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (response.ok) {
                        this.innerHTML = '✓ Added';
                        this.classList.add('added');

                        // Update cart count
                        if (typeof BaDere !== 'undefined' && BaDere.updateCartCount) {
                            BaDere.updateCartCount();
                        }

                        if (typeof BaDere !== 'undefined') {
                            BaDere.toast('Added to cart!', 'success');
                        }

                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.classList.remove('added');
                            this.disabled = false;
                        }, 2000);
                    } else {
                        throw new Error('Add to cart failed');
                    }

                } catch (error) {
                    this.innerHTML = originalText;
                    this.disabled = false;
                    if (typeof BaDere !== 'undefined') {
                        BaDere.toast('Could not add to cart', 'error');
                    }
                }
            });
        });

        // Quick wishlist toggle
        document.querySelectorAll('.quick-wishlist').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();

                const bookId = this.dataset.bookId;
                if (!bookId) return;

                const isInWishlist = this.classList.contains('in-wishlist');
                const url = isInWishlist
                    ? `/actions/wishlist_remove.php?book_id=${bookId}`
                    : '/actions/wishlist_add.php';

                try {
                    if (isInWishlist) {
                        await fetch(url);
                    } else {
                        const formData = new FormData();
                        formData.append('book_id', bookId);
                        await fetch(url, { method: 'POST', body: formData });
                    }

                    this.classList.toggle('in-wishlist');
                    this.title = this.classList.contains('in-wishlist') ? 'Remove from wishlist' : 'Add to wishlist';

                } catch (error) {
                    console.error('Wishlist error:', error);
                }
            });
        });
    }

    /**
     * Show loading state
     */
    function showLoading(container) {
        if (!container) return;

        container.classList.add('loading');
        container.innerHTML = `
            <div class="loading-indicator">
                <div class="spinner"></div>
                <p>Loading books...</p>
            </div>
        `;
    }

    /**
     * Show error message
     */
    function showError(container, message) {
        if (!container) return;

        container.classList.remove('loading');
        container.innerHTML = `
            <div class="error-message">
                <p>${message}</p>
                <button class="retry-btn" onclick="location.reload()">Retry</button>
            </div>
        `;
    }

    /**
     * Update URL parameters without page reload
     */
    function updateUrlParams(params, clear = false) {
        const url = new URL(window.location.href);

        if (clear) {
            url.search = '';
        }

        for (const [key, value] of Object.entries(params)) {
            if (value === null || value === undefined || value === '') {
                url.searchParams.delete(key);
            } else if (key !== 'ajax' && key !== 'append') {
                url.searchParams.set(key, value);
            }
        }

        // Remove ajax param from URL
        url.searchParams.delete('ajax');
        url.searchParams.delete('append');

        window.history.replaceState({}, '', url.toString());
    }

    /**
     * Format currency
     */
    function formatCurrency(amount) {
        if (typeof BaDere !== 'undefined' && BaDere.formatCurrency) {
            return BaDere.formatCurrency(amount);
        }
        return 'GH₵ ' + parseFloat(amount).toFixed(2);
    }

    // Add search/filter styles
    function addStyles() {
        if (document.getElementById('search-styles')) return;

        const style = document.createElement('style');
        style.id = 'search-styles';
        style.textContent = `
            .loading-indicator {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 60px 20px;
                color: var(--text-muted, #6c757d);
            }
            .spinner {
                width: 40px;
                height: 40px;
                border: 3px solid var(--border-color, #dee2e6);
                border-top-color: var(--primary-color, #28a745);
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }
            .spinner-small {
                display: inline-block;
                width: 14px;
                height: 14px;
                border: 2px solid currentColor;
                border-top-color: transparent;
                border-radius: 50%;
                animation: spin 0.6s linear infinite;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            .error-message {
                text-align: center;
                padding: 40px;
                color: var(--danger-color, #dc3545);
            }
            .retry-btn {
                margin-top: 15px;
                padding: 8px 20px;
                background: var(--primary-color, #28a745);
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .view-grid .book-card {
                display: flex;
                flex-direction: column;
            }
            .view-list .book-card {
                display: flex;
                flex-direction: row;
                gap: 20px;
            }
            .view-list .book-card img {
                width: 120px;
                height: auto;
            }
            .filter-sidebar {
                transition: transform 0.3s ease;
            }
            @media (max-width: 768px) {
                .filter-sidebar {
                    position: fixed;
                    top: 0;
                    left: -100%;
                    width: 280px;
                    height: 100%;
                    background: white;
                    z-index: 1000;
                    overflow-y: auto;
                    padding: 20px;
                }
                .filter-sidebar.open {
                    left: 0;
                }
                body.filters-open::after {
                    content: '';
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.5);
                    z-index: 999;
                }
            }
            .quick-add-cart.added {
                background: var(--success-color, #28a745);
                color: white;
            }
            .quick-wishlist.in-wishlist {
                color: var(--danger-color, #dc3545);
            }
            .price-range-slider {
                position: relative;
                height: 30px;
            }
            .range-track {
                position: absolute;
                height: 4px;
                background: var(--primary-color, #28a745);
                top: 50%;
                transform: translateY(-50%);
            }
        `;
        document.head.appendChild(style);
    }

    // Initialize
    addStyles();

    if (typeof BaDere !== 'undefined') {
        BaDere.ready(init);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }

    // Initialize interactive elements on page load
    document.addEventListener('DOMContentLoaded', initResultCards);
})();
