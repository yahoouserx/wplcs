<?php
/**
 * WPLCS Admin Class
 * 
 * Handles admin interface and dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPLCS_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    /**
     * Initialize admin
     */
    public function admin_init() {
        // Register settings
        register_setting('wplcs_settings', 'wplcs_license_key_length');
        register_setting('wplcs_settings', 'wplcs_license_key_format');
        register_setting('wplcs_settings', 'wplcs_enable_api');
        register_setting('wplcs_settings', 'wplcs_api_key');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('WPLCS License Manager', 'wplcs'),
            __('License Manager', 'wplcs'),
            'manage_options',
            'wplcs-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-admin-network',
            30
        );
        
        add_submenu_page(
            'wplcs-dashboard',
            __('Dashboard', 'wplcs'),
            __('Dashboard', 'wplcs'),
            'manage_options',
            'wplcs-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'wplcs-dashboard',
            __('All Licenses', 'wplcs'),
            __('All Licenses', 'wplcs'),
            'manage_options',
            'wplcs-licenses',
            array($this, 'licenses_page')
        );
        
        add_submenu_page(
            'wplcs-dashboard',
            __('Settings', 'wplcs'),
            __('Settings', 'wplcs'),
            'manage_options',
            'wplcs-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wplcs') === false) {
            return;
        }
        
        wp_enqueue_style('wplcs-admin', WPLCS_PLUGIN_URL . 'assets/admin-style.css', array(), WPLCS_VERSION);
        wp_enqueue_script('wplcs-admin', WPLCS_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), WPLCS_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('wplcs-admin', 'wplcs_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wplcs_admin_nonce'),
            'messages' => array(
                'confirm_delete' => __('Are you sure you want to delete this license?', 'wplcs'),
                'license_copied' => __('License key copied to clipboard', 'wplcs')
            )
        ));
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        include WPLCS_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    /**
     * Licenses page
     */
    public function licenses_page() {
        include WPLCS_PLUGIN_DIR . 'templates/admin-licenses.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        include WPLCS_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    /**
     * Get admin page URL
     */
    public static function get_admin_url($page = 'dashboard') {
        return admin_url('admin.php?page=wplcs-' . $page);
    }
}
