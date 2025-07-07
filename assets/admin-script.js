/**
 * WPLCS Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize admin functionality
    initializeAdmin();
    
    function initializeAdmin() {
        // Modal functionality
        initializeModals();
        
        // License generation
        initializeLicenseGeneration();
        
        // Copy functionality
        initializeCopyFunctionality();
        
        // Bulk actions
        initializeBulkActions();
        
        // Settings
        initializeSettings();
        
        // Confirmation dialogs
        initializeConfirmations();
    }
    
    /**
     * Initialize modal functionality
     */
    function initializeModals() {
        // Open modal buttons
        $(document).on('click', '#wplcs-generate-license, #wplcs-generate-first-license', function(e) {
            e.preventDefault();
            openModal('#wplcs-generate-modal');
        });
        
        // Close modal buttons
        $(document).on('click', '.wplcs-modal-close', function(e) {
            e.preventDefault();
            closeModal($(this).closest('.wplcs-modal'));
        });
        
        // Close modal on backdrop click
        $(document).on('click', '.wplcs-modal', function(e) {
            if (e.target === this) {
                closeModal($(this));
            }
        });
        
        // Close modal on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('.wplcs-modal:visible');
            }
        });
    }
    
    /**
     * Initialize license generation functionality
     */
    function initializeLicenseGeneration() {
        $(document).on('submit', '#wplcs-generate-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            
            // Show loading state
            $submitBtn.prop('disabled', true).text('Generating...');
            
            // Prepare form data
            var formData = {
                action: 'wplcs_generate_license',
                nonce: wplcs_ajax.nonce,
                product_id: $form.find('[name="product_id"]').val(),
                user_id: $form.find('[name="user_id"]').val(),
                email: $form.find('[name="email"]').val(),
                expires_at: $form.find('[name="expires_at"]').val(),
                activation_limit: $form.find('[name="activation_limit"]').val()
            };
            
            // Send AJAX request
            $.post(wplcs_ajax.ajax_url, formData)
                .done(function(response) {
                    if (response.success) {
                        showMessage('License generated successfully: ' + response.data.license_key, 'success');
                        closeModal('#wplcs-generate-modal');
                        $form[0].reset();
                        
                        // Reload page to show new license
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage('Error: ' + response.data, 'error');
                    }
                })
                .fail(function() {
                    showMessage('Network error. Please try again.', 'error');
                })
                .always(function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                });
        });
    }
    
    /**
     * Initialize copy functionality
     */
    function initializeCopyFunctionality() {
        $(document).on('click', '.wplcs-copy-license', function(e) {
            e.preventDefault();
            
            var licenseKey = $(this).data('license');
            copyToClipboard(licenseKey, 'License key copied to clipboard');
        });
        
        $(document).on('click', '.wplcs-copy-api-key', function(e) {
            e.preventDefault();
            
            var apiKey = $(this).data('api-key');
            copyToClipboard(apiKey, 'API key copied to clipboard');
        });
    }
    
    /**
     * Initialize bulk actions
     */
    function initializeBulkActions() {
        // Select all checkbox
        $(document).on('change', '#wplcs-select-all', function() {
            var isChecked = $(this).prop('checked');
            $('.wplcs-license-checkbox').prop('checked', isChecked);
            toggleBulkActions();
        });
        
        // Individual checkboxes
        $(document).on('change', '.wplcs-license-checkbox', function() {
            var totalCheckboxes = $('.wplcs-license-checkbox').length;
            var checkedCheckboxes = $('.wplcs-license-checkbox:checked').length;
            
            $('#wplcs-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
            toggleBulkActions();
        });
        
        function toggleBulkActions() {
            var hasChecked = $('.wplcs-license-checkbox:checked').length > 0;
            $('#wplcs-bulk-actions').toggle(hasChecked);
        }
    }
    
    /**
     * Initialize settings functionality
     */
    function initializeSettings() {
        // Reset settings
        $(document).on('click', '#wplcs-reset-settings', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to reset all settings to their default values?')) {
                // Implementation for resetting settings
                showMessage('Settings reset functionality would be implemented here', 'info');
            }
        });
        
        // Delete all data
        $(document).on('click', '#wplcs-delete-all-data', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete ALL license data? This action cannot be undone!')) {
                if (confirm('This will permanently delete all licenses, activations, and logs. Are you absolutely sure?')) {
                    // Implementation for deleting all data
                    showMessage('Delete all data functionality would be implemented here', 'warning');
                }
            }
        });
    }
    
    /**
     * Initialize confirmation dialogs
     */
    function initializeConfirmations() {
        $(document).on('click', '.wplcs-confirm-action', function(e) {
            var message = $(this).data('confirm') || 'Are you sure you want to perform this action?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    /**
     * Open modal
     */
    function openModal(modalSelector) {
        $(modalSelector).fadeIn(200);
        $('body').addClass('modal-open');
    }
    
    /**
     * Close modal
     */
    function closeModal(modalSelector) {
        $(modalSelector).fadeOut(200, function() {
            $('body').removeClass('modal-open');
        });
    }
    
    /**
     * Copy text to clipboard
     */
    function copyToClipboard(text, successMessage) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showMessage(successMessage || 'Copied to clipboard', 'success');
            }).catch(function() {
                fallbackCopyToClipboard(text, successMessage);
            });
        } else {
            fallbackCopyToClipboard(text, successMessage);
        }
    }
    
    /**
     * Fallback copy to clipboard for older browsers
     */
    function fallbackCopyToClipboard(text, successMessage) {
        var textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showMessage(successMessage || 'Copied to clipboard', 'success');
        } catch (err) {
            showMessage('Copy to clipboard failed', 'error');
        }
        
        document.body.removeChild(textArea);
    }
    
    /**
     * Show message
     */
    function showMessage(message, type) {
        type = type || 'info';
        
        var messageHtml = '<div class="notice notice-' + type + ' is-dismissible wplcs-message">' +
            '<p>' + message + '</p>' +
            '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
            '</div>';
        
        // Remove existing messages
        $('.wplcs-message').remove();
        
        // Add new message
        $('.wplcs-admin h1').after(messageHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.wplcs-message').fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Manual dismiss
        $(document).on('click', '.wplcs-message .notice-dismiss', function() {
            $(this).closest('.wplcs-message').fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * License validation functionality
     */
    function validateLicense(licenseKey, domain) {
        var data = {
            action: 'wplcs_validate_license',
            license_key: licenseKey,
            domain: domain
        };
        
        return $.post(wplcs_ajax.ajax_url, data);
    }
    
    /**
     * License activation functionality
     */
    function activateLicense(licenseKey, domain) {
        var data = {
            action: 'wplcs_activate_license',
            license_key: licenseKey,
            domain: domain
        };
        
        return $.post(wplcs_ajax.ajax_url, data);
    }
    
    /**
     * License deactivation functionality
     */
    function deactivateLicense(licenseKey, domain) {
        var data = {
            action: 'wplcs_deactivate_license',
            license_key: licenseKey,
            domain: domain
        };
        
        return $.post(wplcs_ajax.ajax_url, data);
    }
    
    /**
     * Format license key for display
     */
    function formatLicenseKey(licenseKey) {
        // Remove any existing formatting
        licenseKey = licenseKey.replace(/[-\s]/g, '');
        
        // Add formatting
        if (licenseKey.length >= 16) {
            return licenseKey.substring(0, 4) + '-' + 
                   licenseKey.substring(4, 8) + '-' + 
                   licenseKey.substring(8, 12) + '-' + 
                   licenseKey.substring(12, 16);
        }
        
        return licenseKey;
    }
    
    /**
     * Validate form inputs
     */
    function validateForm($form) {
        var isValid = true;
        
        $form.find('[required]').each(function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            if (!value) {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });
        
        return isValid;
    }
    
    /**
     * Initialize tooltips
     */
    function initializeTooltips() {
        $('[data-tooltip]').each(function() {
            var $element = $(this);
            var tooltipText = $element.data('tooltip');
            
            $element.on('mouseenter', function() {
                showTooltip($(this), tooltipText);
            }).on('mouseleave', function() {
                hideTooltip();
            });
        });
    }
    
    /**
     * Show tooltip
     */
    function showTooltip($element, text) {
        var $tooltip = $('<div class="wplcs-tooltip">' + text + '</div>');
        $('body').append($tooltip);
        
        var elementOffset = $element.offset();
        var elementHeight = $element.outerHeight();
        
        $tooltip.css({
            top: elementOffset.top + elementHeight + 5,
            left: elementOffset.left,
            display: 'block'
        });
    }
    
    /**
     * Hide tooltip
     */
    function hideTooltip() {
        $('.wplcs-tooltip').remove();
    }
    
    // Initialize tooltips
    initializeTooltips();
    
    // Handle page-specific functionality
    var currentPage = new URLSearchParams(window.location.search).get('page');
    
    switch (currentPage) {
        case 'wplcs-dashboard':
            // Dashboard-specific functionality
            break;
        case 'wplcs-licenses':
            // Licenses page-specific functionality
            break;
        case 'wplcs-settings':
            // Settings page-specific functionality
            break;
    }
});
