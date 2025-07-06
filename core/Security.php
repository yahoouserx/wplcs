<?php

namespace WPLCS\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security handler for WPLCS
 */
class Security {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Database();
    }
    
    /**
     * Initialize security
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_auth_hooks'));
        add_filter('wplcs_verify_token', array($this, 'verify_token_security'), 10, 2);
        
        // Schedule rate limit cleanup
        if (!wp_next_scheduled('wplcs_cleanup_rate_limits')) {
            wp_schedule_event(time(), 'hourly', 'wplcs_cleanup_rate_limits');
        }
        
        add_action('wplcs_cleanup_rate_limits', array($this, 'cleanup_expired_rate_limits'));
    }
    
    /**
     * Register authentication hooks
     */
    public function register_auth_hooks() {
        // Headers for CORS
        add_action('rest_api_init', function() {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
            add_filter('rest_pre_serve_request', array($this, 'send_cors_headers'));
        });
    }
    
    /**
     * Send CORS headers
     */
    public function send_cors_headers($value) {
        $origin = get_http_origin();
        
        if ($origin) {
            header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        }
        
        return $value;
    }
    
    /**
     * Rate limiting check
     */
    public function check_rate_limit($identifier, $limit = null, $window = 3600) {
        if ($limit === null) {
            $limit = WPLCS_MAX_API_REQUESTS_PER_HOUR;
        }
        
        global $wpdb;
        $table = $this->database->get_table('rate_limits');
        
        // Clean up expired entries first
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE expires_at < %s",
            current_time('mysql')
        ));
        
        // Get current count
        $current_count = $wpdb->get_var($wpdb->prepare(
            "SELECT requests_count FROM {$table} WHERE identifier = %s",
            $identifier
        ));
        
        if ($current_count === null) {
            // First request in window
            $wpdb->insert($table, array(
                'identifier' => $identifier,
                'requests_count' => 1,
                'window_start' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', time() + $window)
            ));
            return true;
        }
        
        if ($current_count >= $limit) {
            return false;
        }
        
        // Increment counter
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET requests_count = requests_count + 1 WHERE identifier = %s",
            $identifier
        ));
        
        return true;
    }
    
    /**
     * Get rate limit identifier
     */
    public function get_rate_limit_identifier($token = null) {
        $ip = $this->get_client_ip();
        
        if ($token) {
            return 'token_' . hash('sha256', $token) . '_' . $ip;
        }
        
        return 'ip_' . $ip;
    }
    
    /**
     * Verify token security
     */
    public function verify_token_security($token, $request) {
        // Rate limiting
        $identifier = $this->get_rate_limit_identifier($token);
        if (!$this->check_rate_limit($identifier)) {
            return new \WP_Error('rate_limited', __('Rate limit exceeded.', 'wplcs'), array('status' => 429));
        }
        
        // IP validation
        if (!$this->validate_ip()) {
            return new \WP_Error('invalid_ip', __('Invalid IP address.', 'wplcs'), array('status' => 403));
        }
        
        // User agent validation
        if (!$this->validate_user_agent()) {
            return new \WP_Error('invalid_user_agent', __('Invalid user agent.', 'wplcs'), array('status' => 403));
        }
        
        return true;
    }
    
    /**
     * Validate IP address
     */
    private function validate_ip() {
        $ip = $this->get_client_ip();
        
        // Block localhost/private IPs in production
        if (!WP_DEBUG && (
            $ip === '127.0.0.1' || 
            $ip === '::1' ||
            strpos($ip, '192.168.') === 0 ||
            strpos($ip, '10.') === 0 ||
            strpos($ip, '172.') === 0
        )) {
            return false;
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Validate user agent
     */
    private function validate_user_agent() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Reject empty or suspicious user agents
        if (empty($user_agent) || strlen($user_agent) < 10) {
            return false;
        }
        
        // Block known bot patterns
        $blocked_patterns = array(
            'bot', 'spider', 'crawler', 'scraper', 'scanner'
        );
        
        foreach ($blocked_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Encrypt data
     */
    public function encrypt($data, $key = null) {
        if ($key === null) {
            $key = $this->get_encryption_key();
        }
        
        $cipher = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
        $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data
     */
    public function decrypt($encrypted_data, $key = null) {
        if ($key === null) {
            $key = $this->get_encryption_key();
        }
        
        $data = base64_decode($encrypted_data);
        $cipher = 'AES-256-CBC';
        $iv_length = openssl_cipher_iv_length($cipher);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
    }
    
    /**
     * Get encryption key
     */
    private function get_encryption_key() {
        $key = get_option('wplcs_encryption_key');
        
        if (!$key) {
            $key = base64_encode(random_bytes(32));
            update_option('wplcs_encryption_key', $key);
        }
        
        return base64_decode($key);
    }
    
    /**
     * Generate secure hash
     */
    public function generate_hash($data, $salt = null) {
        if ($salt === null) {
            $salt = wp_salt('secure_auth');
        }
        
        return hash_hmac('sha256', $data, $salt);
    }
    
    /**
     * Verify hash
     */
    public function verify_hash($data, $hash, $salt = null) {
        if ($salt === null) {
            $salt = wp_salt('secure_auth');
        }
        
        return hash_equals($hash, $this->generate_hash($data, $salt));
    }
    
    /**
     * Generate nonce for API requests
     */
    public function generate_api_nonce($action, $token) {
        return wp_create_nonce('wplcs_' . $action . '_' . substr($token, 0, 16));
    }
    
    /**
     * Verify API nonce
     */
    public function verify_api_nonce($nonce, $action, $token) {
        return wp_verify_nonce($nonce, 'wplcs_' . $action . '_' . substr($token, 0, 16));
    }
    
    /**
     * Sanitize domain
     */
    public function sanitize_domain($domain) {
        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $domain);
        
        // Remove www
        $domain = preg_replace('#^www\.#', '', $domain);
        
        // Remove path
        $domain = parse_url('//' . $domain, PHP_URL_HOST);
        
        // Validate domain format
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            return false;
        }
        
        return strtolower($domain);
    }
    
    /**
     * Get client IP address
     */
    public function get_client_ip() {
        $ip_fields = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_fields as $field) {
            if (!empty($_SERVER[$field])) {
                $ips = explode(',', $_SERVER[$field]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Log security event
     */
    public function log_security_event($event_type, $details = array()) {
        global $wpdb;
        $table = $this->database->get_table('logs');
        
        $log_data = array(
            'action' => 'security_' . $event_type,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'request_data' => json_encode($details)
        );
        
        $wpdb->insert($table, $log_data);
    }
    
    /**
     * Cleanup expired rate limits
     */
    public function cleanup_expired_rate_limits() {
        global $wpdb;
        $table = $this->database->get_table('rate_limits');
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE expires_at < %s",
            current_time('mysql')
        ));
    }
    
    /**
     * Check if request is from allowed origin
     */
    public function check_allowed_origin($origin = null) {
        if ($origin === null) {
            $origin = get_http_origin();
        }
        
        if (!$origin) {
            return false;
        }
        
        // Get allowed origins from settings
        $allowed_origins = get_option('wplcs_allowed_origins', array());
        
        // Always allow WordPress site URL
        $allowed_origins[] = home_url();
        $allowed_origins[] = site_url();
        
        foreach ($allowed_origins as $allowed) {
            if (strpos($origin, $allowed) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate API signature
     */
    public function validate_signature($data, $signature, $secret) {
        $expected_signature = hash_hmac('sha256', $data, $secret);
        return hash_equals($signature, $expected_signature);
    }
}