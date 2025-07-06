<?php
namespace WPLCS\UI;

use WPLCS\Core\Tokens;
use WPLCS\Core\Sessions;

/**
 * Shortcode AJAX Handler
 */
class Shortcode_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        // AJAX actions for logged-in users
        add_action('wp_ajax_wplcs_deactivate_session', array($this, 'deactivate_session'));
        add_action('wp_ajax_wplcs_regenerate_token', array($this, 'regenerate_token'));
        add_action('wp_ajax_wplcs_renew_license', array($this, 'renew_license'));
    }
    
    /**
     * Deactivate session AJAX handler
     */
    public function deactivate_session() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wplcs_shortcode_nonce')) {
            wp_send_json_error(__('Security verification failed.', 'wplcs'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action.', 'wplcs'));
        }
        
        $session_id = intval($_POST['session_id']);
        $user_id = get_current_user_id();
        
        if (!$session_id) {
            wp_send_json_error(__('Invalid session ID.', 'wplcs'));
        }
        
        $sessions = new Sessions();
        $result = $sessions->deactivate_session($session_id, $user_id);
        
        if ($result) {
            wp_send_json_success(__('Session deactivated successfully.', 'wplcs'));
        } else {
            wp_send_json_error(__('Failed to deactivate session.', 'wplcs'));
        }
    }
    
    /**
     * Regenerate token AJAX handler
     */
    public function regenerate_token() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wplcs_shortcode_nonce')) {
            wp_send_json_error(__('Security verification failed.', 'wplcs'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action.', 'wplcs'));
        }
        
        $token_id = intval($_POST['token_id']);
        $user_id = get_current_user_id();
        
        if (!$token_id) {
            wp_send_json_error(__('Invalid token ID.', 'wplcs'));
        }
        
        $tokens = new Tokens();
        $new_token = $tokens->regenerate_token($token_id, $user_id);
        
        if ($new_token) {
            wp_send_json_success(array(
                'message' => __('Token regenerated successfully.', 'wplcs'),
                'new_token' => $new_token
            ));
        } else {
            wp_send_json_error(__('Failed to regenerate token.', 'wplcs'));
        }
    }
    
    /**
     * Renew license AJAX handler
     */
    public function renew_license() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wplcs_shortcode_nonce')) {
            wp_send_json_error(__('Security verification failed.', 'wplcs'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action.', 'wplcs'));
        }
        
        $token_id = intval($_POST['token_id']);
        $user_id = get_current_user_id();
        
        if (!$token_id) {
            wp_send_json_error(__('Invalid token ID.', 'wplcs'));
        }
        
        // Get the token and tier information
        global $wpdb;
        $database = new \WPLCS\Core\Database();
        $tokens_table = $database->get_table('tokens');
        $tiers_table = $database->get_table('tiers');
        
        $token = $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, tier.name as tier_name, tier.price
             FROM {$tokens_table} t
             JOIN {$tiers_table} tier ON t.tier_id = tier.id
             WHERE t.id = %d AND t.user_id = %d",
            $token_id, $user_id
        ));
        
        if (!$token) {
            wp_send_json_error(__('Token not found or does not belong to you.', 'wplcs'));
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(__('WooCommerce is required for license renewal.', 'wplcs'));
        }
        
        // Find WooCommerce product for this tier
        $products = get_posts(array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => '_wplcs_tier_id',
                    'value' => $token->tier_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (empty($products)) {
            wp_send_json_error(__('No product found for license renewal.', 'wplcs'));
        }
        
        $product = $products[0];
        
        // Add product to cart and redirect to checkout
        WC()->cart->empty_cart();
        WC()->cart->add_to_cart($product->ID, 1, 0, array(), array(
            'wplcs_renewal_token_id' => $token_id
        ));
        
        $checkout_url = wc_get_checkout_url();
        
        wp_send_json_success(array(
            'message' => __('Redirecting to renewal checkout...', 'wplcs'),
            'redirect_url' => $checkout_url
        ));
    }
}