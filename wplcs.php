<?php
/**
 * Plugin Name:       WPLCS - WordPress License Control System
 * Plugin URI:        https://github.com/yahoouserx/wplcs
 * Description:       Modular and secure token-based allocation, access permission enforcement, session-aware transfer operations, and distributed package availability system.
 * Version:           1.0.0
 * Author:            yahoouserx
 * Author URI:        https://github.com/yahoouserx
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wplcs
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 5.0
 * Network:           false
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
define('WPLCS_INCLUDES_DIR', WPLCS_PLUGIN_DIR . 'includes/');
define('WPLCS_ADMIN_DIR', WPLCS_PLUGIN_DIR . 'admin/');
define('WPLCS_CORE_DIR', WPLCS_PLUGIN_DIR . 'core/');
define('WPLCS_ENDPOINTS_DIR', WPLCS_PLUGIN_DIR . 'endpoints/');
define('WPLCS_UI_DIR', WPLCS_PLUGIN_DIR . 'ui/');
define('WPLCS_ASSETS_URL', WPLCS_PLUGIN_URL . 'assets/');

// Security constants
define('WPLCS_API_VERSION', 'v1');
define('WPLCS_TOKEN_LENGTH', 64);
define('WPLCS_SESSION_TIMEOUT', 3600); // 1 hour default
define('WPLCS_MAX_API_REQUESTS_PER_HOUR', 1000);

/**
 * Main WPLCS Plugin Class
 */
final class WPLCS {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Plugin components
     */
    public $database;
    public $tokens;
    public $sessions;
    public $woocommerce;
    public $api;
    public $dashboard;
    public $admin;
    public $security;
    
    /**
     * Get plugin instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_autoloader();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'), 10);
        add_action('init', array($this, 'load_textdomain'));
        
        register_activation_hook(WPLCS_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(WPLCS_PLUGIN_FILE, array($this, 'deactivate'));
        register_uninstall_hook(WPLCS_PLUGIN_FILE, array('WPLCS', 'uninstall'));
    }
    
    /**
     * Initialize autoloader
     */
    private function init_autoloader() {
        spl_autoload_register(array($this, 'autoload'));
    }
    
    /**
     * PSR-4 Autoloader
     */
    public function autoload($class) {
        $prefix = 'WPLCS\\';
        $base_dir = WPLCS_PLUGIN_DIR;
        
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check dependencies
        if (!$this->check_dependencies()) {
            return;
        }
        
        // Initialize components
        $this->init_components();
        
        // Initialize hooks
        $this->init_component_hooks();
        
        do_action('wplcs_loaded');
    }
    
    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
        // Check WooCommerce
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return false;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Database handler
        $this->database = new WPLCS\Core\Database();
        
        // Core components
        $this->tokens = new WPLCS\Core\Tokens();
        $this->sessions = new WPLCS\Core\Sessions();
        $this->security = new WPLCS\Core\Security();
        
        // Integration components
        $this->woocommerce = new WPLCS\Includes\WooCommerce_Integration();
        
        // API components
        $this->api = new WPLCS\Endpoints\API();
        
        // UI components
        $this->dashboard = new WPLCS\UI\Dashboard();
        $this->admin = new WPLCS\Admin\Admin_Panel();
    }
    
    /**
     * Initialize component hooks
     */
    private function init_component_hooks() {
        // Initialize each component
        $this->database->init();
        $this->tokens->init();
        $this->sessions->init();
        $this->security->init();
        $this->woocommerce->init();
        $this->api->init();
        $this->dashboard->init();
        $this->admin->init();
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wplcs',
            false,
            dirname(WPLCS_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $database = new WPLCS\Core\Database();
        $database->create_tables();
        
        // Create default tiers
        $this->create_default_tiers();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation timestamp
        update_option('wplcs_activation_time', time());
        
        do_action('wplcs_activated');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wplcs_cleanup_expired_tokens');
        wp_clear_scheduled_hook('wplcs_cleanup_expired_sessions');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        do_action('wplcs_deactivated');
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove database tables
        $database = new WPLCS\Core\Database();
        $database->drop_tables();
        
        // Remove options
        delete_option('wplcs_activation_time');
        delete_option('wplcs_db_version');
        delete_option('wplcs_settings');
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wplcs_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wplcs_%'");
        
        do_action('wplcs_uninstalled');
    }
    
    /**
     * Create default tiers
     */
    private function create_default_tiers() {
        $default_tiers = array(
            array(
                'name' => 'Trial',
                'max_nodes' => 1,
                'duration' => 7 * DAY_IN_SECONDS, // 7 days
                'is_trial' => 1,
                'features' => json_encode(array('basic_access'))
            ),
            array(
                'name' => 'Basic',
                'max_nodes' => 3,
                'duration' => 30 * DAY_IN_SECONDS, // 30 days
                'is_trial' => 0,
                'features' => json_encode(array('basic_access', 'priority_support'))
            ),
            array(
                'name' => 'Professional',
                'max_nodes' => 10,
                'duration' => 90 * DAY_IN_SECONDS, // 90 days
                'is_trial' => 0,
                'features' => json_encode(array('basic_access', 'priority_support', 'advanced_features'))
            ),
            array(
                'name' => 'Enterprise',
                'max_nodes' => -1, // Unlimited
                'duration' => 365 * DAY_IN_SECONDS, // 1 year
                'is_trial' => 0,
                'features' => json_encode(array('basic_access', 'priority_support', 'advanced_features', 'unlimited_nodes'))
            )
        );
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wplcs_tiers';
        
        foreach ($default_tiers as $tier) {
            $wpdb->insert($table_name, $tier);
        }
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>WPLCS:</strong> ' . 
             esc_html__('WooCommerce is required for this plugin to work properly.', 'wplcs') . 
             '</p></div>';
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        echo '<div class="error"><p><strong>WPLCS:</strong> ' . 
             esc_html__('This plugin requires PHP 7.4 or higher.', 'wplcs') . 
             '</p></div>';
    }
}

/**
 * Initialize WPLCS
 */
function WPLCS() {
    return WPLCS::instance();
}

// Initialize the plugin
WPLCS();