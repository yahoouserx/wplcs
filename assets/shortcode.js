jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize theme system for frontend
    initFrontendTheme();
    
    // Copy token to clipboard
    $(document).on('click', '.wplcs-copy-token', function(e) {
        e.preventDefault();
        
        var token = $(this).data('token');
        var $button = $(this);
        var originalText = $button.text();
        
        // Create temporary input to copy from
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(token).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Show feedback
        $button.text('Copied!').prop('disabled', true);
        setTimeout(function() {
            $button.text(originalText).prop('disabled', false);
        }, 2000);
    });
    
    // Deactivate site/session
    $(document).on('click', '.wplcs-deactivate-site', function(e) {
        e.preventDefault();
        
        if (!confirm(wplcs_ajax.i18n.confirm_deactivate)) {
            return;
        }
        
        var sessionId = $(this).data('session-id');
        var $button = $(this);
        var $siteItem = $button.closest('.wplcs-site-item');
        
        $button.text(wplcs_ajax.i18n.processing).prop('disabled', true);
        
        $.ajax({
            url: wplcs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wplcs_deactivate_session',
                nonce: wplcs_ajax.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    $siteItem.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showNotice(wplcs_ajax.i18n.success, 'success');
                } else {
                    showNotice(response.data || wplcs_ajax.i18n.error, 'error');
                    $button.text('Deactivate').prop('disabled', false);
                }
            },
            error: function() {
                showNotice(wplcs_ajax.i18n.error, 'error');
                $button.text('Deactivate').prop('disabled', false);
            }
        });
    });
    
    // Regenerate token
    $(document).on('click', '.wplcs-regenerate-token', function(e) {
        e.preventDefault();
        
        if (!confirm(wplcs_ajax.i18n.confirm_regenerate)) {
            return;
        }
        
        var tokenId = $(this).data('token-id');
        var $button = $(this);
        var $tokenField = $button.closest('.wplcs-license-card').find('.wplcs-token-field');
        
        $button.text(wplcs_ajax.i18n.processing).prop('disabled', true);
        
        $.ajax({
            url: wplcs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wplcs_regenerate_token',
                nonce: wplcs_ajax.nonce,
                token_id: tokenId
            },
            success: function(response) {
                if (response.success) {
                    $tokenField.val(response.data.new_token);
                    showNotice(wplcs_ajax.i18n.success, 'success');
                } else {
                    showNotice(response.data || wplcs_ajax.i18n.error, 'error');
                }
                $button.text('Regenerate Key').prop('disabled', false);
            },
            error: function() {
                showNotice(wplcs_ajax.i18n.error, 'error');
                $button.text('Regenerate Key').prop('disabled', false);
            }
        });
    });
    
    // Renew license
    $(document).on('click', '.wplcs-renew-license', function(e) {
        e.preventDefault();
        
        var tokenId = $(this).data('token-id');
        var $button = $(this);
        
        $button.text(wplcs_ajax.i18n.processing).prop('disabled', true);
        
        $.ajax({
            url: wplcs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wplcs_renew_license',
                nonce: wplcs_ajax.nonce,
                token_id: tokenId
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to renewal page or refresh current page
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        location.reload();
                    }
                } else {
                    showNotice(response.data || wplcs_ajax.i18n.error, 'error');
                    $button.text('Renew License').prop('disabled', false);
                }
            },
            error: function() {
                showNotice(wplcs_ajax.i18n.error, 'error');
                $button.text('Renew License').prop('disabled', false);
            }
        });
    });
    
    // Show notification - Enhanced for theme system
    function showNotice(message, type) {
        // Use modern notification system if available
        if (window.wplcs && window.wplcs.notify) {
            window.wplcs.notify(message, type);
            return;
        }
        
        // Fallback to traditional notice
        var $notice = $('<div class="wplcs-notice wplcs-notice-' + type + ' wplcs-notice-dismissible">' +
            '<p>' + message + '</p>' +
            '<button type="button" class="wplcs-notice-dismiss">&times;</button>' +
            '</div>');
        
        $('.wplcs-account-dashboard').prepend($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Dismiss notice manually
    $(document).on('click', '.wplcs-notice-dismiss', function(e) {
        e.preventDefault();
        $(this).closest('.wplcs-notice').fadeOut(300, function() {
            $(this).remove();
        });
    });
    
    // Responsive grid handling
    function adjustGridColumns() {
        var $dashboard = $('.wplcs-account-dashboard');
        var $grid = $('.wplcs-licenses-grid');
        var columns = parseInt($dashboard.data('columns')) || 2;
        var screenWidth = $(window).width();
        
        if (screenWidth < 768) {
            columns = 1;
        } else if (screenWidth < 1200 && columns > 2) {
            columns = 2;
        }
        
        $grid.css('grid-template-columns', 'repeat(' + columns + ', 1fr)');
    }
    
    // Initialize responsive grid
    adjustGridColumns();
    $(window).on('resize', adjustGridColumns);
});

// Initialize frontend theme system
function initFrontendTheme() {
    // Apply saved theme preference or system preference
    var savedTheme = localStorage.getItem('wplcs-theme-preference');
    var systemPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
    
    // Apply theme to theme container
    var $themeContainer = $('.wplcs-theme-container');
    if ($themeContainer.length) {
        $themeContainer.attr('data-theme', theme);
        document.documentElement.setAttribute('data-theme', theme);
    }
    
    // Listen for theme changes from main theme system
    $(document).on('wplcs:themeChanged', function(event, newTheme) {
        $themeContainer.attr('data-theme', newTheme);
    });
    
    // Add theme toggle if not present (optional for frontend)
    if (window.wplcsTheme && typeof window.wplcsTheme.getCurrentTheme === 'function') {
        // Theme system is available, let it handle everything
        return;
    }
    
    // Simple theme toggle for standalone frontend use
    var $toggleButton = $('<button class="wplcs-theme-toggle-simple" style="position: fixed; top: 20px; right: 20px; z-index: 1000; padding: 10px; border: none; border-radius: 5px; background: var(--wplcs-primary); color: var(--wplcs-text-inverse); cursor: pointer;">🌓</button>');
    $('body').append($toggleButton);
    
    $toggleButton.on('click', function() {
        var currentTheme = $themeContainer.attr('data-theme');
        var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        $themeContainer.attr('data-theme', newTheme);
        document.documentElement.setAttribute('data-theme', newTheme);
        
        // Save preference
        try {
            localStorage.setItem('wplcs-theme-preference', newTheme);
        } catch (e) {
            console.warn('Could not save theme preference');
        }
    });
}

// Copy to clipboard fallback for older browsers
if (!document.execCommand) {
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        
        // Fallback for older browsers
        var textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        return new Promise(function(resolve, reject) {
            if (document.execCommand('copy')) {
                resolve();
            } else {
                reject();
            }
            document.body.removeChild(textArea);
        });
    }
}