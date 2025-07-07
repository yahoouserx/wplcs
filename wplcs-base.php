<?php
/**
 * Plugin Name: WPLCS - WordPress License Control System
 * Plugin URI: https://github.com/yahoouserx/wplcs
 * Description: Complete WordPress license management system with license generation, validation, and admin dashboard.
 * Version: 1.0.0
 * Author: yahoouserx
 * Author URI: https://github.com/yahoouserx
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wplcs
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WPLCS_VERSION', '1.0.0');
define('WPLCS_PLUGIN_FILE', __FILE__);
define('WPLCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPLCS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPLCS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once WPLCS_PLUGIN_DIR . 'includes/class-wplcs-core.php';
require_once WPLCS_PLUGIN_DIR . 'includes/class-wplcs-database.php';
require_once WPLCS_PLUGIN_DIR . 'includes/class-wplcs-license-manager.php';
require_once WPLCS_PLUGIN_DIR . 'includes/class-wplcs-api.php';
require_once WPLCS_PLUGIN_DIR . 'includes/class-wplcs-admin.php';

/**
 * Main plugin class initialization
 */
class WPLCS_Plugin {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('wplcs', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize core components
        new WPLCS_Core();
        new WPLCS_Database();
        new WPLCS_License_Manager();
        new WPLCS_API();
        
        // Initialize admin interface if in admin
        if (is_admin()) {
            new WPLCS_Admin();
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        WPLCS_Database::create_tables();
        
        // Set default options
        add_option('wplcs_version', WPLCS_VERSION);
        add_option('wplcs_license_key_length', 32);
        add_option('wplcs_license_key_format', 'XXXX-XXXX-XXXX-XXXX');
        add_option('wplcs_enable_api', 1);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wplcs_cleanup_expired_licenses');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize plugin
WPLCS_Plugin::get_instance();
