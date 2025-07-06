jQuery(document).ready(function($) {
    'use strict';
    
    // Toggle tier status
    $(document).on('click', '.wplcs-toggle-tier', function(e) {
        e.preventDefault();
        
        if (!confirm(wplcs_plans_ajax.i18n.confirm_toggle)) {
            return;
        }
        
        var tierId = $(this).data('tier-id');
        var isActive = parseInt($(this).data('active'));
        var $button = $(this);
        var $card = $button.closest('.wplcs-plan-card');
        
        $button.prop('disabled', true);
        
        $.ajax({
            url: wplcs_plans_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wplcs_toggle_tier',
                nonce: wplcs_plans_ajax.nonce,
                tier_id: tierId,
                is_active: isActive
            },
            success: function(response) {
                if (response.success) {
                    var newStatus = response.data.new_status;
                    
                    // Update button text and data
                    $button.text(newStatus ? 'Deactivate' : 'Activate')
                           .data('active', newStatus);
                    
                    // Update card classes
                    $card.removeClass('active inactive')
                         .addClass(newStatus ? 'active' : 'inactive');
                    
                    // Update badge
                    var $badge = $card.find('.wplcs-badge.active, .wplcs-badge.inactive');
                    $badge.removeClass('active inactive')
                          .addClass(newStatus ? 'active' : 'inactive')
                          .text(newStatus ? 'Active' : 'Inactive');
                    
                    showNotice(response.data.message, 'success');
                } else {
                    showNotice(response.data || 'Failed to update tier status.', 'error');
                }
                $button.prop('disabled', false);
            },
            error: function() {
                showNotice('An error occurred. Please try again.', 'error');
                $button.prop('disabled', false);
            }
        });
    });
    
    // Delete tier
    $(document).on('click', '.wplcs-delete-tier', function(e) {
        e.preventDefault();
        
        if (!confirm(wplcs_plans_ajax.i18n.confirm_delete)) {
            return;
        }
        
        var tierId = $(this).data('tier-id');
        var $button = $(this);
        var $card = $button.closest('.wplcs-plan-card');
        
        $button.prop('disabled', true);
        
        $.ajax({
            url: wplcs_plans_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wplcs_delete_tier',
                nonce: wplcs_plans_ajax.nonce,
                tier_id: tierId
            },
            success: function(response) {
                if (response.success) {
                    $card.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if grid is empty
                        if ($('.wplcs-plans-grid .wplcs-plan-card').length === 0) {
                            location.reload(); // Reload to show empty state
                        }
                    });
                    
                    showNotice(response.data || 'Plan deleted successfully.', 'success');
                } else {
                    showNotice(response.data || 'Failed to delete plan.', 'error');
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                showNotice('An error occurred. Please try again.', 'error');
                $button.prop('disabled', false);
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
    
    // Form validation
    $('.wplcs-tier-form').on('submit', function(e) {
        var name = $('input[name="name"]').val().trim();
        var price = parseFloat($('input[name="price"]').val());
        var maxNodes = parseInt($('input[name="max_nodes"]').val());
        var duration = parseInt($('input[name="duration"]').val());
        
        if (!name) {
            e.preventDefault();
            showNotice('Plan name is required.', 'error');
            $('input[name="name"]').focus();
            return false;
        }
        
        if (isNaN(price) || price < 0) {
            e.preventDefault();
            showNotice('Please enter a valid price.', 'error');
            $('input[name="price"]').focus();
            return false;
        }
        
        if (isNaN(maxNodes) || maxNodes < -1) {
            e.preventDefault();
            showNotice('Maximum sites must be -1 (unlimited) or a positive number.', 'error');
            $('input[name="max_nodes"]').focus();
            return false;
        }
        
        if (isNaN(duration) || duration < 1) {
            e.preventDefault();
            showNotice('Duration must be at least 1 day.', 'error');
            $('input[name="duration"]').focus();
            return false;
        }
    });
    
    // Enhance product select
    if ($.fn.chosen) {
        $('.wplcs-product-select').chosen({
            placeholder_text_multiple: 'Select WooCommerce products...',
            width: '100%'
        });
    }
});