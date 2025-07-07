<?php
/**
 * WordPress Plugin Testing Environment
 * This file provides a minimal WordPress-like environment for testing the WPLCS plugin
 */

// Simulate WordPress constants and functions
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
define('WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins');
define('WP_CONTENT_URL', 'http://localhost:5000/wp-content');
define('WP_PLUGIN_URL', 'http://localhost:5000/wp-content/plugins');

// Simulate WordPress functions
function plugin_dir_path($file) {
    return dirname($file) . '/';
}

function plugin_dir_url($file) {
    return 'http://localhost:5000/' . basename(dirname($file)) . '/';
}

function plugin_basename($file) {
    return basename(dirname($file)) . '/' . basename($file);
}

function add_action($hook, $function) {
    // Simulate WordPress action hooks
    return true;
}

function add_option($option, $value) {
    // Simulate WordPress options
    return true;
}

function load_plugin_textdomain($domain, $deprecated, $plugin_rel_path) {
    return true;
}

function register_activation_hook($file, $function) {
    return true;
}

function register_deactivation_hook($file, $function) {
    return true;
}

function flush_rewrite_rules() {
    return true;
}

function wp_clear_scheduled_hook($hook) {
    return true;
}

function is_admin() {
    return isset($_GET['admin']) || strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false;
}

function current_user_can($capability) {
    return true; // Simulate admin user
}

function wp_die($message) {
    die($message);
}

function __($text, $domain = 'default') {
    return $text;
}

function _e($text, $domain = 'default') {
    echo $text;
}

function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_url($url) {
    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

function wp_nonce_field($action, $name = '_wpnonce') {
    $nonce = wp_create_nonce($action);
    echo '<input type="hidden" name="' . $name . '" value="' . $nonce . '">';
}

function wp_create_nonce($action) {
    return hash('sha256', $action . 'secret');
}

function check_admin_referer($action, $query_arg = '_wpnonce') {
    return true;
}

function sanitize_text_field($str) {
    return trim(strip_tags($str));
}

function get_option($option, $default = false) {
    $options = array(
        'wplcs_license_key_length' => 32,
        'wplcs_license_key_format' => 'XXXX-XXXX-XXXX-XXXX',
        'wplcs_enable_api' => 1,
        'wplcs_api_key' => 'test-api-key-12345',
        'wplcs_db_version' => '1.0.0'
    );
    
    return isset($options[$option]) ? $options[$option] : $default;
}

function update_option($option, $value) {
    return true;
}

function wp_generate_password($length = 12, $special_chars = true) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    if ($special_chars) {
        $chars .= '!@#$%^&*()';
    }
    
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}

function get_admin_page_title() {
    return 'WPLCS Settings';
}

function home_url($path = '') {
    return 'http://localhost:5000' . $path;
}

function date_i18n($format, $timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    return date($format, $timestamp);
}

function selected($selected, $current = true, $echo = true) {
    $result = selected_helper($selected, $current);
    if ($echo) {
        echo $result;
    }
    return $result;
}

function selected_helper($selected, $current) {
    if ((string) $selected === (string) $current) {
        return ' selected="selected"';
    }
    return '';
}

function checked($checked, $current = true, $echo = true) {
    $result = checked_helper($checked, $current);
    if ($echo) {
        echo $result;
    }
    return $result;
}

function checked_helper($checked, $current) {
    if ((string) $checked === (string) $current) {
        return ' checked="checked"';
    }
    return '';
}

function submit_button($text = 'Save Changes', $type = 'primary', $name = 'submit') {
    echo '<p class="submit"><input type="submit" name="' . $name . '" class="button button-' . $type . '" value="' . $text . '"></p>';
}

// Simulate global $wpdb
global $wpdb;
$wpdb = new stdClass();
$wpdb->prefix = 'wp_';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WPLCS - WordPress License Control System</title>
    <link rel="stylesheet" href="assets/admin-style.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f1f1f1;
        }
        .header {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .nav-tab {
            padding: 10px 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .nav-tab.active {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
        }
        .content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>WPLCS - WordPress License Control System</h1>
        <p>Testing Environment - Version 1.0.0</p>
    </div>

    <div class="nav-tabs">
        <a href="?page=dashboard" class="nav-tab <?php echo (!isset($_GET['page']) || $_GET['page'] == 'dashboard') ? 'active' : ''; ?>">Dashboard</a>
        <a href="?page=licenses" class="nav-tab <?php echo (isset($_GET['page']) && $_GET['page'] == 'licenses') ? 'active' : ''; ?>">Licenses</a>
        <a href="?page=settings" class="nav-tab <?php echo (isset($_GET['page']) && $_GET['page'] == 'settings') ? 'active' : ''; ?>">Settings</a>
    </div>

    <div class="content">
        <?php
        // Load the plugin
        try {
            require_once 'wplcs.php';
            
            // Display different pages based on the request
            $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
            
            switch ($page) {
                case 'licenses':
                    echo '<h2>License Management</h2>';
                    if (file_exists('templates/admin-licenses.php')) {
                        include 'templates/admin-licenses.php';
                    } else {
                        echo '<p>License management template not found.</p>';
                    }
                    break;
                    
                case 'settings':
                    echo '<h2>Plugin Settings</h2>';
                    if (file_exists('templates/admin-settings.php')) {
                        include 'templates/admin-settings.php';
                    } else {
                        echo '<p>Settings template not found.</p>';
                    }
                    break;
                    
                default:
                    echo '<h2>Dashboard</h2>';
                    if (file_exists('templates/admin-dashboard.php')) {
                        include 'templates/admin-dashboard.php';
                    } else {
                        echo '<p>Dashboard template not found.</p>';
                    }
                    break;
            }
            
        } catch (Exception $e) {
            echo '<div class="error"><p>Error loading plugin: ' . $e->getMessage() . '</p></div>';
        }
        ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Simulate WordPress AJAX object
        var wplcs_ajax = {
            ajax_url: '<?php echo home_url("/wp-admin/admin-ajax.php"); ?>',
            nonce: '<?php echo wp_create_nonce("wplcs_ajax_nonce"); ?>'
        };
    </script>
    <script src="assets/admin-script.js"></script>
</body>
</html>