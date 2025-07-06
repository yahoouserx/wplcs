/**
 * WPLCS Modern Theme System
 * Light/Dark mode functionality inspired by cursor.com
 */

(function($) {
    'use strict';

    class WPLCSTheme {
        constructor() {
            this.currentTheme = 'light';
            this.storageKey = 'wplcs-theme-preference';
            this.transitionDuration = 300;
            
            this.init();
        }

        init() {
            this.setupDOM();
            this.loadThemePreference();
            this.bindEvents();
            this.applySystemPreference();
            this.initializeAnimations();
        }

        setupDOM() {
            // Create theme toggle button if it doesn't exist
            if (!document.querySelector('.wplcs-theme-toggle')) {
                this.createThemeToggle();
            }

            // Wrap admin content with theme container
            const adminContent = document.querySelector('#wpbody-content, .wrap');
            if (adminContent && !adminContent.closest('.wplcs-theme-container')) {
                const container = document.createElement('div');
                container.className = 'wplcs-theme-container';
                adminContent.parentNode.insertBefore(container, adminContent);
                container.appendChild(adminContent);
            }
        }

        createThemeToggle() {
            const toggleHTML = `
                <button class="wplcs-theme-toggle" id="wplcs-theme-toggle" aria-label="Toggle theme">
                    <svg class="wplcs-theme-toggle-icon sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <svg class="wplcs-theme-toggle-icon moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                    <span class="wplcs-theme-toggle-text">
                        <span class="light-text">Dark</span>
                        <span class="dark-text">Light</span>
                    </span>
                </button>
            `;

            document.body.insertAdjacentHTML('beforeend', toggleHTML);
        }

        bindEvents() {
            // Theme toggle button
            $(document).on('click', '#wplcs-theme-toggle', (e) => {
                e.preventDefault();
                this.toggleTheme();
            });

            // Keyboard shortcut (Ctrl/Cmd + Shift + L)
            $(document).on('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'l') {
                    e.preventDefault();
                    this.toggleTheme();
                }
            });

            // Listen for system preference changes
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                    if (!localStorage.getItem(this.storageKey)) {
                        this.setTheme(e.matches ? 'dark' : 'light');
                    }
                });
            }

            // Smooth transitions after page load
            $(window).on('load', () => {
                setTimeout(() => {
                    document.documentElement.style.setProperty('--transition-duration', this.transitionDuration + 'ms');
                }, 100);
            });
        }

        loadThemePreference() {
            const savedTheme = localStorage.getItem(this.storageKey);
            if (savedTheme && ['light', 'dark'].includes(savedTheme)) {
                this.setTheme(savedTheme);
            }
        }

        applySystemPreference() {
            // Only apply system preference if user hasn't set a preference
            if (!localStorage.getItem(this.storageKey) && window.matchMedia) {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                this.setTheme(prefersDark ? 'dark' : 'light');
            }
        }

        toggleTheme() {
            const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
            this.setTheme(newTheme);
            this.saveThemePreference(newTheme);
            
            // Add toggle animation
            this.animateToggle();
        }

        setTheme(theme) {
            this.currentTheme = theme;
            document.documentElement.setAttribute('data-theme', theme);
            
            // Update body class for WordPress admin compatibility
            document.body.classList.remove('wplcs-theme-light', 'wplcs-theme-dark');
            document.body.classList.add(`wplcs-theme-${theme}`);
            
            // Update toggle button text
            this.updateToggleButton();
            
            // Trigger custom event
            $(document).trigger('wplcs:themeChanged', [theme]);
            
            // Update CSS custom properties for immediate effect
            this.updateCSSVariables(theme);
        }

        updateCSSVariables(theme) {
            const root = document.documentElement;
            
            if (theme === 'dark') {
                // Apply dark theme variables immediately
                root.style.setProperty('--wplcs-bg-primary', '#0d1117');
                root.style.setProperty('--wplcs-text-primary', '#f0f6fc');
            } else {
                // Apply light theme variables immediately
                root.style.setProperty('--wplcs-bg-primary', '#ffffff');
                root.style.setProperty('--wplcs-text-primary', '#1a1d21');
            }
        }

        updateToggleButton() {
            const toggleButton = document.querySelector('#wplcs-theme-toggle');
            if (toggleButton) {
                const lightText = toggleButton.querySelector('.light-text');
                const darkText = toggleButton.querySelector('.dark-text');
                
                if (lightText && darkText) {
                    if (this.currentTheme === 'dark') {
                        lightText.style.display = 'none';
                        darkText.style.display = 'inline';
                    } else {
                        lightText.style.display = 'inline';
                        darkText.style.display = 'none';
                    }
                }
            }
        }

        animateToggle() {
            const toggle = document.querySelector('#wplcs-theme-toggle');
            if (toggle) {
                toggle.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    toggle.style.transform = '';
                }, 150);
            }
        }

        saveThemePreference(theme) {
            try {
                localStorage.setItem(this.storageKey, theme);
            } catch (e) {
                console.warn('Could not save theme preference:', e);
            }
        }

        initializeAnimations() {
            // Add entrance animations to dashboard elements
            this.addEntranceAnimations();
            
            // Initialize intersection observer for scroll animations
            this.setupScrollAnimations();
        }

        addEntranceAnimations() {
            const elements = document.querySelectorAll('.wplcs-card, .wplcs-stat-card-modern');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }

        setupScrollAnimations() {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('wplcs-fade-in');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '50px'
                });

                // Observe elements that should animate on scroll
                document.querySelectorAll('.wplcs-table-container, .wplcs-nav-tabs').forEach(el => {
                    observer.observe(el);
                });
            }
        }

        // Utility methods for external use
        getCurrentTheme() {
            return this.currentTheme;
        }

        isDarkMode() {
            return this.currentTheme === 'dark';
        }

        // Method to programmatically set theme
        setThemeMode(theme) {
            if (['light', 'dark'].includes(theme)) {
                this.setTheme(theme);
                this.saveThemePreference(theme);
            }
        }
    }

    // Enhanced UI Components
    class WPLCSUIComponents {
        constructor() {
            this.init();
        }

        init() {
            this.enhanceExistingElements();
            this.addTooltips();
            this.enhanceButtons();
            this.enhanceForms();
        }

        enhanceExistingElements() {
            // Convert existing WordPress admin elements to modern components
            this.modernizeAdminPanels();
            this.modernizeDataTables();
            this.modernizeNotices();
        }

        modernizeAdminPanels() {
            // Enhance existing admin panels
            $('.wrap').each(function() {
                const $wrap = $(this);
                if (!$wrap.hasClass('wplcs-dashboard-modern')) {
                    $wrap.addClass('wplcs-dashboard-modern');
                }
            });

            // Convert existing buttons
            $('.button, .button-primary, .button-secondary').each(function() {
                const $btn = $(this);
                if (!$btn.hasClass('wplcs-btn')) {
                    $btn.addClass('wplcs-btn');
                    
                    if ($btn.hasClass('button-primary')) {
                        $btn.addClass('wplcs-btn-primary');
                    } else if ($btn.hasClass('button-secondary')) {
                        $btn.addClass('wplcs-btn-secondary');
                    }
                }
            });
        }

        modernizeDataTables() {
            // Enhance WordPress list tables
            $('.wp-list-table').each(function() {
                const $table = $(this);
                if (!$table.closest('.wplcs-table-container').length) {
                    $table.wrap('<div class="wplcs-table-container"></div>');
                    $table.addClass('wplcs-table');
                }
            });
        }

        modernizeNotices() {
            // Enhance admin notices
            $('.notice, .updated, .error').each(function() {
                const $notice = $(this);
                
                if ($notice.hasClass('notice-success') || $notice.hasClass('updated')) {
                    $notice.prepend('<span class="wplcs-badge-dot"></span>');
                    $notice.addClass('wplcs-notice-success');
                } else if ($notice.hasClass('notice-error') || $notice.hasClass('error')) {
                    $notice.prepend('<span class="wplcs-badge-dot"></span>');
                    $notice.addClass('wplcs-notice-error');
                } else if ($notice.hasClass('notice-warning')) {
                    $notice.prepend('<span class="wplcs-badge-dot"></span>');
                    $notice.addClass('wplcs-notice-warning');
                } else if ($notice.hasClass('notice-info')) {
                    $notice.prepend('<span class="wplcs-badge-dot"></span>');
                    $notice.addClass('wplcs-notice-info');
                }
            });
        }

        addTooltips() {
            // Simple tooltip implementation
            $('[data-tooltip]').each(function() {
                const $el = $(this);
                const tooltipText = $el.data('tooltip');
                
                $el.on('mouseenter', function(e) {
                    const tooltip = $('<div class="wplcs-tooltip">' + tooltipText + '</div>');
                    $('body').append(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.css({
                        top: rect.top - tooltip.outerHeight() - 5,
                        left: rect.left + (rect.width / 2) - (tooltip.outerWidth() / 2)
                    });
                    
                    setTimeout(() => tooltip.addClass('show'), 10);
                });
                
                $el.on('mouseleave', function() {
                    $('.wplcs-tooltip').remove();
                });
            });
        }

        enhanceButtons() {
            // Add ripple effect to buttons
            $(document).on('click', '.wplcs-btn', function(e) {
                const $btn = $(this);
                const ripple = $('<span class="wplcs-ripple"></span>');
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.css({
                    width: size,
                    height: size,
                    left: x,
                    top: y
                });
                
                $btn.append(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        }

        enhanceForms() {
            // Enhance form inputs
            $('.wplcs-form-input, .wplcs-form-textarea').each(function() {
                const $input = $(this);
                const $group = $input.closest('.wplcs-form-group');
                
                $input.on('focus', function() {
                    $group.addClass('focused');
                });
                
                $input.on('blur', function() {
                    $group.removeClass('focused');
                    if (this.value) {
                        $group.addClass('has-value');
                    } else {
                        $group.removeClass('has-value');
                    }
                });
                
                // Check initial value
                if ($input.val()) {
                    $group.addClass('has-value');
                }
            });
        }
    }

    // Notification System
    class WPLCSNotifications {
        constructor() {
            this.container = null;
            this.init();
        }

        init() {
            this.createContainer();
        }

        createContainer() {
            if (!document.querySelector('.wplcs-notifications')) {
                const container = document.createElement('div');
                container.className = 'wplcs-notifications';
                container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10001;
                    pointer-events: none;
                `;
                document.body.appendChild(container);
                this.container = container;
            }
        }

        show(message, type = 'info', duration = 4000) {
            const notification = document.createElement('div');
            notification.className = `wplcs-notification wplcs-notification-${type}`;
            notification.style.cssText = `
                background: var(--wplcs-bg-elevated);
                border: 1px solid var(--wplcs-border-primary);
                border-radius: var(--wplcs-radius-lg);
                padding: var(--wplcs-space-md);
                margin-bottom: var(--wplcs-space-sm);
                box-shadow: var(--wplcs-shadow-lg);
                pointer-events: auto;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                max-width: 300px;
                color: var(--wplcs-text-primary);
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: var(--wplcs-space-sm);">
                    <span class="wplcs-badge-dot" style="background: var(--wplcs-${type === 'error' ? 'error' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info'});"></span>
                    <span>${message}</span>
                </div>
            `;

            this.container.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);

            // Auto remove
            setTimeout(() => {
                this.remove(notification);
            }, duration);

            return notification;
        }

        remove(notification) {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }

    // Initialize everything when DOM is ready
    $(document).ready(function() {
        // Initialize theme system
        window.wplcsTheme = new WPLCSTheme();
        
        // Initialize UI components
        window.wplcsUI = new WPLCSUIComponents();
        
        // Initialize notifications
        window.wplcsNotifications = new WPLCSNotifications();
        
        // Global utility functions
        window.wplcs = {
            setTheme: (theme) => window.wplcsTheme.setThemeMode(theme),
            getCurrentTheme: () => window.wplcsTheme.getCurrentTheme(),
            isDarkMode: () => window.wplcsTheme.isDarkMode(),
            notify: (message, type, duration) => window.wplcsNotifications.show(message, type, duration)
        };
        
        // Trigger initialization complete event
        $(document).trigger('wplcs:initialized');
    });

})(jQuery);