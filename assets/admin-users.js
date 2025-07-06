jQuery(document).ready(function($) {
    'use strict';
    
    // Deactivate user token
    $(document).on('click', '.wplcs-deactivate-token', function(e) {
        e.preventDefault();
        
        if (!confirm(wplcs_users_ajax.i18n.confirm_deactivate)) {
            return;
        }
        
        var tokenId = $(this).data('token-id');
        var $button = $(this);
        var $row = $button.closest('tr');
        
        $button.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: wplcs_users_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wplcs_deactivate_user_token',
                nonce: wplcs_users_ajax.nonce,
                token_id: tokenId
            },
            success: function(response) {
                if (response.success) {
                    // Update status in the row
                    $row.find('.wplcs-status').removeClass('active').addClass('inactive').text('Inactive');
                    $button.remove();
                    
                    showNotice(response.data || 'Token deactivated successfully.', 'success');
                } else {
                    showNotice(response.data || 'Failed to deactivate token.', 'error');
                    $button.prop('disabled', false).text('Deactivate');
                }
            },
            error: function() {
                showNotice('An error occurred. Please try again.', 'error');
                $button.prop('disabled', false).text('Deactivate');
            }
        });
    });
    
    // Reset user sessions
    $(document).on('click', '.wplcs-reset-sessions', function(e) {
        e.preventDefault();
        
        if (!confirm(wplcs_users_ajax.i18n.confirm_reset)) {
            return;
        }
        
        var userId = $(this).data('user-id');
        var $button = $(this);
        var $row = $button.closest('tr');
        
        $button.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: wplcs_users_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wplcs_reset_user_sessions',
                nonce: wplcs_users_ajax.nonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    // Update session count in the row
                    $row.find('.active-sessions').text('(0 active)');
                    $button.remove();
                    
                    showNotice(response.data || 'All user sessions have been reset.', 'success');
                } else {
                    showNotice(response.data || 'Failed to reset user sessions.', 'error');
                    $button.prop('disabled', false).text('Reset Sessions');
                }
            },
            error: function() {
                showNotice('An error occurred. Please try again.', 'error');
                $button.prop('disabled', false).text('Reset Sessions');
            }
        });
    });
    
    // Show notification
    function showNotice(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible">' +
            '<p>' + message + '</p>' +
            '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
            '</div>');
        
        $('.wrap > h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Handle notice dismiss
    $(document).on('click', '.notice-dismiss', function(e) {
        e.preventDefault();
        $(this).closest('.notice').fadeOut(300, function() {
            $(this).remove();
        });
    });
    
    // Enhanced search functionality
    var searchTimeout;
    $('input[name="s"]').on('input', function() {
        var $input = $(this);
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(function() {
            if ($input.val().length >= 3 || $input.val().length === 0) {
                // Auto-submit search after 500ms delay for better UX
                // $input.closest('form').submit();
            }
        }, 500);
    });
    
    // Row highlighting on hover
    $('.wplcs-users-table tbody tr').hover(
        function() {
            $(this).addClass('hover');
        },
        function() {
            $(this).removeClass('hover');
        }
    );
    
    // Responsive table handling
    function makeTableResponsive() {
        var $table = $('.wplcs-users-table');
        if ($(window).width() < 768) {
            $table.addClass('mobile-view');
        } else {
            $table.removeClass('mobile-view');
        }
    }
    
    makeTableResponsive();
    $(window).on('resize', makeTableResponsive);
});