/**
 * WPLCS Admin JavaScript
 * Handles WordPress admin functionality and integrates with theme system
 */

(function($) {
    'use strict';

    // Admin functionality class
    class WPLCSAdmin {
        constructor() {
            this.ajaxUrl = wplcs_admin.ajax_url;
            this.nonce = wplcs_admin.nonce;
            this.strings = wplcs_admin.strings;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initializeComponents();
            this.setupAjaxHandlers();
            this.enhanceExistingElements();
        }

        bindEvents() {
            // Search functionality
            $(document).on('click', '#wplcs-search-btn', this.handleSearch.bind(this));
            $(document).on('keypress', '#wplcs-token-search', (e) => {
                if (e.which === 13) {
                    this.handleSearch();
                }
            });

            // Token management
            $(document).on('click', '.wplcs-toggle-token', this.toggleToken.bind(this));
            $(document).on('click', '.wplcs-delete-token', this.deleteToken.bind(this));

            // Tier management
            $(document).on('click', '.wplcs-save-tier', this.saveTier.bind(this));
            $(document).on('click', '.wplcs-delete-tier', this.deleteTier.bind(this));

            // Settings
            $(document).on('click', '.wplcs-save-settings', this.saveSettings.bind(this));

            // Bulk actions
            $(document).on('click', '.wplcs-bulk-action', this.handleBulkAction.bind(this));

            // Export/Import
            $(document).on('click', '.wplcs-export-data', this.exportData.bind(this));
            $(document).on('change', '.wplcs-import-file', this.importData.bind(this));

            // Real-time updates
            this.startRealtimeUpdates();
        }

        initializeComponents() {
            // Initialize data tables
            this.initializeDataTables();
            
            // Initialize charts if available
            this.initializeCharts();
            
            // Initialize tooltips
            this.initializeTooltips();
            
            // Initialize drag and drop
            this.initializeDragDrop();
        }

        setupAjaxHandlers() {
            // Global AJAX error handler
            $(document).ajaxError((event, xhr, settings, error) => {
                if (settings.url.includes(this.ajaxUrl)) {
                    this.showNotification(this.strings.error, 'error');
                }
            });

            // Global AJAX loading indicator
            $(document).ajaxStart(() => {
                $('.wplcs-global-loader').show();
            }).ajaxStop(() => {
                $('.wplcs-global-loader').hide();
            });
        }

        enhanceExistingElements() {
            // Enhance WordPress list tables
            $('.wp-list-table').each((index, table) => {
                this.enhanceTable($(table));
            });

            // Enhance forms
            $('.wplcs-form').each((index, form) => {
                this.enhanceForm($(form));
            });

            // Add confirmation dialogs
            $('[data-confirm]').on('click', this.handleConfirmation.bind(this));
        }

        // Search functionality
        handleSearch() {
            const query = $('#wplcs-token-search').val();
            const $container = $('#wplcs-tokens-table');
            
            if (!query.trim()) {
                this.showNotification('Please enter a search term', 'warning');
                return;
            }

            this.showLoading($container);

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wplcs_admin_search_tokens',
                    nonce: this.nonce,
                    query: query
                },
                success: (response) => {
                    if (response.success) {
                        $container.html(response.data.html);
                        this.showNotification(`Found ${response.data.count} results`, 'success');
                    } else {
                        this.showNotification(response.data.message || this.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showNotification(this.strings.error, 'error');
                },
                complete: () => {
                    this.hideLoading($container);
                }
            });
        }

        // Token management
        toggleToken(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const tokenId = $btn.data('token-id');
            
            this.showLoading($btn);

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wplcs_admin_toggle_token',
                    nonce: this.nonce,
                    token_id: tokenId
                },
                success: (response) => {
                    if (response.success) {
                        // Update UI
                        const $row = $btn.closest('tr');
                        $row.find('.wplcs-status').text(response.data.status);
                        $row.toggleClass('active inactive');
                        
                        this.showNotification(response.data.message, 'success');
                    } else {
                        this.showNotification(response.data.message || this.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showNotification(this.strings.error, 'error');
                },
                complete: () => {
                    this.hideLoading($btn);
                }
            });
        }

        deleteToken(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const tokenId = $btn.data('token-id');
            
            if (!confirm(this.strings.confirm_delete)) {
                return;
            }

            this.showLoading($btn);

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wplcs_admin_delete_token',
                    nonce: this.nonce,
                    token_id: tokenId
                },
                success: (response) => {
                    if (response.success) {
                        $btn.closest('tr').fadeOut(() => {
                            $(this).remove();
                        });
                        this.showNotification(response.data.message, 'success');
                    } else {
                        this.showNotification(response.data.message || this.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showNotification(this.strings.error, 'error');
                },
                complete: () => {
                    this.hideLoading($btn);
                }
            });
        }

        // Tier management
        saveTier(e) {
            e.preventDefault();
            const $form = $(e.currentTarget).closest('form');
            const formData = $form.serialize();
            
            this.showLoading($form);

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: formData + '&action=wplcs_admin_save_tier&nonce=' + this.nonce,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        this.showNotification(response.data.message || this.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showNotification(this.strings.error, 'error');
                },
                complete: () => {
                    this.hideLoading($form);
                }
            });
        }

        deleteTier(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const tierId = $btn.data('tier-id');
            
            if (!confirm(this.strings.confirm_delete)) {
                return;
            }

            this.showLoading($btn);

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wplcs_admin_delete_tier',
                    nonce: this.nonce,
                    tier_id: tierId
                },
                success: (response) => {
                    if (response.success) {
                        $btn.closest('.wplcs-tier-card').fadeOut(() => {
                            $(this).remove();
                        });
                        this.showNotification(response.data.message, 'success');
                    } else {
                        this.showNotification(response.data.message || this.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showNotification(this.strings.error, 'error');
                },
                complete: () => {
                    this.hideLoading($btn);
                }
            });
        }

        // Settings management
        saveSettings(e) {
            e.preventDefault();
            const $form = $(e.currentTarget).closest('form');
            const formData = $form.serialize();
            
            this.showLoading($form);

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: formData + '&action=wplcs_admin_save_settings&nonce=' + this.nonce,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                    } else {
                        this.showNotification(response.data.message || this.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showNotification(this.strings.error, 'error');
                },
                complete: () => {
                    this.hideLoading($form);
                }
            });
        }

        // Bulk actions
        handleBulkAction(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const action = $btn.data('action');
            const selected = $('input[name="selected_items[]"]:checked').map(function() {
                return this.value;
            }).get();

            if (selected.length === 0) {
                this.showNotification('Please select items first', 'warning');
                return;
            }

            if (!confirm(`Are you sure you want to ${action} ${selected.length} item(s)?`)) {
                return;
            }

            this.showLoading($btn);

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wplcs_admin_bulk_action',
                    nonce: this.nonce,
                    bulk_action: action,
                    selected_items: selected
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        location.reload(); // Refresh the page
                    } else {
                        this.showNotification(response.data.message || this.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showNotification(this.strings.error, 'error');
                },
                complete: () => {
                    this.hideLoading($btn);
                }
            });
        }

        // Data export/import
        exportData(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const exportType = $btn.data('export-type');
            
            this.showLoading($btn);

            // Create a temporary link to download the export
            const downloadLink = document.createElement('a');
            downloadLink.href = `${this.ajaxUrl}?action=wplcs_export_data&type=${exportType}&nonce=${this.nonce}`;
            downloadLink.download = `wplcs-${exportType}-${new Date().toISOString().slice(0, 10)}.json`;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);

            setTimeout(() => {
                this.hideLoading($btn);
                this.showNotification('Export completed', 'success');
            }, 2000);
        }

        importData(e) {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('action', 'wplcs_import_data');
            formData.append('nonce', this.nonce);
            formData.append('import_file', file);

            const $container = $(e.target).closest('.wplcs-import-section');
            this.showLoading($container);

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                    } else {
                        this.showNotification(response.data.message || this.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showNotification(this.strings.error, 'error');
                },
                complete: () => {
                    this.hideLoading($container);
                }
            });
        }

        // Real-time updates
        startRealtimeUpdates() {
            // Update dashboard stats every 30 seconds
            setInterval(() => {
                this.updateDashboardStats();
            }, 30000);

            // Update activity log every 60 seconds
            setInterval(() => {
                this.updateActivityLog();
            }, 60000);
        }

        updateDashboardStats() {
            const $statsContainer = $('.wplcs-stats-grid-modern');
            if ($statsContainer.length === 0) return;

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wplcs_get_dashboard_stats',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Update each stat card
                        Object.keys(response.data.stats).forEach(key => {
                            const $statCard = $statsContainer.find(`[data-stat="${key}"]`);
                            if ($statCard.length) {
                                $statCard.find('.wplcs-stat-value').text(response.data.stats[key]);
                            }
                        });
                    }
                }
            });
        }

        updateActivityLog() {
            const $activityContainer = $('#recent-activity');
            if ($activityContainer.length === 0) return;

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wplcs_get_recent_activity',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $activityContainer.find('.wplcs-card').first().html(response.data.html);
                    }
                }
            });
        }

        // Enhanced table functionality
        enhanceTable($table) {
            // Add sorting
            $table.find('th[data-sortable]').addClass('sortable').on('click', (e) => {
                this.sortTable($(e.currentTarget), $table);
            });

            // Add row selection
            $table.find('tbody tr').on('click', (e) => {
                if (!$(e.target).is('input, button, a')) {
                    $(e.currentTarget).toggleClass('selected');
                }
            });

            // Add bulk selection
            $table.find('thead input[type="checkbox"]').on('change', (e) => {
                $table.find('tbody input[type="checkbox"]').prop('checked', e.target.checked);
            });
        }

        sortTable($header, $table) {
            const column = $header.index();
            const isNumeric = $header.data('type') === 'numeric';
            const isDate = $header.data('type') === 'date';
            const isAsc = !$header.hasClass('asc');

            // Clear other headers
            $table.find('th').removeClass('asc desc');
            $header.addClass(isAsc ? 'asc' : 'desc');

            const $rows = $table.find('tbody tr').toArray();
            
            $rows.sort((a, b) => {
                let aVal = $(a).find('td').eq(column).text().trim();
                let bVal = $(b).find('td').eq(column).text().trim();

                if (isNumeric) {
                    aVal = parseFloat(aVal) || 0;
                    bVal = parseFloat(bVal) || 0;
                } else if (isDate) {
                    aVal = new Date(aVal);
                    bVal = new Date(bVal);
                } else {
                    aVal = aVal.toLowerCase();
                    bVal = bVal.toLowerCase();
                }

                if (aVal < bVal) return isAsc ? -1 : 1;
                if (aVal > bVal) return isAsc ? 1 : -1;
                return 0;
            });

            $table.find('tbody').append($rows);
        }

        // Enhanced form functionality
        enhanceForm($form) {
            // Add form validation
            $form.on('submit', (e) => {
                if (!this.validateForm($form)) {
                    e.preventDefault();
                }
            });

            // Add character counters
            $form.find('textarea[maxlength], input[maxlength]').each((index, element) => {
                this.addCharacterCounter($(element));
            });

            // Add form auto-save
            if ($form.hasClass('auto-save')) {
                this.setupAutoSave($form);
            }
        }

        validateForm($form) {
            let isValid = true;
            
            $form.find('[required]').each((index, element) => {
                const $field = $(element);
                if (!$field.val().trim()) {
                    this.showFieldError($field, 'This field is required');
                    isValid = false;
                } else {
                    this.clearFieldError($field);
                }
            });

            return isValid;
        }

        addCharacterCounter($field) {
            const maxLength = parseInt($field.attr('maxlength'));
            const $counter = $('<div class="character-counter"></div>');
            $field.after($counter);

            const updateCounter = () => {
                const remaining = maxLength - $field.val().length;
                $counter.text(`${remaining} characters remaining`);
                $counter.toggleClass('warning', remaining < 20);
            };

            $field.on('input', updateCounter);
            updateCounter();
        }

        setupAutoSave($form) {
            let saveTimeout;
            
            $form.find('input, textarea, select').on('input change', () => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    this.autoSaveForm($form);
                }, 2000);
            });
        }

        autoSaveForm($form) {
            const formData = $form.serialize();
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: formData + '&action=wplcs_auto_save&nonce=' + this.nonce,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Draft saved', 'info', 2000);
                    }
                }
            });
        }

        // Data table initialization
        initializeDataTables() {
            if (typeof $.fn.DataTable !== 'undefined') {
                $('.wplcs-data-table').DataTable({
                    responsive: true,
                    pageLength: 25,
                    order: [[0, 'desc']],
                    language: {
                        search: 'Search:',
                        lengthMenu: 'Show _MENU_ entries',
                        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                        paginate: {
                            first: 'First',
                            last: 'Last',
                            next: 'Next',
                            previous: 'Previous'
                        }
                    }
                });
            }
        }

        // Chart initialization
        initializeCharts() {
            if (typeof Chart !== 'undefined') {
                const $chartContainers = $('.wplcs-chart');
                
                $chartContainers.each((index, container) => {
                    this.createChart($(container));
                });
            }
        }

        createChart($container) {
            const chartType = $container.data('chart-type') || 'line';
            const chartData = $container.data('chart-data') || {};
            
            const ctx = $container.find('canvas')[0].getContext('2d');
            
            new Chart(ctx, {
                type: chartType,
                data: chartData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        }

        // Tooltip initialization
        initializeTooltips() {
            // This is handled by the theme system, but we can add admin-specific tooltips here
            $('[data-admin-tooltip]').each((index, element) => {
                const $el = $(element);
                const tooltipText = $el.data('admin-tooltip');
                
                $el.on('mouseenter', (e) => {
                    this.showTooltip(e.target, tooltipText);
                });
                
                $el.on('mouseleave', () => {
                    this.hideTooltip();
                });
            });
        }

        // Drag and drop initialization
        initializeDragDrop() {
            if (typeof $.fn.sortable !== 'undefined') {
                $('.wplcs-sortable').sortable({
                    handle: '.drag-handle',
                    update: (event, ui) => {
                        this.handleSortUpdate(event, ui);
                    }
                });
            }
        }

        handleSortUpdate(event, ui) {
            const $list = $(event.target);
            const order = $list.sortable('toArray', { attribute: 'data-id' });
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wplcs_update_order',
                    nonce: this.nonce,
                    order: order,
                    list_type: $list.data('list-type')
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Order updated', 'success', 2000);
                    }
                }
            });
        }

        // Utility methods
        showLoading($element) {
            $element.addClass('wplcs-loading').prop('disabled', true);
        }

        hideLoading($element) {
            $element.removeClass('wplcs-loading').prop('disabled', false);
        }

        showNotification(message, type = 'info', duration = 4000) {
            if (window.wplcs && window.wplcs.notify) {
                window.wplcs.notify(message, type, duration);
            } else {
                // Fallback
                console.log(`${type.toUpperCase()}: ${message}`);
            }
        }

        showFieldError($field, message) {
            $field.addClass('error');
            let $error = $field.next('.field-error');
            if (!$error.length) {
                $error = $('<div class="field-error"></div>');
                $field.after($error);
            }
            $error.text(message);
        }

        clearFieldError($field) {
            $field.removeClass('error');
            $field.next('.field-error').remove();
        }

        showTooltip(element, text) {
            const $tooltip = $('<div class="admin-tooltip">' + text + '</div>');
            $('body').append($tooltip);
            
            const rect = element.getBoundingClientRect();
            $tooltip.css({
                top: rect.top - $tooltip.outerHeight() - 5,
                left: rect.left + (rect.width / 2) - ($tooltip.outerWidth() / 2)
            });
        }

        hideTooltip() {
            $('.admin-tooltip').remove();
        }

        handleConfirmation(e) {
            const message = $(e.currentTarget).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        }
    }

    // Initialize admin functionality
    $(document).ready(() => {
        window.wplcsAdmin = new WPLCSAdmin();
        
        // Theme integration
        $(document).on('wplcs:themeChanged', (event, theme) => {
            window.wplcsAdmin.showNotification(
                `Switched to ${theme} mode`, 
                'info', 
                2000
            );
        });
    });

})(jQuery);