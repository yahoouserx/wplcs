<?php
/**
 * Admin Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && check_admin_referer('wplcs_settings_nonce')) {
    update_option('wplcs_license_key_length', intval($_POST['wplcs_license_key_length']));
    update_option('wplcs_license_key_format', sanitize_text_field($_POST['wplcs_license_key_format']));
    update_option('wplcs_enable_api', intval($_POST['wplcs_enable_api']));
    
    // Generate API key if enabled and not set
    if (intval($_POST['wplcs_enable_api']) && !get_option('wplcs_api_key')) {
        $api_key = wp_generate_password(32, false);
        update_option('wplcs_api_key', $api_key);
    }
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'wplcs') . '</p></div>';
}

// Get current settings
$license_key_length = get_option('wplcs_license_key_length', 32);
$license_key_format = get_option('wplcs_license_key_format', 'XXXX-XXXX-XXXX-XXXX');
$enable_api = get_option('wplcs_enable_api', 1);
$api_key = get_option('wplcs_api_key', '');
?>

<div class="wrap wplcs-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('wplcs_settings_nonce'); ?>
        
        <div class="wplcs-settings-sections">
            <div class="wplcs-settings-section">
                <h2><?php _e('License Key Settings', 'wplcs'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wplcs_license_key_length"><?php _e('License Key Length', 'wplcs'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="wplcs_license_key_length" name="wplcs_license_key_length" 
                                   value="<?php echo esc_attr($license_key_length); ?>" min="16" max="64" class="small-text">
                            <p class="description">
                                <?php _e('The length of generated license keys (16-64 characters).', 'wplcs'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wplcs_license_key_format"><?php _e('License Key Format', 'wplcs'); ?></label>
                        </th>
                        <td>
                            <select id="wplcs_license_key_format" name="wplcs_license_key_format">
                                <option value="XXXX-XXXX-XXXX-XXXX" <?php selected($license_key_format, 'XXXX-XXXX-XXXX-XXXX'); ?>>
                                    XXXX-XXXX-XXXX-XXXX
                                </option>
                                <option value="XXXXXXXXXXXXXXXX" <?php selected($license_key_format, 'XXXXXXXXXXXXXXXX'); ?>>
                                    <?php _e('No formatting', 'wplcs'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('The format for displaying license keys.', 'wplcs'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="wplcs-settings-section">
                <h2><?php _e('API Settings', 'wplcs'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wplcs_enable_api"><?php _e('Enable API', 'wplcs'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="wplcs_enable_api" name="wplcs_enable_api" value="1" 
                                   <?php checked($enable_api, 1); ?>>
                            <label for="wplcs_enable_api"><?php _e('Enable REST API endpoints for license validation', 'wplcs'); ?></label>
                            <p class="description">
                                <?php _e('Allow external applications to validate licenses via API.', 'wplcs'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <?php if ($enable_api && $api_key): ?>
                    <tr>
                        <th scope="row">
                            <label for="wplcs_api_key"><?php _e('API Key', 'wplcs'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wplcs_api_key" value="<?php echo esc_attr($api_key); ?>" 
                                   class="regular-text" readonly>
                            <button type="button" class="button wplcs-copy-api-key" data-api-key="<?php echo esc_attr($api_key); ?>">
                                <?php _e('Copy', 'wplcs'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Use this API key for authentication when making API requests.', 'wplcs'); ?>
                            </p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="wplcs-settings-section">
                <h2><?php _e('Database Information', 'wplcs'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Database Version', 'wplcs'); ?></th>
                        <td>
                            <strong><?php echo esc_html(get_option('wplcs_db_version', '0')); ?></strong>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Plugin Version', 'wplcs'); ?></th>
                        <td>
                            <strong><?php echo esc_html(WPLCS_VERSION); ?></strong>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Database Tables', 'wplcs'); ?></th>
                        <td>
                            <ul>
                                <li><?php echo esc_html($GLOBALS['wpdb']->prefix . 'wplcs_licenses'); ?></li>
                                <li><?php echo esc_html($GLOBALS['wpdb']->prefix . 'wplcs_license_activations'); ?></li>
                                <li><?php echo esc_html($GLOBALS['wpdb']->prefix . 'wplcs_license_logs'); ?></li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="wplcs-settings-section">
                <h2><?php _e('API Endpoints', 'wplcs'); ?></h2>
                
                <div class="wplcs-api-endpoints">
                    <h3><?php _e('REST API Endpoints', 'wplcs'); ?></h3>
                    <ul>
                        <li>
                            <strong><?php _e('Validate License:', 'wplcs'); ?></strong>
                            <code><?php echo home_url('/wp-json/wplcs/v1/validate'); ?></code>
                        </li>
                        <li>
                            <strong><?php _e('Activate License:', 'wplcs'); ?></strong>
                            <code><?php echo home_url('/wp-json/wplcs/v1/activate'); ?></code>
                        </li>
                        <li>
                            <strong><?php _e('Deactivate License:', 'wplcs'); ?></strong>
                            <code><?php echo home_url('/wp-json/wplcs/v1/deactivate'); ?></code>
                        </li>
                        <li>
                            <strong><?php _e('Get License Status:', 'wplcs'); ?></strong>
                            <code><?php echo home_url('/wp-json/wplcs/v1/status'); ?></code>
                        </li>
                    </ul>
                    
                    <h3><?php _e('Legacy API Endpoints', 'wplcs'); ?></h3>
                    <ul>
                        <li>
                            <strong><?php _e('Validate License:', 'wplcs'); ?></strong>
                            <code><?php echo home_url('/wplcs-api/v1/validate'); ?></code>
                        </li>
                        <li>
                            <strong><?php _e('Activate License:', 'wplcs'); ?></strong>
                            <code><?php echo home_url('/wplcs-api/v1/activate'); ?></code>
                        </li>
                        <li>
                            <strong><?php _e('Deactivate License:', 'wplcs'); ?></strong>
                            <code><?php echo home_url('/wplcs-api/v1/deactivate'); ?></code>
                        </li>
                        <li>
                            <strong><?php _e('Get License Status:', 'wplcs'); ?></strong>
                            <code><?php echo home_url('/wplcs-api/v1/status'); ?></code>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php submit_button(__('Save Settings', 'wplcs')); ?>
    </form>
    
    <div class="wplcs-danger-zone">
        <h2><?php _e('Danger Zone', 'wplcs'); ?></h2>
        <p><?php _e('These actions are irreversible. Please proceed with caution.', 'wplcs'); ?></p>
        
        <button type="button" class="button button-secondary" id="wplcs-reset-settings">
            <?php _e('Reset Settings', 'wplcs'); ?>
        </button>
        
        <button type="button" class="button button-link-delete" id="wplcs-delete-all-data">
            <?php _e('Delete All License Data', 'wplcs'); ?>
        </button>
    </div>
</div>
