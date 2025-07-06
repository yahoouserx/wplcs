<?php

namespace WPLCS\Endpoints;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API endpoints for WPLCS
 */
class API {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'wplcs/v1';
    
    /**
     * Initialize API
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Status endpoint
        register_rest_route(self::NAMESPACE, '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_status'),
            'permission_callback' => array($this, 'verify_token_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Access token'
                )
            )
        ));
        
        // Resource endpoint
        register_rest_route(self::NAMESPACE, '/resource', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_resource'),
            'permission_callback' => array($this, 'verify_token_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Access token'
                ),
                'type' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Resource type'
                )
            )
        ));
        
        // Serve/Download endpoint
        register_rest_route(self::NAMESPACE, '/serve', array(
            'methods' => 'GET',
            'callback' => array($this, 'serve_resource'),
            'permission_callback' => array($this, 'verify_token_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Access token'
                ),
                'resource' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Resource identifier'
                )
            )
        ));
        
        // Session management
        register_rest_route(self::NAMESPACE, '/session', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_session'),
                'permission_callback' => array($this, 'verify_token_permission'),
                'args' => array(
                    'token' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'Access token'
                    ),
                    'domain' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'Node domain'
                    )
                )
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_session'),
                'permission_callback' => array($this, 'verify_token_permission'),
                'args' => array(
                    'session_id' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'Session ID'
                    )
                )
            )
        ));
        
        // Heartbeat endpoint
        register_rest_route(self::NAMESPACE, '/heartbeat', array(
            'methods' => 'POST',
            'callback' => array($this, 'heartbeat'),
            'permission_callback' => array($this, 'verify_session_permission'),
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Session ID'
                )
            )
        ));
    }
    
    /**
     * Check token status
     */
    public function check_status($request) {
        $token = $request->get_param('token');
        
        $tokens = new \WPLCS\Core\Tokens();
        $token_data = $tokens->validate_token($token);
        
        if (is_wp_error($token_data)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => $token_data->get_error_message(),
                'code' => $token_data->get_error_code()
            ), 400);
        }
        
        $sessions = new \WPLCS\Core\Sessions();
        $active_sessions = $sessions->get_token_sessions($token_data->id, true);
        
        $response = array(
            'success' => true,
            'data' => array(
                'token_id' => $token_data->id,
                'tier' => $token_data->tier_name,
                'max_nodes' => $token_data->max_nodes,
                'current_nodes' => count($active_sessions),
                'expires_at' => $token_data->expires_at,
                'is_trial' => (bool) $token_data->is_trial,
                'features' => json_decode($token_data->features, true),
                'usage_count' => $token_data->usage_count,
                'last_used' => $token_data->last_used_at
            )
        );
        
        return new \WP_REST_Response($response, 200);
    }
    
    /**
     * Check available resources
     */
    public function check_resource($request) {
        $token = $request->get_param('token');
        $type = $request->get_param('type');
        
        $tokens = new \WPLCS\Core\Tokens();
        $token_data = $tokens->validate_token($token);
        
        if (is_wp_error($token_data)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => $token_data->get_error_message()
            ), 400);
        }
        
        // Get available resources based on tier
        $resources = $this->get_tier_resources($token_data->tier_id, $type);
        
        $response = array(
            'success' => true,
            'data' => array(
                'resources' => $resources,
                'tier' => $token_data->tier_name,
                'has_access' => !empty($resources)
            )
        );
        
        return new \WP_REST_Response($response, 200);
    }
    
    /**
     * Serve/Download resource
     */
    public function serve_resource($request) {
        $token = $request->get_param('token');
        $resource = $request->get_param('resource');
        
        $tokens = new \WPLCS\Core\Tokens();
        $token_data = $tokens->validate_token($token);
        
        if (is_wp_error($token_data)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => $token_data->get_error_message()
            ), 400);
        }
        
        // Validate resource access
        $has_access = $this->validate_resource_access($token_data->tier_id, $resource);
        
        if (!$has_access) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => __('Access denied to this resource.', 'wplcs')
            ), 403);
        }
        
        // Log resource access
        $this->log_resource_access($token_data->id, $resource);
        
        // Get resource data
        $resource_data = $this->get_resource_data($resource);
        
        if (!$resource_data) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => __('Resource not found.', 'wplcs')
            ), 404);
        }
        
        // Return signed URL or direct download
        if ($resource_data['type'] === 'file') {
            return $this->serve_file($resource_data);
        } else {
            return new \WP_REST_Response(array(
                'success' => true,
                'data' => $resource_data
            ), 200);
        }
    }
    
    /**
     * Create session
     */
    public function create_session($request) {
        $token = $request->get_param('token');
        $domain = $request->get_param('domain');
        
        $tokens = new \WPLCS\Core\Tokens();
        $token_data = $tokens->validate_token($token);
        
        if (is_wp_error($token_data)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => $token_data->get_error_message()
            ), 400);
        }
        
        // Sanitize domain
        $security = new \WPLCS\Core\Security();
        $clean_domain = $security->sanitize_domain($domain);
        
        if (!$clean_domain) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => __('Invalid domain format.', 'wplcs')
            ), 400);
        }
        
        $sessions = new \WPLCS\Core\Sessions();
        $session_data = $sessions->create_session(
            $token_data->id,
            $clean_domain,
            $security->get_client_ip(),
            isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        );
        
        if (is_wp_error($session_data)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => $session_data->get_error_message()
            ), 400);
        }
        
        return new \WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'session_id' => $session_data['session_id'],
                'expires_at' => $session_data['expires_at'],
                'domain' => $clean_domain
            )
        ), 201);
    }
    
    /**
     * Delete session
     */
    public function delete_session($request) {
        $session_id = $request->get_param('session_id');
        
        $sessions = new \WPLCS\Core\Sessions();
        $result = $sessions->deactivate_session($session_id);
        
        if (!$result) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => __('Session not found or access denied.', 'wplcs')
            ), 404);
        }
        
        return new \WP_REST_Response(array(
            'success' => true,
            'message' => __('Session deactivated successfully.', 'wplcs')
        ), 200);
    }
    
    /**
     * Heartbeat endpoint
     */
    public function heartbeat($request) {
        $session_id = $request->get_param('session_id');
        
        $sessions = new \WPLCS\Core\Sessions();
        $session_data = $sessions->validate_session($session_id);
        
        if (is_wp_error($session_data)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => $session_data->get_error_message()
            ), 400);
        }
        
        return new \WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'session_id' => $session_data->session_id,
                'expires_at' => $session_data->expires_at,
                'last_activity' => $session_data->last_activity
            )
        ), 200);
    }
    
    /**
     * Verify token permission
     */
    public function verify_token_permission($request) {
        $security = new \WPLCS\Core\Security();
        
        // Rate limiting check
        $token = $request->get_param('token');
        $identifier = $security->get_rate_limit_identifier($token);
        
        if (!$security->check_rate_limit($identifier)) {
            return new \WP_Error('rate_limited', __('Rate limit exceeded.', 'wplcs'), array('status' => 429));
        }
        
        // Security checks
        $security_check = $security->verify_token_security($token, $request);
        if (is_wp_error($security_check)) {
            return $security_check;
        }
        
        return true;
    }
    
    /**
     * Verify session permission
     */
    public function verify_session_permission($request) {
        $security = new \WPLCS\Core\Security();
        
        // Rate limiting check
        $identifier = $security->get_rate_limit_identifier();
        
        if (!$security->check_rate_limit($identifier)) {
            return new \WP_Error('rate_limited', __('Rate limit exceeded.', 'wplcs'), array('status' => 429));
        }
        
        return true;
    }
    
    /**
     * Get tier resources
     */
    private function get_tier_resources($tier_id, $type = null) {
        // Get resources assigned to this tier
        $resources = get_option('wplcs_tier_resources_' . $tier_id, array());
        
        if ($type) {
            $resources = array_filter($resources, function($resource) use ($type) {
                return isset($resource['type']) && $resource['type'] === $type;
            });
        }
        
        return $resources;
    }
    
    /**
     * Validate resource access
     */
    private function validate_resource_access($tier_id, $resource_id) {
        $resources = $this->get_tier_resources($tier_id);
        
        foreach ($resources as $resource) {
            if ($resource['id'] === $resource_id) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get resource data
     */
    private function get_resource_data($resource_id) {
        $all_resources = get_option('wplcs_resources', array());
        
        return isset($all_resources[$resource_id]) ? $all_resources[$resource_id] : null;
    }
    
    /**
     * Serve file
     */
    private function serve_file($resource_data) {
        $file_path = $resource_data['path'];
        
        if (!file_exists($file_path)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => __('File not found.', 'wplcs')
            ), 404);
        }
        
        // Generate signed URL for secure download
        $security = new \WPLCS\Core\Security();
        $expires = time() + 3600; // 1 hour
        $signature = $security->generate_hash($file_path . $expires);
        
        $download_url = add_query_arg(array(
            'wplcs_download' => '1',
            'file' => base64_encode($file_path),
            'expires' => $expires,
            'signature' => $signature
        ), home_url());
        
        return new \WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'download_url' => $download_url,
                'filename' => basename($file_path),
                'size' => filesize($file_path),
                'expires_at' => date('Y-m-d H:i:s', $expires)
            )
        ), 200);
    }
    
    /**
     * Log resource access
     */
    private function log_resource_access($token_id, $resource) {
        global $wpdb;
        $database = new \WPLCS\Core\Database();
        $table = $database->get_table('logs');
        
        $security = new \WPLCS\Core\Security();
        
        $log_data = array(
            'token_id' => $token_id,
            'action' => 'resource_access',
            'resource' => $resource,
            'ip_address' => $security->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'response_code' => 200
        );
        
        $wpdb->insert($table, $log_data);
    }
}