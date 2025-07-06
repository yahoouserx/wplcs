/**
 * WPLCS Dashboard JavaScript
 */

(function($) {
    'use strict';
    
    var WPLCS_Dashboard = {
        
        /**
         * Initialize dashboard
         */
        init: function() {
            this.bindEvents();
            this.initTabs();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Tab switching
            $(document).on('click', '.wplcs-tab-button', this.switchTab);
            
            // Token actions
            $(document).on('click', '.wplcs-deactivate-token', this.deactivateToken);
            $(document).on('click', '.wplcs-copy-token', this.copyToken);
            
            // Session actions
            $(document).on('click', '.wplcs-deactivate-session', this.deactivateSession);
            
            // Refresh dashboard
            $(document).on('click', '.wplcs-refresh', this.refreshDashboard);
        },
        
        /**
         * Initialize tabs
         */
        initTabs: function() {
            // Show first tab by default
            $('.wplcs-tab-button:first').addClass('active');
            $('.wplcs-tab-panel:first').addClass('active');
        },
        
        /**
         * Switch tab
         */
        switchTab: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var tabId = $button.data('tab');
            
            // Update active states
            $('.wplcs-tab-button').removeClass('active');
            $('.wplcs-tab-panel').removeClass('active');
            
            $button.addClass('active');
            $('#wplcs-tab-' + tabId).addClass('active');
        },
        
        /**
         * Deactivate token
         */
        deactivateToken: function(e) {
            e.preventDefault();
            
            if (!confirm(wplcs_ajax.strings.confirm_deactivate_token)) {
                return;
            }
            
            var $button = $(this);
            var tokenId = $button.data('token-id');
            
            $button.prop('disabled', true).text(wplcs_ajax.strings.loading);
            
            $.ajax({
                url: wplcs_ajax.url,
                type: 'POST',
                data: {
                    action: 'wplcs_deactivate_token',
                    token_id: tokenId,
                    nonce: wplcs_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        WPLCS_Dashboard.showNotice('success', response.data);
                        WPLCS_Dashboard.refreshDashboard();
                    } else {
                        WPLCS_Dashboard.showNotice('error', response.data);
                        $button.prop('disabled', false).text('Deactivate');
                    }
                },
                error: function() {
                    WPLCS_Dashboard.showNotice('error', wplcs_ajax.strings.error);
                    $button.prop('disabled', false).text('Deactivate');
                }
            });
        },
        
        /**
         * Copy token
         */
        copyToken: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var tokenRef = $button.data('token');
            
            // For security, we don't expose actual tokens in frontend
            // This would typically open a modal or make an AJAX request
            // to reveal the token after additional authentication
            
            WPLCS_Dashboard.showTokenModal(tokenRef);
        },
        
        /**
         * Show token modal
         */
        showTokenModal: function(tokenRef) {
            // Create modal
            var modal = $('<div class="wplcs-modal-overlay">' +
                '<div class="wplcs-modal">' +
                    '<div class="wplcs-modal-header">' +
                        '<h3>Access Token</h3>' +
                        '<button class="wplcs-modal-close">&times;</button>' +
                    '</div>' +
                    '<div class="wplcs-modal-body">' +
                        '<p>For security reasons, tokens are not displayed directly. Please contact support if you need to retrieve your token.</p>' +
                        '<p><strong>Token Reference:</strong> ' + tokenRef + '</p>' +
                    '</div>' +
                '</div>' +
            '</div>');
            
            $('body').append(modal);
            
            // Close modal
            modal.on('click', '.wplcs-modal-close, .wplcs-modal-overlay', function(e) {
                if (e.target === this) {
                    modal.remove();
                }
            });
        },
        
        /**
         * Deactivate session
         */
        deactivateSession: function(e) {
            e.preventDefault();
            
            if (!confirm(wplcs_ajax.strings.confirm_deactivate_session)) {
                return;
            }
            
            var $button = $(this);
            var sessionId = $button.data('session-id');
            
            $button.prop('disabled', true).text(wplcs_ajax.strings.loading);
            
            $.ajax({
                url: wplcs_ajax.url,
                type: 'POST',
                data: {
                    action: 'wplcs_deactivate_session',
                    session_id: sessionId,
                    nonce: wplcs_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        WPLCS_Dashboard.showNotice('success', response.data);
                        WPLCS_Dashboard.refreshDashboard();
                    } else {
                        WPLCS_Dashboard.showNotice('error', response.data);
                        $button.prop('disabled', false).text('Remove');
                    }
                },
                error: function() {
                    WPLCS_Dashboard.showNotice('error', wplcs_ajax.strings.error);
                    $button.prop('disabled', false).text('Remove');
                }
            });
        },
        
        /**
         * Refresh dashboard
         */
        refreshDashboard: function(e) {
            if (e) {
                e.preventDefault();
            }
            
            var $button = $('.wplcs-refresh');
            $button.prop('disabled', true).text(wplcs_ajax.strings.loading);
            
            $.ajax({
                url: wplcs_ajax.url,
                type: 'POST',
                data: {
                    action: 'wplcs_refresh_dashboard',
                    nonce: wplcs_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.wplcs-dashboard').html(response.data.html);
                        WPLCS_Dashboard.initTabs();
                    } else {
                        WPLCS_Dashboard.showNotice('error', response.data);
                    }
                },
                error: function() {
                    WPLCS_Dashboard.showNotice('error', wplcs_ajax.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Refresh');
                }
            });
        },
        
        /**
         * Show notice
         */
        showNotice: function(type, message) {
            var noticeClass = type === 'success' ? 'wplcs-notice-success' : 'wplcs-notice-error';
            var notice = $('<div class="wplcs-notice ' + noticeClass + '">' + message + '</div>');
            
            $('body').append(notice);
            
            // Position notice
            notice.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '15px 20px',
                borderRadius: '5px',
                zIndex: 9999,
                maxWidth: '300px'
            });
            
            if (type === 'success') {
                notice.css({
                    backgroundColor: '#d4edda',
                    color: '#155724',
                    border: '1px solid #c3e6cb'
                });
            } else {
                notice.css({
                    backgroundColor: '#f8d7da',
                    color: '#721c24',
                    border: '1px solid #f5c6cb'
                });
            }
            
            // Auto remove
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        WPLCS_Dashboard.init();
    });
    
})(jQuery);