<?php
/**
 * WPLCS Core Class
 * 
 * Handles core plugin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPLCS_Core {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'loaded'));
    }
    
    /**
     * Initialize core functionality
     */
    public function init() {
        // Register custom post types if needed
        $this->register_post_types();
        
        // Add rewrite rules for API endpoints
        $this->add_rewrite_rules();
        
        // Schedule cleanup events
        $this->schedule_events();
    }
    
    /**
     * Plugin loaded
     */
    public function loaded() {
        // Any functionality that needs to run after WordPress is fully loaded
    }
    
    /**
     * Register custom post types
     */
    private function register_post_types() {
        // Register license product post type
        register_post_type('wplcs_product', array(
            'labels' => array(
                'name' => __('License Products', 'wplcs'),
                'singular_name' => __('License Product', 'wplcs'),
                'add_new' => __('Add New Product', 'wplcs'),
                'add_new_item' => __('Add New License Product', 'wplcs'),
                'edit_item' => __('Edit License Product', 'wplcs'),
                'new_item' => __('New License Product', 'wplcs'),
                'view_item' => __('View License Product', 'wplcs'),
                'search_items' => __('Search License Products', 'wplcs'),
                'not_found' => __('No license products found', 'wplcs'),
                'not_found_in_trash' => __('No license products found in trash', 'wplcs')
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => array('title', 'editor'),
            'rewrite' => false
        ));
    }
    
    /**
     * Add rewrite rules for API endpoints
     */
    private function add_rewrite_rules() {
        add_rewrite_rule(
            '^wplcs-api/v1/([^/]+)/?$',
            'index.php?wplcs_api=1&wplcs_action=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^wplcs-api/v1/([^/]+)/([^/]+)/?$',
            'index.php?wplcs_api=1&wplcs_action=$matches[1]&wplcs_param=$matches[2]',
            'top'
        );
    }
    
    /**
     * Schedule cleanup events
     */
    private function schedule_events() {
        if (!wp_next_scheduled('wplcs_cleanup_expired_licenses')) {
            wp_schedule_event(time(), 'daily', 'wplcs_cleanup_expired_licenses');
        }
        
        add_action('wplcs_cleanup_expired_licenses', array($this, 'cleanup_expired_licenses'));
    }
    
    /**
     * Cleanup expired licenses
     */
    public function cleanup_expired_licenses() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplcs_licenses';
        $expired_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        // Delete expired licenses that are marked for deletion
        $wpdb->delete(
            $table_name,
            array(
                'status' => 'expired',
                'expires_at' => array('value' => $expired_date, 'compare' => '<')
            ),
            array('%s', '%s')
        );
    }
    
    /**
     * Get plugin version
     */
    public static function get_version() {
        return WPLCS_VERSION;
    }
    
    /**
     * Get plugin path
     */
    public static function get_plugin_path() {
        return WPLCS_PLUGIN_DIR;
    }
    
    /**
     * Get plugin URL
     */
    public static function get_plugin_url() {
        return WPLCS_PLUGIN_URL;
    }
}
