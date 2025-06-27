// Webshop JavaScript functionality

// Cart management
function addToCart(productId, quantity = 1) {
    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            showNotification('Product toegevoegd aan winkelwagen!', 'success');
        } else {
            showNotification(data.error || 'Er is een fout opgetreden', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Er is een fout opgetreden', 'error');
    });
}

// Update cart count in navigation
function updateCartCount(count) {
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
}

// Show notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Laden...';
            }
        });
    });
});

// Cart functionality
class Cart {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateCartBadge();
    }

    bindEvents() {
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart')) {
                e.preventDefault();
                this.addToCart(e.target);
            }
        });

        // Cart update events
        document.addEventListener('cart:updated', () => {
            this.updateCartBadge();
        });
    }

    async addToCart(button) {
        const productId = button.getAttribute('data-product-id');
        const productName = button.getAttribute('data-product-name');
        const quantity = 1;

        // Show loading state
        const originalText = button.textContent;
        button.textContent = 'Adding...';
        button.disabled = true;
        button.classList.add('btn-loading');

        try {
            const response = await fetch('/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast(`${productName} added to cart!`, 'success');
                this.updateCartBadge();
                
                // Dispatch custom event
                document.dispatchEvent(new CustomEvent('cart:item-added', {
                    detail: { productId, quantity, cart: data.cart }
                }));
            } else {
                this.showToast(data.message || 'Error adding item to cart', 'error');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            this.showToast('Error adding item to cart', 'error');
        } finally {
            // Reset button state
            button.textContent = originalText;
            button.disabled = false;
            button.classList.remove('btn-loading');
        }
    }

    async updateCartBadge() {
        try {
            const response = await fetch('/cart/count', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                const badge = document.querySelector('.cart-badge');
                if (badge) {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'block' : 'none';
                }
            }
        } catch (error) {
            console.error('Error updating cart badge:', error);
        }
    }

    showToast(message, type = 'success') {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.toast');
        existingToasts.forEach(toast => toast.remove());

        // Create new toast
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;

        document.body.appendChild(toast);

        // Show toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }
}

// Category filtering and sorting
class CategoryFilters {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Sort dropdown
        const sortSelect = document.getElementById('sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.applySorting(e.target.value);
            });
        }

        // View toggle buttons
        const gridViewBtn = document.getElementById('grid-view');
        const listViewBtn = document.getElementById('list-view');
        
        if (gridViewBtn && listViewBtn) {
            gridViewBtn.addEventListener('click', () => this.setView('grid'));
            listViewBtn.addEventListener('click', () => this.setView('list'));
        }

        // Price range filter
        const priceRange = document.getElementById('price-range');
        if (priceRange) {
            priceRange.addEventListener('change', (e) => {
                this.applyPriceFilter(e.target.value);
            });
        }
    }

    applySorting(sortValue) {
        const url = new URL(window.location);
        url.searchParams.set('sort', sortValue);
        url.searchParams.set('page', '1'); // Reset to first page
        window.location.href = url.toString();
    }

    setView(viewType) {
        const container = document.getElementById('products-container');
        const gridBtn = document.getElementById('grid-view');
        const listBtn = document.getElementById('list-view');

        if (!container) return;

        if (viewType === 'grid') {
            container.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6';
            gridBtn.classList.add('text-blue-600', 'bg-blue-50');
            listBtn.classList.remove('text-blue-600', 'bg-blue-50');
        } else {
            container.className = 'space-y-4';
            listBtn.classList.add('text-blue-600', 'bg-blue-50');
            gridBtn.classList.remove('text-blue-600', 'bg-blue-50');
        }

        // Save preference
        localStorage.setItem('product-view', viewType);
    }

    applyPriceFilter(priceRange) {
        const url = new URL(window.location);
        url.searchParams.set('price', priceRange);
        url.searchParams.set('page', '1'); // Reset to first page
        window.location.href = url.toString();
    }
}

// Search functionality
class Search {
    constructor() {
        this.searchInput = document.getElementById('search-input');
        this.searchForm = document.getElementById('search-form');
        this.init();
    }

    init() {
        if (this.searchInput && this.searchForm) {
            this.bindEvents();
        }
    }

    bindEvents() {
        // Auto-suggest functionality
        this.searchInput.addEventListener('input', this.debounce((e) => {
            this.handleSearch(e.target.value);
        }, 300));

        // Form submission
        this.searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.performSearch();
        });
    }

    async handleSearch(query) {
        if (query.length < 2) {
            this.hideSuggestions();
            return;
        }

        try {
            const response = await fetch(`/api/search/suggestions?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                this.showSuggestions(data.suggestions);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    showSuggestions(suggestions) {
        // Implementation for showing search suggestions
        // This would create a dropdown with suggested products/categories
    }

    hideSuggestions() {
        // Hide suggestions dropdown
    }

    performSearch() {
        const query = this.searchInput.value.trim();
        if (query) {
            window.location.href = `/products/search?q=${encodeURIComponent(query)}`;
        }
    }

    debounce(func, wait) {
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
}

// Image lazy loading
class LazyLoader {
    constructor() {
        this.images = document.querySelectorAll('img[data-src]');
        this.init();
    }

    init() {
        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadImage(entry.target);
                        this.observer.unobserve(entry.target);
                    }
                });
            });

            this.images.forEach(img => this.observer.observe(img));
        } else {
            // Fallback for older browsers
            this.images.forEach(img => this.loadImage(img));
        }
    }

    loadImage(img) {
        img.src = img.dataset.src;
        img.onload = () => {
            img.classList.add('loaded');
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    new Cart();
    new CategoryFilters();
    new Search();
    new LazyLoader();

    // Restore view preference
    const savedView = localStorage.getItem('product-view');
    if (savedView && document.getElementById('products-container')) {
        const filters = new CategoryFilters();
        filters.setView(savedView);
    }

    // Initialize any tooltips or popovers
    initializeTooltips();
    
    // Initialize mobile menu
    initializeMobileMenu();
});

// Utility functions
function initializeTooltips() {
    // Add tooltip functionality if needed
}

function initializeMobileMenu() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
}

// Global utility functions
window.formatPrice = function(price) {
    return new Intl.NumberFormat('nl-NL', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
};

window.showConfirmDialog = function(message, callback) {
    if (confirm(message)) {
        callback();
    }
};

