<?php
/**
 * WPLCS Uninstall Script
 * 
 * This file is executed when the plugin is uninstalled (deleted).
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user has permission to uninstall plugins
if (!current_user_can('activate_plugins')) {
    exit;
}

// Remove all plugin options
$options_to_delete = array(
    'wplcs_version',
    'wplcs_db_version',
    'wplcs_license_key_length',
    'wplcs_license_key_format',
    'wplcs_enable_api',
    'wplcs_api_key'
);

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Drop database tables
global $wpdb;

$tables_to_drop = array(
    $wpdb->prefix . 'wplcs_licenses',
    $wpdb->prefix . 'wplcs_license_activations',
    $wpdb->prefix . 'wplcs_license_logs'
);

foreach ($tables_to_drop as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Clear any cached data
wp_cache_flush();

// Remove scheduled events
wp_clear_scheduled_hook('wplcs_cleanup_expired_licenses');

// Delete transients
delete_transient('wplcs_license_stats');
delete_transient('wplcs_api_cache');

// Remove user meta data related to the plugin
$wpdb->delete(
    $wpdb->usermeta,
    array('meta_key' => 'wplcs_license_preferences'),
    array('%s')
);

// Remove post meta data related to the plugin
$wpdb->delete(
    $wpdb->postmeta,
    array('meta_key' => 'wplcs_license_data'),
    array('%s')
);

// Clean up any uploaded files (if any)
$upload_dir = wp_upload_dir();
$plugin_upload_dir = $upload_dir['basedir'] . '/wplcs';

if (is_dir($plugin_upload_dir)) {
    // Remove directory and all its contents
    $files = glob($plugin_upload_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($plugin_upload_dir);
}

// Log uninstall action
error_log('WPLCS Plugin: Uninstall completed successfully');
