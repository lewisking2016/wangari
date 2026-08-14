/**
 * Wangari — Main JavaScript
 * Premium animations, interactions, and functionality
 */

'use strict';

// ═══════════════════════════════════════════════════════════════
// GLOBAL APP OBJECT
// ═══════════════════════════════════════════════════════════════

const WangariApp = {
    // Configuration
    config: {
        scrollThreshold: 100,
        animationDuration: 400,
    },

    // Initialize app
    init() {
        this.setupIntersectionObserver();
        this.setupMobileMenu();
        this.setupSmoothScroll();
        this.setupFormValidation();
        this.setupCartFunctionality();
        this.setupShopFilters();
        this.setupCounterAnimation();
        this.setupModalHandlers();
        console.log('Wangari App initialized');
    },

    // ═══════════════════════════════════════════════════════════════
    // INTERSECTION OBSERVER FOR SCROLL ANIMATIONS
    // ═══════════════════════════════════════════════════════════════

    setupIntersectionObserver() {
        const observerOptions = {
            root: null,
            rootMargin: '0px 0px -100px 0px',
            threshold: 0.1,
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    // Add animation class
                    entry.target.classList.add('fade-up');
                    entry.target.style.opacity = '1';
                    
                    // Stop observing after animation
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe all elements with fade-up class that haven't animated yet
        document.querySelectorAll('.fade-up:not(.animated)').forEach((el) => {
            el.style.opacity = '0';
            observer.observe(el);
        });
    },

    // ═══════════════════════════════════════════════════════════════
    // MOBILE MENU TOGGLE
    // ═══════════════════════════════════════════════════════════════

    setupMobileMenu() {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mainNav = document.getElementById('main-nav');

        if (!mobileMenuBtn) return;

        mobileMenuBtn.addEventListener('click', () => {
            mainNav.classList.toggle('active');
            mobileMenuBtn.classList.toggle('active');

            // Animate hamburger to X
            const spans = mobileMenuBtn.querySelectorAll('span');
            if (mobileMenuBtn.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translateY(12px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translateY(-12px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });

        // Close menu on link click
        mainNav.querySelectorAll('.nav-link').forEach((link) => {
            link.addEventListener('click', () => {
                mainNav.classList.remove('active');
                mobileMenuBtn.classList.remove('active');
                const spans = mobileMenuBtn.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            });
        });

        // Close menu on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.navbar')) {
                mainNav.classList.remove('active');
                mobileMenuBtn.classList.remove('active');
            }
        });
    },

    // ═══════════════════════════════════════════════════════════════
    // SMOOTH SCROLL
    // ═══════════════════════════════════════════════════════════════

    setupSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                }
            });
        });
    },

    // ═══════════════════════════════════════════════════════════════
    // FORM VALIDATION
    // ═══════════════════════════════════════════════════════════════

    setupFormValidation() {
        const forms = document.querySelectorAll('[data-validate="true"]');

        forms.forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                
                // Get form data
                const formData = new FormData(form);
                const data = Object.fromEntries(formData);

                // Basic validation
                if (!data.name || !data.email || !data.phone) {
                    this.showNotification('Please fill all required fields', 'error');
                    return;
                }

                // Email validation
                if (!this.validateEmail(data.email)) {
                    this.showNotification('Please enter a valid email', 'error');
                    return;
                }

                // Phone validation (Kenya format)
                if (!this.validatePhone(data.phone)) {
                    this.showNotification('Please enter a valid phone number', 'error');
                    return;
                }

                // Success
                this.showNotification('Thank you! We will be in touch soon.', 'success');
                form.reset();

                // Close modal if in one
                const modal = form.closest('.modal');
                if (modal) {
                    setTimeout(() => this.closeModal(modal.id), 1000);
                }
            });
        });
    },

    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    validatePhone(phone) {
        const phoneRegex = /^(\+?254|0)?[1-9]\d{8}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    },

    // ═══════════════════════════════════════════════════════════════
    // NOTIFICATION SYSTEM
    // ═══════════════════════════════════════════════════════════════

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            max-width: 400px;
            z-index: 3000;
            animation: slideIn 0.3s ease;
        `;
        notification.innerHTML = `
            <div>${message}</div>
            <button onclick="this.parentElement.remove()" style="
                background: none;
                border: none;
                color: inherit;
                cursor: pointer;
                font-size: 1.2rem;
                margin-left: auto;
            ">&times;</button>
        `;
        document.body.appendChild(notification);

        // Auto remove after 4 seconds
        setTimeout(() => notification.remove(), 4000);
    },

    // ═══════════════════════════════════════════════════════════════
    // CART FUNCTIONALITY
    // ═══════════════════════════════════════════════════════════════

    setupCartFunctionality() {
        const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
        
        addToCartBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = btn.getAttribute('data-id');
                const quantity = parseInt(btn.getAttribute('data-qty')) || 1;
                this.addToCart(productId, quantity);
            });
        });

        this.updateCartCount();
    },

    // SHOP FILTERS
    setupShopFilters() {
        const filterForm = document.querySelector('.product-filters');
        if (!filterForm) return;

        const typeChecks = Array.from(filterForm.querySelectorAll('input[name="type"]'));
        const availabilityChecks = Array.from(filterForm.querySelectorAll('input[name="availability"]'));

        const apply = () => {
            const selectedTypes = typeChecks.filter(i => i.checked).map(i => i.value);
            const inStockOnly = availabilityChecks.find(i => i.value === 'in-stock')?.checked;

            const cards = document.querySelectorAll('.product-card');
            let visible = 0;
            cards.forEach(card => {
                const type = card.getAttribute('data-type') || '';
                const instock = card.getAttribute('data-instock') === '1';

                let show = true;
                if (selectedTypes.length && !selectedTypes.includes(type)) show = false;
                if (inStockOnly && !instock) show = false;

                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            const countEl = document.getElementById('products-count');
            if (countEl) countEl.textContent = visible;
        };

        // Attach listeners
        [...typeChecks, ...availabilityChecks].forEach(inp => inp.addEventListener('change', apply));
    },

    showCartPopup(text) {
        const existing = document.querySelector('.cart-popup');
        if (existing) existing.remove();

        const popup = document.createElement('div');
        popup.className = 'cart-popup';
        popup.style.cssText = 'position: fixed; right: 20px; bottom: 100px; background: white; border: 1px solid rgba(0,0,0,0.08); padding: 12px 16px; box-shadow: var(--shadow-lg); z-index: 4000; border-radius: 8px;';
        popup.textContent = text;

        document.body.appendChild(popup);

        setTimeout(() => popup.remove(), 3000);
    },

    async addToCart(productId, quantity = 1) {
        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            const response = await fetch('/Backend/api/cart.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification(result.message, 'success');
                this.updateCartCount(result.data.cart_count);
                // Show small cart popup anchored to cart icon with product name if available
                let name = result.data?.product_name || null;
                if (!name) {
                    const card = document.querySelector(`.product-card[data-id="${productId}"] .product-name`);
                    if (card) name = card.textContent.trim();
                }
                if (name) this.showCartPopup(`${name} added to cart`);
            } else {
                this.showNotification(result.message || 'Failed to add product', 'error');
            }
        } catch (error) {
            console.error('Cart error:', error);
            this.showNotification('Error connecting to cart', 'error');
        }
    },

    getCart() {
        // We'll rely on the server session now, but keep this for compatibility if needed
        return [];
    },

    saveCart(cart) {
        // We'll rely on the server session now
    },

    async updateCartCount(count = null) {
        if (count === null) {
            try {
                const response = await fetch('/Backend/api/cart.php?action=get');
                const result = await response.json();
                if (result.success) {
                    count = result.data.count;
                }
            } catch (e) {
                count = 0;
            }
        }

        const cartBadge = document.querySelector('.cart-count');
        if (cartBadge) {
            cartBadge.textContent = count || 0;
            cartBadge.style.animation = 'pulse 0.5s ease';
        }
    },

    // ═══════════════════════════════════════════════════════════════
    // COUNTER ANIMATION
    // ═══════════════════════════════════════════════════════════════

    setupCounterAnimation() {
        const counters = document.querySelectorAll('[data-count]');

        counters.forEach((counter) => {
            const target = parseInt(counter.getAttribute('data-count'));
            const duration = 2000;
            const increment = target / (duration / 16);

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting && !counter.hasAnimated) {
                        counter.hasAnimated = true;
                        let current = 0;

                        const timer = setInterval(() => {
                            current += increment;
                            if (current >= target) {
                                counter.textContent = target.toLocaleString();
                                clearInterval(timer);
                            } else {
                                counter.textContent = Math.floor(current).toLocaleString();
                            }
                        }, 16);

                        observer.unobserve(counter);
                    }
                });
            });

            observer.observe(counter);
        });
    },

    // ═══════════════════════════════════════════════════════════════
    // MODAL HANDLERS
    // ═══════════════════════════════════════════════════════════════

    setupModalHandlers() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            const closeBtn = modal.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.closeModal(modal.id));
            }
        });
    },

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        modal.style.animation = 'fadeIn 0.3s ease';

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal(modalId);
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                this.closeModal(modalId);
            }
        });
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    },

    // ═══════════════════════════════════════════════════════════════
    // TAB NAVIGATION
    // ═══════════════════════════════════════════════════════════════

    setupTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const tabsContainer = button.closest('[data-tabs-group]');
                const tabId = button.getAttribute('data-tab');

                // Remove active from all buttons
                tabsContainer.querySelectorAll('.tab-button').forEach((btn) => {
                    btn.classList.remove('active');
                });

                // Add active to clicked button
                button.classList.add('active');

                // Hide all tab contents
                document.querySelectorAll(`[data-tabs-group="${tabsContainer.getAttribute('data-tabs-group')}"] .tab-content`).forEach((content) => {
                    content.classList.remove('active');
                });

                // Show selected tab
                const selectedTab = document.querySelector(`.tab-content[data-tab="${tabId}"]`);
                if (selectedTab) {
                    selectedTab.classList.add('active');
                    selectedTab.style.animation = 'fadeIn 0.3s ease';
                }
            });
        });
    },

    // ═══════════════════════════════════════════════════════════════
    // ACCORDION
    // ═══════════════════════════════════════════════════════════════

    setupAccordion() {
        const accordionHeaders = document.querySelectorAll('.accordion-header');

        accordionHeaders.forEach((header) => {
            header.addEventListener('click', () => {
                const item = header.closest('.accordion-item');
                const isActive = item.classList.contains('active');

                // Close all items
                document.querySelectorAll('.accordion-item').forEach((el) => {
                    el.classList.remove('active');
                });

                // Open clicked item if it wasn't active
                if (!isActive) {
                    item.classList.add('active');
                }
            });
        });
    },

    // ═══════════════════════════════════════════════════════════════
    // PAGE LOAD INITIALIZATION
    // ═══════════════════════════════════════════════════════════════

    onPageLoad() {
        // Re-run intersection observer
        this.setupIntersectionObserver();
        this.setupTabs();
        this.setupAccordion();
        this.updateCartCount();
    },
};

// ═══════════════════════════════════════════════════════════════
// DOCUMENT READY
// ═══════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
    WangariApp.init();
});

// Re-initialize on page load
window.addEventListener('load', () => {
    WangariApp.onPageLoad();
});

