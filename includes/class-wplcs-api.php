<?php
/**
 * WPLCS API Class
 * 
 * Handles REST API endpoints for license operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPLCS_API {
    
    private $license_manager;
    
    public function __construct() {
        $this->license_manager = new WPLCS_License_Manager();
        
        add_action('init', array($this, 'init'));
        add_action('template_redirect', array($this, 'handle_api_request'));
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    /**
     * Initialize API
     */
    public function init() {
        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Add custom query variables
     */
    public function add_query_vars($vars) {
        $vars[] = 'wplcs_api';
        $vars[] = 'wplcs_action';
        $vars[] = 'wplcs_param';
        return $vars;
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('wplcs/v1', '/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_validate_license'),
            'permission_callback' => array($this, 'api_permission_check')
        ));
        
        register_rest_route('wplcs/v1', '/activate', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_activate_license'),
            'permission_callback' => array($this, 'api_permission_check')
        ));
        
        register_rest_route('wplcs/v1', '/deactivate', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_deactivate_license'),
            'permission_callback' => array($this, 'api_permission_check')
        ));
        
        register_rest_route('wplcs/v1', '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_license_status'),
            'permission_callback' => array($this, 'api_permission_check')
        ));
    }
    
    /**
     * Handle legacy API requests
     */
    public function handle_api_request() {
        if (!get_query_var('wplcs_api')) {
            return;
        }
        
        $action = get_query_var('wplcs_action');
        
        if (!get_option('wplcs_enable_api', 1)) {
            $this->send_api_response(array(
                'success' => false,
                'message' => 'API is disabled'
            ), 403);
            return;
        }
        
        switch ($action) {
            case 'validate':
                $this->handle_validate_request();
                break;
            case 'activate':
                $this->handle_activate_request();
                break;
            case 'deactivate':
                $this->handle_deactivate_request();
                break;
            case 'status':
                $this->handle_status_request();
                break;
            default:
                $this->send_api_response(array(
                    'success' => false,
                    'message' => 'Invalid API action'
                ), 400);
                break;
        }
    }
    
    /**
     * Handle validate license request
     */
    private function handle_validate_request() {
        $license_key = $this->get_request_param('license_key');
        $domain = $this->get_request_param('domain');
        
        if (empty($license_key)) {
            $this->send_api_response(array(
                'success' => false,
                'message' => 'License key is required'
            ), 400);
            return;
        }
        
        $result = $this->license_manager->validate_license($license_key, $domain);
        $this->send_api_response($result);
    }
    
    /**
     * Handle activate license request
     */
    private function handle_activate_request() {
        $license_key = $this->get_request_param('license_key');
        $domain = $this->get_request_param('domain');
        
        if (empty($license_key) || empty($domain)) {
            $this->send_api_response(array(
                'success' => false,
                'message' => 'License key and domain are required'
            ), 400);
            return;
        }
        
        $result = $this->license_manager->activate_license($license_key, $domain);
        $this->send_api_response($result);
    }
    
    /**
     * Handle deactivate license request
     */
    private function handle_deactivate_request() {
        $license_key = $this->get_request_param('license_key');
        $domain = $this->get_request_param('domain');
        
        if (empty($license_key) || empty($domain)) {
            $this->send_api_response(array(
                'success' => false,
                'message' => 'License key and domain are required'
            ), 400);
            return;
        }
        
        $result = $this->license_manager->deactivate_license($license_key, $domain);
        $this->send_api_response($result);
    }
    
    /**
     * Handle status request
     */
    private function handle_status_request() {
        $license_key = $this->get_request_param('license_key');
        
        if (empty($license_key)) {
            $this->send_api_response(array(
                'success' => false,
                'message' => 'License key is required'
            ), 400);
            return;
        }
        
        $license = WPLCS_Database::get_license_by_key($license_key);
        
        if (!$license) {
            $this->send_api_response(array(
                'success' => false,
                'message' => 'License not found'
            ), 404);
            return;
        }
        
        $this->send_api_response(array(
            'success' => true,
            'license' => array(
                'status' => $license->status,
                'activation_count' => $license->activation_count,
                'activation_limit' => $license->activation_limit,
                'expires_at' => $license->expires_at
            )
        ));
    }
    
    /**
     * REST API validate license endpoint
     */
    public function api_validate_license($request) {
        $license_key = $request->get_param('license_key');
        $domain = $request->get_param('domain');
        
        if (empty($license_key)) {
            return new WP_Error('missing_license_key', 'License key is required', array('status' => 400));
        }
        
        $result = $this->license_manager->validate_license($license_key, $domain);
        
        if ($result['success']) {
            return rest_ensure_response($result);
        } else {
            return new WP_Error('validation_failed', $result['message'], array('status' => 400));
        }
    }
    
    /**
     * REST API activate license endpoint
     */
    public function api_activate_license($request) {
        $license_key = $request->get_param('license_key');
        $domain = $request->get_param('domain');
        
        if (empty($license_key) || empty($domain)) {
            return new WP_Error('missing_parameters', 'License key and domain are required', array('status' => 400));
        }
        
        $result = $this->license_manager->activate_license($license_key, $domain);
        
        if ($result['success']) {
            return rest_ensure_response($result);
        } else {
            return new WP_Error('activation_failed', $result['message'], array('status' => 400));
        }
    }
    
    /**
     * REST API deactivate license endpoint
     */
    public function api_deactivate_license($request) {
        $license_key = $request->get_param('license_key');
        $domain = $request->get_param('domain');
        
        if (empty($license_key) || empty($domain)) {
            return new WP_Error('missing_parameters', 'License key and domain are required', array('status' => 400));
        }
        
        $result = $this->license_manager->deactivate_license($license_key, $domain);
        
        if ($result['success']) {
            return rest_ensure_response($result);
        } else {
            return new WP_Error('deactivation_failed', $result['message'], array('status' => 400));
        }
    }
    
    /**
     * REST API get license status endpoint
     */
    public function api_get_license_status($request) {
        $license_key = $request->get_param('license_key');
        
        if (empty($license_key)) {
            return new WP_Error('missing_license_key', 'License key is required', array('status' => 400));
        }
        
        $license = WPLCS_Database::get_license_by_key($license_key);
        
        if (!$license) {
            return new WP_Error('license_not_found', 'License not found', array('status' => 404));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'license' => array(
                'status' => $license->status,
                'activation_count' => $license->activation_count,
                'activation_limit' => $license->activation_limit,
                'expires_at' => $license->expires_at
            )
        ));
    }
    
    /**
     * API permission check
     */
    public function api_permission_check() {
        return get_option('wplcs_enable_api', 1);
    }
    
    /**
     * Get request parameter
     */
    private function get_request_param($param) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return sanitize_text_field($_POST[$param] ?? '');
        }
        return sanitize_text_field($_GET[$param] ?? '');
    }
    
    /**
     * Send API response
     */
    private function send_api_response($data, $status_code = 200) {
        status_header($status_code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
