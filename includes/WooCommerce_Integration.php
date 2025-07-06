<?php

namespace WPLCS\Includes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Integration for WPLCS
 */
class WooCommerce_Integration {
    
    /**
     * Initialize WooCommerce integration
     */
    public function init() {
        // Order completion hooks
        add_action('woocommerce_order_status_completed', array($this, 'process_completed_order'));
        add_action('woocommerce_order_status_processing', array($this, 'process_processing_order'));
        
        // Product settings
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_wplcs_product_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_wplcs_product_fields'));
        
        // My Account integration
        add_filter('woocommerce_account_menu_items', array($this, 'add_wplcs_account_menu'));
        add_action('woocommerce_account_wplcs-console_endpoint', array($this, 'wplcs_console_content'));
        add_action('init', array($this, 'add_wplcs_endpoints'));
        
        // Admin order details
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_order_wplcs_info'));
        
        // Email integration
        add_action('woocommerce_email_order_details', array($this, 'add_token_to_email'), 20, 4);
    }
    
    /**
     * Process completed order
     */
    public function process_completed_order($order_id) {
        $this->generate_tokens_for_order($order_id);
    }
    
    /**
     * Process processing order (for digital products)
     */
    public function process_processing_order($order_id) {
        $order = wc_get_order($order_id);
        
        // Only process if all items are virtual/downloadable
        $all_virtual = true;
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product->is_virtual() && !$product->is_downloadable()) {
                $all_virtual = false;
                break;
            }
        }
        
        if ($all_virtual) {
            $this->generate_tokens_for_order($order_id);
        }
    }
    
    /**
     * Generate tokens for order
     */
    public function generate_tokens_for_order($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Check if tokens already generated
        $existing_tokens = get_post_meta($order_id, '_wplcs_tokens', true);
        if (!empty($existing_tokens)) {
            return;
        }
        
        $tokens = new \WPLCS\Core\Tokens();
        $generated_tokens = array();
        
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            
            if (!$product) {
                continue;
            }
            
            // Get WPLCS settings for this product
            $wplcs_enabled = get_post_meta($product->get_id(), '_wplcs_enabled', true);
            $tier_id = get_post_meta($product->get_id(), '_wplcs_tier_id', true);
            $is_trial = get_post_meta($product->get_id(), '_wplcs_is_trial', true);
            
            if ($wplcs_enabled !== 'yes' || !$tier_id) {
                continue;
            }
            
            $quantity = $item->get_quantity();
            
            // Generate tokens based on quantity
            for ($i = 0; $i < $quantity; $i++) {
                $token_result = $tokens->generate_token(
                    $order->get_user_id(),
                    $tier_id,
                    $order_id,
                    $is_trial === 'yes'
                );
                
                if (!is_wp_error($token_result)) {
                    $generated_tokens[] = array(
                        'token' => $token_result['token'],
                        'token_id' => $token_result['token_id'],
                        'tier_name' => $token_result['tier_name'],
                        'expires_at' => $token_result['expires_at'],
                        'max_nodes' => $token_result['max_nodes'],
                        'product_id' => $product->get_id(),
                        'product_name' => $product->get_name()
                    );
                }
            }
        }
        
        if (!empty($generated_tokens)) {
            // Save tokens to order meta
            update_post_meta($order_id, '_wplcs_tokens', $generated_tokens);
            
            // Add order note
            $order->add_order_note(sprintf(
                __('WPLCS: Generated %d token(s)', 'wplcs'),
                count($generated_tokens)
            ));
            
            // Send notification email
            $this->send_token_notification_email($order, $generated_tokens);
        }
    }
    
    /**
     * Add WPLCS product fields
     */
    public function add_wplcs_product_fields() {
        global $post;
        
        echo '<div class="options_group">';
        echo '<h3>' . __('WPLCS Settings', 'wplcs') . '</h3>';
        
        // Enable WPLCS
        woocommerce_wp_checkbox(array(
            'id' => '_wplcs_enabled',
            'label' => __('Enable WPLCS', 'wplcs'),
            'description' => __('Generate WPLCS tokens when this product is purchased.', 'wplcs')
        ));
        
        // Tier selection
        $tokens = new \WPLCS\Core\Tokens();
        $tiers = $tokens->get_tiers();
        $tier_options = array('' => __('Select tier...', 'wplcs'));
        
        foreach ($tiers as $tier) {
            $tier_options[$tier->id] = sprintf(
                '%s (%d nodes, %s)',
                $tier->name,
                $tier->max_nodes == -1 ? __('unlimited', 'wplcs') : $tier->max_nodes,
                human_time_diff(0, $tier->duration)
            );
        }
        
        woocommerce_wp_select(array(
            'id' => '_wplcs_tier_id',
            'label' => __('WPLCS Tier', 'wplcs'),
            'options' => $tier_options,
            'description' => __('Select the tier for generated tokens.', 'wplcs')
        ));
        
        // Trial flag
        woocommerce_wp_checkbox(array(
            'id' => '_wplcs_is_trial',
            'label' => __('Trial Token', 'wplcs'),
            'description' => __('Mark generated tokens as trial tokens.', 'wplcs')
        ));
        
        echo '</div>';
    }
    
    /**
     * Save WPLCS product fields
     */
    public function save_wplcs_product_fields($post_id) {
        $enabled = isset($_POST['_wplcs_enabled']) ? 'yes' : 'no';
        update_post_meta($post_id, '_wplcs_enabled', $enabled);
        
        if (isset($_POST['_wplcs_tier_id'])) {
            update_post_meta($post_id, '_wplcs_tier_id', sanitize_text_field($_POST['_wplcs_tier_id']));
        }
        
        $is_trial = isset($_POST['_wplcs_is_trial']) ? 'yes' : 'no';
        update_post_meta($post_id, '_wplcs_is_trial', $is_trial);
    }
    
    /**
     * Add WPLCS account menu item
     */
    public function add_wplcs_account_menu($items) {
        $items['wplcs-console'] = __('WPLCS Console', 'wplcs');
        return $items;
    }
    
    /**
     * Add WPLCS endpoints
     */
    public function add_wplcs_endpoints() {
        add_rewrite_endpoint('wplcs-console', EP_ROOT | EP_PAGES);
    }
    
    /**
     * WPLCS console content
     */
    public function wplcs_console_content() {
        $dashboard = new \WPLCS\UI\Dashboard();
        $dashboard->render_user_dashboard();
    }
    
    /**
     * Display WPLCS info in admin order details
     */
    public function display_order_wplcs_info($order) {
        $tokens = get_post_meta($order->get_id(), '_wplcs_tokens', true);
        
        if (empty($tokens)) {
            return;
        }
        
        echo '<div class="wplcs-order-info">';
        echo '<h3>' . __('WPLCS Tokens', 'wplcs') . '</h3>';
        echo '<table class="widefat">';
        echo '<thead><tr>';
        echo '<th>' . __('Token ID', 'wplcs') . '</th>';
        echo '<th>' . __('Tier', 'wplcs') . '</th>';
        echo '<th>' . __('Max Nodes', 'wplcs') . '</th>';
        echo '<th>' . __('Expires', 'wplcs') . '</th>';
        echo '<th>' . __('Status', 'wplcs') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($tokens as $token_data) {
            $token_obj = new \WPLCS\Core\Tokens();
            $user_tokens = $token_obj->get_user_tokens($order->get_user_id(), false);
            $current_token = null;
            
            foreach ($user_tokens as $ut) {
                if ($ut->id == $token_data['token_id']) {
                    $current_token = $ut;
                    break;
                }
            }
            
            $status = $current_token && $current_token->is_active ? __('Active', 'wplcs') : __('Inactive', 'wplcs');
            if ($current_token && strtotime($current_token->expires_at) < time()) {
                $status = __('Expired', 'wplcs');
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($token_data['token_id']) . '</td>';
            echo '<td>' . esc_html($token_data['tier_name']) . '</td>';
            echo '<td>' . ($token_data['max_nodes'] == -1 ? __('Unlimited', 'wplcs') : $token_data['max_nodes']) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($token_data['expires_at']))) . '</td>';
            echo '<td>' . esc_html($status) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
    
    /**
     * Add token info to order emails
     */
    public function add_token_to_email($order, $sent_to_admin, $plain_text, $email) {
        // Only add to customer emails
        if ($sent_to_admin || $email->id !== 'customer_completed_order') {
            return;
        }
        
        $tokens = get_post_meta($order->get_id(), '_wplcs_tokens', true);
        
        if (empty($tokens)) {
            return;
        }
        
        if ($plain_text) {
            echo "\n\n" . __('WPLCS Access Tokens:', 'wplcs') . "\n";
            echo str_repeat('-', 50) . "\n";
            
            foreach ($tokens as $token_data) {
                echo sprintf(
                    __('Token: %s', 'wplcs') . "\n" .
                    __('Tier: %s', 'wplcs') . "\n" .
                    __('Max Nodes: %s', 'wplcs') . "\n" .
                    __('Expires: %s', 'wplcs') . "\n\n",
                    $token_data['token'],
                    $token_data['tier_name'],
                    $token_data['max_nodes'] == -1 ? __('Unlimited', 'wplcs') : $token_data['max_nodes'],
                    date_i18n(get_option('date_format'), strtotime($token_data['expires_at']))
                );
            }
        } else {
            echo '<h2>' . __('WPLCS Access Tokens', 'wplcs') . '</h2>';
            echo '<div style="margin: 20px 0; padding: 20px; background: #f9f9f9; border: 1px solid #ddd;">';
            
            foreach ($tokens as $token_data) {
                echo '<div style="margin-bottom: 15px; padding: 15px; background: white; border-left: 4px solid #0073aa;">';
                echo '<h4 style="margin: 0 0 10px 0;">' . esc_html($token_data['tier_name']) . '</h4>';
                echo '<p><strong>' . __('Token:', 'wplcs') . '</strong> <code>' . esc_html($token_data['token']) . '</code></p>';
                echo '<p><strong>' . __('Max Nodes:', 'wplcs') . '</strong> ' . ($token_data['max_nodes'] == -1 ? __('Unlimited', 'wplcs') : $token_data['max_nodes']) . '</p>';
                echo '<p><strong>' . __('Expires:', 'wplcs') . '</strong> ' . esc_html(date_i18n(get_option('date_format'), strtotime($token_data['expires_at']))) . '</p>';
                echo '</div>';
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Send token notification email
     */
    private function send_token_notification_email($order, $tokens) {
        $user = get_user_by('id', $order->get_user_id());
        
        if (!$user) {
            return;
        }
        
        $subject = sprintf(__('[%s] Your WPLCS Access Tokens', 'wplcs'), get_bloginfo('name'));
        
        $message = sprintf(__('Hi %s,', 'wplcs'), $user->display_name) . "\n\n";
        $message .= __('Your WPLCS access tokens have been generated:', 'wplcs') . "\n\n";
        
        foreach ($tokens as $token_data) {
            $message .= sprintf(
                __('Tier: %s', 'wplcs') . "\n" .
                __('Token: %s', 'wplcs') . "\n" .
                __('Max Nodes: %s', 'wplcs') . "\n" .
                __('Expires: %s', 'wplcs') . "\n\n",
                $token_data['tier_name'],
                $token_data['token'],
                $token_data['max_nodes'] == -1 ? __('Unlimited', 'wplcs') : $token_data['max_nodes'],
                date_i18n(get_option('date_format'), strtotime($token_data['expires_at']))
            );
        }
        
        $message .= __('You can manage your tokens in your account dashboard.', 'wplcs') . "\n";
        $message .= wc_get_page_permalink('myaccount') . 'wplcs-console/';
        
        wp_mail($user->user_email, $subject, $message);
    }
}