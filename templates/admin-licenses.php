<?php
/**
 * Admin Licenses Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle pagination
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Handle status filter
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Get licenses
$licenses = WPLCS_Database::get_all_licenses($per_page, $offset, $status_filter);

// Handle actions
if (isset($_POST['action']) && isset($_POST['license_id'])) {
    check_admin_referer('wplcs_admin_nonce');
    
    $license_id = intval($_POST['license_id']);
    $action = sanitize_text_field($_POST['action']);
    
    switch ($action) {
        case 'delete':
            if (WPLCS_Database::delete_license($license_id)) {
                echo '<div class="notice notice-success"><p>' . __('License deleted successfully.', 'wplcs') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to delete license.', 'wplcs') . '</p></div>';
            }
            break;
        
        case 'activate':
            if (WPLCS_Database::update_license($license_id, array('status' => 'active'))) {
                echo '<div class="notice notice-success"><p>' . __('License activated successfully.', 'wplcs') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to activate license.', 'wplcs') . '</p></div>';
            }
            break;
        
        case 'suspend':
            if (WPLCS_Database::update_license($license_id, array('status' => 'suspended'))) {
                echo '<div class="notice notice-success"><p>' . __('License suspended successfully.', 'wplcs') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to suspend license.', 'wplcs') . '</p></div>';
            }
            break;
    }
}
?>

<div class="wrap wplcs-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wplcs-licenses-header">
        <div class="wplcs-header-actions">
            <button type="button" class="button button-primary" id="wplcs-generate-license">
                <?php _e('Generate New License', 'wplcs'); ?>
            </button>
            
            <button type="button" class="button" id="wplcs-bulk-actions" style="display: none;">
                <?php _e('Bulk Actions', 'wplcs'); ?>
            </button>
        </div>
        
        <div class="wplcs-header-filters">
            <select id="wplcs-status-filter" onchange="window.location.href = '<?php echo admin_url('admin.php?page=wplcs-licenses'); ?>&status=' + this.value;">
                <option value=""><?php _e('All Statuses', 'wplcs'); ?></option>
                <option value="active" <?php selected($status_filter, 'active'); ?>><?php _e('Active', 'wplcs'); ?></option>
                <option value="inactive" <?php selected($status_filter, 'inactive'); ?>><?php _e('Inactive', 'wplcs'); ?></option>
                <option value="expired" <?php selected($status_filter, 'expired'); ?>><?php _e('Expired', 'wplcs'); ?></option>
                <option value="suspended" <?php selected($status_filter, 'suspended'); ?>><?php _e('Suspended', 'wplcs'); ?></option>
            </select>
        </div>
    </div>
    
    <?php if (!empty($licenses)): ?>
        <div class="wplcs-licenses-table">
            <form method="post" id="wplcs-licenses-form">
                <?php wp_nonce_field('wplcs_admin_nonce'); ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" id="wplcs-select-all">
                            </td>
                            <th><?php _e('License Key', 'wplcs'); ?></th>
                            <th><?php _e('Product ID', 'wplcs'); ?></th>
                            <th><?php _e('User/Email', 'wplcs'); ?></th>
                            <th><?php _e('Status', 'wplcs'); ?></th>
                            <th><?php _e('Activations', 'wplcs'); ?></th>
                            <th><?php _e('Expires', 'wplcs'); ?></th>
                            <th><?php _e('Created', 'wplcs'); ?></th>
                            <th><?php _e('Actions', 'wplcs'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licenses as $license): ?>
                            <tr>
                                <th class="check-column">
                                    <input type="checkbox" name="license_ids[]" value="<?php echo esc_attr($license->id); ?>" class="wplcs-license-checkbox">
                                </th>
                                <td>
                                    <code class="wplcs-license-key"><?php echo esc_html($license->license_key); ?></code>
                                    <button type="button" class="button-link wplcs-copy-license" data-license="<?php echo esc_attr($license->license_key); ?>">
                                        <?php _e('Copy', 'wplcs'); ?>
                                    </button>
                                </td>
                                <td><?php echo esc_html($license->product_id); ?></td>
                                <td>
                                    <?php if ($license->user_id): ?>
                                        <?php 
                                        $user = get_userdata($license->user_id);
                                        echo $user ? esc_html($user->display_name) : __('User not found', 'wplcs');
                                        ?>
                                    <?php elseif ($license->email): ?>
                                        <?php echo esc_html($license->email); ?>
                                    <?php else: ?>
                                        <em><?php _e('No user assigned', 'wplcs'); ?></em>
                                    <?php endif; ?>
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
                                    <?php if ($license->expires_at): ?>
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($license->expires_at))); ?>
                                    <?php else: ?>
                                        <em><?php _e('Never', 'wplcs'); ?></em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($license->created_at))); ?>
                                </td>
                                <td>
                                    <div class="wplcs-row-actions">
                                        <?php if ($license->status !== 'active'): ?>
                                            <form method="post" style="display: inline;">
                                                <?php wp_nonce_field('wplcs_admin_nonce'); ?>
                                                <input type="hidden" name="license_id" value="<?php echo esc_attr($license->id); ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="button button-small button-primary">
                                                    <?php _e('Activate', 'wplcs'); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($license->status === 'active'): ?>
                                            <form method="post" style="display: inline;">
                                                <?php wp_nonce_field('wplcs_admin_nonce'); ?>
                                                <input type="hidden" name="license_id" value="<?php echo esc_attr($license->id); ?>">
                                                <input type="hidden" name="action" value="suspend">
                                                <button type="submit" class="button button-small">
                                                    <?php _e('Suspend', 'wplcs'); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="post" style="display: inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this license?', 'wplcs'); ?>');">
                                            <?php wp_nonce_field('wplcs_admin_nonce'); ?>
                                            <input type="hidden" name="license_id" value="<?php echo esc_attr($license->id); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="button button-small button-link-delete">
                                                <?php _e('Delete', 'wplcs'); ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
        
        <div class="wplcs-pagination">
            <?php
            $total_pages = ceil(WPLCS_Database::get_license_stats()['total'] / $per_page);
            if ($total_pages > 1) {
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo; Previous', 'wplcs'),
                    'next_text' => __('Next &raquo;', 'wplcs'),
                    'total' => $total_pages,
                    'current' => $page
                ));
            }
            ?>
        </div>
    <?php else: ?>
        <div class="wplcs-empty-state">
            <h2><?php _e('No Licenses Found', 'wplcs'); ?></h2>
            <p><?php _e('You haven\'t generated any licenses yet. Create your first license to get started.', 'wplcs'); ?></p>
            <button type="button" class="button button-primary" id="wplcs-generate-first-license">
                <?php _e('Generate First License', 'wplcs'); ?>
            </button>
        </div>
    <?php endif; ?>
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
