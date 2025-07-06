<?php
/**
 * Plugin Name:       WPLCS Base Plugin
 * Plugin URI:        https://github.com/yahoouserx/wplcs
 * Description:       Base setup for custom WPLCS WordPress plugin.
 * Version:           1.0.0
 * Author:            yahoouserx
 * Author URI:        https://github.com/yahoouserx
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wplcs-base
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Plugin constants.
define( 'WPLCS_BASE_VERSION', '1.0.0' );
define( 'WPLCS_BASE_PLUGIN_FILE', __FILE__ );
define( 'WPLCS_BASE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPLCS_BASE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activation hook callback.
 */
function wplcs_base_activate() {
    // Actions to perform on plugin activation.
}
register_activation_hook( __FILE__, 'wplcs_base_activate' );

/**
 * Deactivation hook callback.
 */
function wplcs_base_deactivate() {
    // Actions to perform on plugin deactivation.
}
register_deactivation_hook( __FILE__, 'wplcs_base_deactivate' );

/**
 * Initialize the plugin.
 */
function wplcs_base_init() {
    // Plugin core initialization code goes here.
}
add_action( 'plugins_loaded', 'wplcs_base_init' );
