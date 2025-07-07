<?php
/**
 * WPLCS License Manager Class
 * 
 * Handles license generation, validation, and management
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPLCS_License_Manager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize license manager
     */
    public function init() {
        // Hook into WordPress actions
        add_action('wp_ajax_wplcs_generate_license', array($this, 'ajax_generate_license'));
        add_action('wp_ajax_wplcs_validate_license', array($this, 'ajax_validate_license'));
        add_action('wp_ajax_wplcs_activate_license', array($this, 'ajax_activate_license'));
        add_action('wp_ajax_wplcs_deactivate_license', array($this, 'ajax_deactivate_license'));
        
        // Non-privileged AJAX actions for API
        add_action('wp_ajax_nopriv_wplcs_validate_license', array($this, 'ajax_validate_license'));
        add_action('wp_ajax_nopriv_wplcs_activate_license', array($this, 'ajax_activate_license'));
        add_action('wp_ajax_nopriv_wplcs_deactivate_license', array($this, 'ajax_deactivate_license'));
    }
    
    /**
     * Generate a new license key
     */
    public function generate_license_key($length = 32) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $license_key = '';
        
        for ($i = 0; $i < $length; $i++) {
            $license_key .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Format the license key
        return $this->format_license_key($license_key);
    }
    
    /**
     * Format license key according to settings
     */
    private function format_license_key($license_key) {
        $format = get_option('wplcs_license_key_format', 'XXXX-XXXX-XXXX-XXXX');
        
        if ($format === 'XXXX-XXXX-XXXX-XXXX') {
            return substr($license_key, 0, 4) . '-' . 
                   substr($license_key, 4, 4) . '-' . 
                   substr($license_key, 8, 4) . '-' . 
                   substr($license_key, 12, 4);
        }
        
        return $license_key;
    }
    
    /**
     * Create a new license
     */
    public function create_license($product_id, $user_id = null, $email = '', $expires_at = null, $activation_limit = 1) {
        // Generate unique license key
        do {
            $license_key = $this->generate_license_key();
        } while (WPLCS_Database::get_license_by_key($license_key));
        
        // Prepare license data
        $license_data = array(
            'license_key' => $license_key,
            'product_id' => $product_id,
            'user_id' => $user_id,
            'email' => $email,
            'status' => 'active',
            'activation_limit' => $activation_limit,
            'activation_count' => 0,
            'expires_at' => $expires_at
        );
        
        // Insert license into database
        $license_id = WPLCS_Database::insert_license($license_data);
        
        if ($license_id) {
            // Log the license creation
            WPLCS_Database::log_license_activity($license_id, 'created', 'License created');
            
            return array(
                'success' => true,
                'license_id' => $license_id,
                'license_key' => $license_key,
                'message' => __('License created successfully', 'wplcs')
            );
        }
        
        return array(
            'success' => false,
            'message' => __('Failed to create license', 'wplcs')
        );
    }
    
    /**
     * Validate a license key
     */
    public function validate_license($license_key, $domain = '') {
        $license = WPLCS_Database::get_license_by_key($license_key);
        
        if (!$license) {
            return array(
                'success' => false,
                'message' => __('Invalid license key', 'wplcs')
            );
        }
        
        // Check if license is active
        if ($license->status !== 'active') {
            return array(
                'success' => false,
                'message' => __('License is not active', 'wplcs'),
                'status' => $license->status
            );
        }
        
        // Check if license has expired
        if ($license->expires_at && strtotime($license->expires_at) < time()) {
            // Update license status to expired
            WPLCS_Database::update_license($license->id, array('status' => 'expired'));
            
            return array(
                'success' => false,
                'message' => __('License has expired', 'wplcs'),
                'status' => 'expired'
            );
        }
        
        // Log validation attempt
        WPLCS_Database::log_license_activity($license->id, 'validated', 'License validated for domain: ' . $domain);
        
        return array(
            'success' => true,
            'message' => __('License is valid', 'wplcs'),
            'license' => $license
        );
    }
    
    /**
     * Activate a license for a domain
     */
    public function activate_license($license_key, $domain) {
        global $wpdb;
        
        $license = WPLCS_Database::get_license_by_key($license_key);
        
        if (!$license) {
            return array(
                'success' => false,
                'message' => __('Invalid license key', 'wplcs')
            );
        }
        
        // Validate license first
        $validation = $this->validate_license($license_key, $domain);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Check if domain is already activated
        $activations_table = $wpdb->prefix . 'wplcs_license_activations';
        $existing_activation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $activations_table WHERE license_id = %d AND domain = %s AND status = 'active'",
            $license->id,
            $domain
        ));
        
        if ($existing_activation) {
            return array(
                'success' => true,
                'message' => __('License is already activated for this domain', 'wplcs')
            );
        }
        
        // Check activation limit
        if ($license->activation_count >= $license->activation_limit) {
            return array(
                'success' => false,
                'message' => __('License activation limit reached', 'wplcs')
            );
        }
        
        // Insert activation record
        $activation_result = $wpdb->insert(
            $activations_table,
            array(
                'license_id' => $license->id,
                'domain' => $domain,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'status' => 'active'
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($activation_result) {
            // Update activation count
            $new_count = $license->activation_count + 1;
            WPLCS_Database::update_license($license->id, array('activation_count' => $new_count));
            
            // Log activation
            WPLCS_Database::log_license_activity($license->id, 'activated', 'License activated for domain: ' . $domain);
            
            return array(
                'success' => true,
                'message' => __('License activated successfully', 'wplcs')
            );
        }
        
        return array(
            'success' => false,
            'message' => __('Failed to activate license', 'wplcs')
        );
    }
    
    /**
     * Deactivate a license for a domain
     */
    public function deactivate_license($license_key, $domain) {
        global $wpdb;
        
        $license = WPLCS_Database::get_license_by_key($license_key);
        
        if (!$license) {
            return array(
                'success' => false,
                'message' => __('Invalid license key', 'wplcs')
            );
        }
        
        // Find activation record
        $activations_table = $wpdb->prefix . 'wplcs_license_activations';
        $activation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $activations_table WHERE license_id = %d AND domain = %s AND status = 'active'",
            $license->id,
            $domain
        ));
        
        if (!$activation) {
            return array(
                'success' => false,
                'message' => __('License is not activated for this domain', 'wplcs')
            );
        }
        
        // Update activation record
        $deactivation_result = $wpdb->update(
            $activations_table,
            array(
                'status' => 'inactive',
                'deactivated_at' => current_time('mysql')
            ),
            array('id' => $activation->id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($deactivation_result) {
            // Update activation count
            $new_count = max(0, $license->activation_count - 1);
            WPLCS_Database::update_license($license->id, array('activation_count' => $new_count));
            
            // Log deactivation
            WPLCS_Database::log_license_activity($license->id, 'deactivated', 'License deactivated for domain: ' . $domain);
            
            return array(
                'success' => true,
                'message' => __('License deactivated successfully', 'wplcs')
            );
        }
        
        return array(
            'success' => false,
            'message' => __('Failed to deactivate license', 'wplcs')
        );
    }
    
    /**
     * AJAX handler for license generation
     */
    public function ajax_generate_license() {
        check_ajax_referer('wplcs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wplcs'));
        }
        
        $product_id = intval($_POST['product_id'] ?? 0);
        $user_id = intval($_POST['user_id'] ?? 0);
        $email = sanitize_email($_POST['email'] ?? '');
        $expires_at = sanitize_text_field($_POST['expires_at'] ?? '');
        $activation_limit = intval($_POST['activation_limit'] ?? 1);
        
        if (empty($product_id)) {
            wp_send_json_error(__('Product ID is required', 'wplcs'));
        }
        
        $result = $this->create_license($product_id, $user_id, $email, $expires_at, $activation_limit);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for license validation
     */
    public function ajax_validate_license() {
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');
        $domain = sanitize_text_field($_POST['domain'] ?? '');
        
        if (empty($license_key)) {
            wp_send_json_error(__('License key is required', 'wplcs'));
        }
        
        $result = $this->validate_license($license_key, $domain);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for license activation
     */
    public function ajax_activate_license() {
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');
        $domain = sanitize_text_field($_POST['domain'] ?? '');
        
        if (empty($license_key) || empty($domain)) {
            wp_send_json_error(__('License key and domain are required', 'wplcs'));
        }
        
        $result = $this->activate_license($license_key, $domain);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for license deactivation
     */
    public function ajax_deactivate_license() {
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');
        $domain = sanitize_text_field($_POST['domain'] ?? '');
        
        if (empty($license_key) || empty($domain)) {
            wp_send_json_error(__('License key and domain are required', 'wplcs'));
        }
        
        $result = $this->deactivate_license($license_key, $domain);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
}
