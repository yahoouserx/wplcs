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
    public $shortcodes;
    public $shortcode_ajax;
    public $admin_panel;
    public $admin_plans;
    public $admin_users;
    
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
        
        // Map namespace parts to correct directory structure
        $namespace_parts = explode('\\', $relative_class);
        
        if (count($namespace_parts) >= 2) {
            $namespace = $namespace_parts[0];
            $class_name = $namespace_parts[1];
            
            // Map namespaces to directories
            $directory_map = array(
                'Core' => 'core',
                'Admin' => 'admin',
                'Endpoints' => 'endpoints',
                'Includes' => 'includes',
                'UI' => 'ui'
            );
            
            if (isset($directory_map[$namespace])) {
                $file = $base_dir . $directory_map[$namespace] . '/' . $class_name . '.php';
            } else {
                // Fallback to original behavior
                $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            }
        } else {
            // Single level namespace, use lowercase
            $file = $base_dir . strtolower($relative_class) . '.php';
        }
        
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
            // Only add admin notice if not during activation
            if (!did_action('activate_plugin')) {
                add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            }
            return false;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            // Only add admin notice if not during activation
            if (!did_action('activate_plugin')) {
                add_action('admin_notices', array($this, 'php_version_notice'));
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Core components
        $this->database = new WPLCS\Core\Database();
        $this->tokens = new WPLCS\Core\Tokens();
        $this->sessions = new WPLCS\Core\Sessions();
        $this->security = new WPLCS\Core\Security();
        
        // API
        $this->api = new WPLCS\Endpoints\API();
        
        // Integration
        $this->woocommerce = new WPLCS\Includes\WooCommerce_Integration();
        
        // UI components
        $this->dashboard = new WPLCS\UI\Dashboard();
        
        // New UI components
        $this->shortcodes = new WPLCS\UI\Shortcodes();
        $this->shortcode_ajax = new WPLCS\UI\Shortcode_Ajax();
        
        // Admin components
        $this->admin_panel = new WPLCS\Admin\Admin_Panel();
        
        // New Admin components
        $this->admin_plans = new WPLCS\Admin\Admin_Plans();
        $this->admin_users = new WPLCS\Admin\Admin_Users();
    }
    
    /**
     * Initialize component hooks
     */
    private function init_component_hooks() {
        // Initialize core components that have init() methods
        if (method_exists($this->database, 'init')) {
            $this->database->init();
        }
        if (method_exists($this->tokens, 'init')) {
            $this->tokens->init();
        }
        if (method_exists($this->sessions, 'init')) {
            $this->sessions->init();
        }
        if (method_exists($this->security, 'init')) {
            $this->security->init();
        }
        if (method_exists($this->woocommerce, 'init')) {
            $this->woocommerce->init();
        }
        if (method_exists($this->api, 'init')) {
            $this->api->init();
        }
        if (method_exists($this->dashboard, 'init')) {
            $this->dashboard->init();
        }
        if (method_exists($this->admin_panel, 'init')) {
            $this->admin_panel->init();
        }
        
        // New components (shortcodes, admin_plans, admin_users) 
        // are initialized in their constructors, no init() method needed
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
        // Start output buffering to prevent unexpected output
        ob_start();
        
        try {
            // Check dependencies first
            if (!class_exists('WooCommerce')) {
                throw new Exception('WooCommerce is required');
            }
            
            if (version_compare(PHP_VERSION, '7.4', '<')) {
                throw new Exception('PHP 7.4 or higher is required');
            }
            
            // Create database tables
            require_once WPLCS_CORE_DIR . 'Database.php';
            $database = new WPLCS\Core\Database();
            $database->create_tables();
            
            // Create default tiers (only if they don't exist)
            $this->create_default_tiers();
            
            // Set activation timestamp
            update_option('wplcs_activation_time', time());
            update_option('wplcs_db_version', WPLCS\Core\Database::DB_VERSION);
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
        } catch (Exception $e) {
            // Log error but don't output it
            error_log('WPLCS Activation Error: ' . $e->getMessage());
            
            // Clean buffer and deactivate if there was an error
            ob_end_clean();
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('WPLCS activation failed: ' . $e->getMessage());
        }
        
        // Clean output buffer
        ob_end_clean();
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
        global $wpdb;
        $table_name = $wpdb->prefix . 'wplcs_tiers';
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        if (!$table_exists) {
            return; // Table doesn't exist yet, skip
        }
        
        // Check if tiers already exist
        $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        if ($existing_count > 0) {
            return; // Tiers already exist, skip creation
        }
        
        $default_tiers = array(
            array(
                'name' => 'Trial',
                'slug' => 'trial',
                'max_nodes' => 1,
                'duration' => 604800, // 7 days in seconds
                'is_trial' => 1,
                'features' => '["basic_access"]',
                'price' => 0.00,
                'sort_order' => 1,
                'is_active' => 1
            ),
            array(
                'name' => 'Basic',
                'slug' => 'basic',
                'max_nodes' => 3,
                'duration' => 2592000, // 30 days in seconds
                'is_trial' => 0,
                'features' => '["basic_access","priority_support"]',
                'price' => 9.99,
                'sort_order' => 2,
                'is_active' => 1
            ),
            array(
                'name' => 'Professional',
                'slug' => 'professional',
                'max_nodes' => 10,
                'duration' => 7776000, // 90 days in seconds
                'is_trial' => 0,
                'features' => '["basic_access","priority_support","advanced_features"]',
                'price' => 29.99,
                'sort_order' => 3,
                'is_active' => 1
            ),
            array(
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'max_nodes' => -1,
                'duration' => 31536000, // 365 days in seconds
                'is_trial' => 0,
                'features' => '["basic_access","priority_support","advanced_features","unlimited_nodes"]',
                'price' => 99.99,
                'sort_order' => 4,
                'is_active' => 1
            )
        );
        
        foreach ($default_tiers as $tier) {
            @$wpdb->insert($table_name, $tier);
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