<?php
/**
 * Admin Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get license statistics
$stats = WPLCS_Database::get_license_stats();
$recent_licenses = WPLCS_Database::get_all_licenses(5);
?>

<div class="wrap wplcs-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wplcs-dashboard-widgets">
        <div class="wplcs-stats-grid">
            <div class="wplcs-stat-card">
                <div class="wplcs-stat-number"><?php echo esc_html($stats['total']); ?></div>
                <div class="wplcs-stat-label"><?php _e('Total Licenses', 'wplcs'); ?></div>
            </div>
            
            <div class="wplcs-stat-card wplcs-stat-active">
                <div class="wplcs-stat-number"><?php echo esc_html($stats['active']); ?></div>
                <div class="wplcs-stat-label"><?php _e('Active Licenses', 'wplcs'); ?></div>
            </div>
            
            <div class="wplcs-stat-card wplcs-stat-expired">
                <div class="wplcs-stat-number"><?php echo esc_html($stats['expired']); ?></div>
                <div class="wplcs-stat-label"><?php _e('Expired Licenses', 'wplcs'); ?></div>
            </div>
            
            <div class="wplcs-stat-card wplcs-stat-suspended">
                <div class="wplcs-stat-number"><?php echo esc_html($stats['suspended']); ?></div>
                <div class="wplcs-stat-label"><?php _e('Suspended Licenses', 'wplcs'); ?></div>
            </div>
        </div>
        
        <div class="wplcs-quick-actions">
            <h2><?php _e('Quick Actions', 'wplcs'); ?></h2>
            <div class="wplcs-action-buttons">
                <button type="button" class="button button-primary" id="wplcs-generate-license">
                    <?php _e('Generate New License', 'wplcs'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=wplcs-licenses'); ?>" class="button">
                    <?php _e('View All Licenses', 'wplcs'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wplcs-settings'); ?>" class="button">
                    <?php _e('Settings', 'wplcs'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="wplcs-dashboard-content">
        <div class="wplcs-recent-licenses">
            <h2><?php _e('Recent Licenses', 'wplcs'); ?></h2>
            
            <?php if (!empty($recent_licenses)): ?>
                <div class="wplcs-licenses-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('License Key', 'wplcs'); ?></th>
                                <th><?php _e('Status', 'wplcs'); ?></th>
                                <th><?php _e('Activations', 'wplcs'); ?></th>
                                <th><?php _e('Created', 'wplcs'); ?></th>
                                <th><?php _e('Actions', 'wplcs'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_licenses as $license): ?>
                                <tr>
                                    <td>
                                        <code class="wplcs-license-key"><?php echo esc_html($license->license_key); ?></code>
                                        <button type="button" class="button-link wplcs-copy-license" data-license="<?php echo esc_attr($license->license_key); ?>">
                                            <?php _e('Copy', 'wplcs'); ?>
                                        </button>
                                    </td>
                                    <td>
                                        <span class="wplcs-status wplcs-status-<?php echo esc_attr($license->status); ?>">
                                            <?php echo esc_html(ucfirst($license->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo esc_html($license->activation_count); ?> / <?php echo esc_html($license->activation_limit); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($license->created_at))); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=wplcs-licenses&action=edit&license_id=' . $license->id); ?>" class="button button-small">
                                            <?php _e('Edit', 'wplcs'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="wplcs-empty-state">
                    <p><?php _e('No licenses found. Generate your first license to get started.', 'wplcs'); ?></p>
                    <button type="button" class="button button-primary" id="wplcs-generate-first-license">
                        <?php _e('Generate First License', 'wplcs'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="wplcs-api-info">
            <h2><?php _e('API Information', 'wplcs'); ?></h2>
            <div class="wplcs-api-endpoints">
                <h3><?php _e('Available Endpoints', 'wplcs'); ?></h3>
                <ul>
                    <li><strong>Validate License:</strong> <code><?php echo home_url('/wp-json/wplcs/v1/validate'); ?></code></li>
                    <li><strong>Activate License:</strong> <code><?php echo home_url('/wp-json/wplcs/v1/activate'); ?></code></li>
                    <li><strong>Deactivate License:</strong> <code><?php echo home_url('/wp-json/wplcs/v1/deactivate'); ?></code></li>
                    <li><strong>Get Status:</strong> <code><?php echo home_url('/wp-json/wplcs/v1/status'); ?></code></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Generate License Modal -->
<div id="wplcs-generate-modal" class="wplcs-modal" style="display: none;">
    <div class="wplcs-modal-content">
        <div class="wplcs-modal-header">
            <h2><?php _e('Generate New License', 'wplcs'); ?></h2>
            <button type="button" class="wplcs-modal-close">&times;</button>
        </div>
        
        <form id="wplcs-generate-form" class="wplcs-modal-body">
            <div class="wplcs-form-group">
                <label for="wplcs-product-id"><?php _e('Product ID', 'wplcs'); ?></label>
                <input type="number" id="wplcs-product-id" name="product_id" required min="1" value="1">
            </div>
            
            <div class="wplcs-form-group">
                <label for="wplcs-user-id"><?php _e('User ID (Optional)', 'wplcs'); ?></label>
                <input type="number" id="wplcs-user-id" name="user_id" min="0">
            </div>
            
            <div class="wplcs-form-group">
                <label for="wplcs-email"><?php _e('Email (Optional)', 'wplcs'); ?></label>
                <input type="email" id="wplcs-email" name="email">
            </div>
            
            <div class="wplcs-form-group">
                <label for="wplcs-expires-at"><?php _e('Expires At (Optional)', 'wplcs'); ?></label>
                <input type="datetime-local" id="wplcs-expires-at" name="expires_at">
            </div>
            
            <div class="wplcs-form-group">
                <label for="wplcs-activation-limit"><?php _e('Activation Limit', 'wplcs'); ?></label>
                <input type="number" id="wplcs-activation-limit" name="activation_limit" min="1" value="1">
            </div>
            
            <div class="wplcs-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Generate License', 'wplcs'); ?></button>
                <button type="button" class="button wplcs-modal-close"><?php _e('Cancel', 'wplcs'); ?></button>
            </div>
        </form>
    </div>
</div>
