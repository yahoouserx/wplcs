<?php

namespace WPLCS\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Session management class
 */
class Sessions {
    
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
     * Initialize sessions
     */
    public function init() {
        // Schedule cleanup of expired sessions
        if (!wp_next_scheduled('wplcs_cleanup_expired_sessions')) {
            wp_schedule_event(time(), 'hourly', 'wplcs_cleanup_expired_sessions');
        }
        
        add_action('wplcs_cleanup_expired_sessions', array($this, 'cleanup_expired_sessions'));
    }
    
    /**
     * Create new session
     */
    public function create_session($token_id, $node_domain, $node_ip, $user_agent = '') {
        global $wpdb;
        
        // Get token information
        $tokens_table = $this->database->get_table('tokens');
        $token = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tokens_table} WHERE id = %d AND is_active = 1",
            $token_id
        ));
        
        if (!$token) {
            return new \WP_Error('invalid_token', __('Invalid or inactive token.', 'wplcs'));
        }
        
        // Check if token is expired
        if (strtotime($token->expires_at) < time()) {
            return new \WP_Error('expired_token', __('Token has expired.', 'wplcs'));
        }
        
        // Check current active sessions count
        $sessions_table = $this->database->get_table('sessions');
        $active_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$sessions_table} WHERE token_id = %d AND is_active = 1",
            $token_id
        ));
        
        // Check node limit (-1 means unlimited)
        if ($token->max_nodes != -1 && $active_sessions >= $token->max_nodes) {
            return new \WP_Error('node_limit_exceeded', __('Maximum number of nodes reached for this token.', 'wplcs'));
        }
        
        // Check if this domain already has an active session for this token
        $existing_session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$sessions_table} WHERE token_id = %d AND node_domain = %s AND is_active = 1",
            $token_id,
            $node_domain
        ));
        
        if ($existing_session) {
            // Update existing session
            $session_id = $this->generate_session_id();
            $expires_at = date('Y-m-d H:i:s', time() + WPLCS_SESSION_TIMEOUT);
            
            $wpdb->update(
                $sessions_table,
                array(
                    'session_id' => $session_id,
                    'node_ip' => $node_ip,
                    'user_agent' => $user_agent,
                    'expires_at' => $expires_at,
                    'last_activity' => current_time('mysql')
                ),
                array('id' => $existing_session->id)
            );
            
            $session_data = array(
                'session_id' => $session_id,
                'token_id' => $token_id,
                'node_domain' => $node_domain,
                'expires_at' => $expires_at
            );
        } else {
            // Create new session
            $session_id = $this->generate_session_id();
            $expires_at = date('Y-m-d H:i:s', time() + WPLCS_SESSION_TIMEOUT);
            
            $session_data = array(
                'token_id' => $token_id,
                'session_id' => $session_id,
                'node_domain' => $node_domain,
                'node_ip' => $node_ip,
                'user_agent' => $user_agent,
                'is_active' => 1,
                'expires_at' => $expires_at
            );
            
            $result = $wpdb->insert($sessions_table, $session_data);
            
            if ($result === false) {
                return new \WP_Error('session_creation_failed', __('Failed to create session.', 'wplcs'));
            }
            
            // Update current nodes count in token
            $wpdb->update(
                $tokens_table,
                array('current_nodes' => $active_sessions + 1),
                array('id' => $token_id)
            );
        }
        
        // Log session creation
        $this->log_action($token_id, $session_id, $token->user_id, 'session_created', array(
            'node_domain' => $node_domain,
            'node_ip' => $node_ip
        ));
        
        return $session_data;
    }
    
    /**
     * Validate session
     */
    public function validate_session($session_id) {
        if (empty($session_id)) {
            return new \WP_Error('empty_session', __('Session ID is required.', 'wplcs'));
        }
        
        global $wpdb;
        $table = $this->database->get_table('sessions');
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, t.user_id, t.expires_at as token_expires_at
             FROM {$table} s
             JOIN {$this->database->get_table('tokens')} t ON s.token_id = t.id
             WHERE s.session_id = %s AND s.is_active = 1 AND t.is_active = 1",
            $session_id
        ));
        
        if (!$session) {
            return new \WP_Error('invalid_session', __('Invalid or inactive session.', 'wplcs'));
        }
        
        // Check if session is expired
        if (strtotime($session->expires_at) < time()) {
            $this->deactivate_session($session->id);
            return new \WP_Error('expired_session', __('Session has expired.', 'wplcs'));
        }
        
        // Check if token is expired
        if (strtotime($session->token_expires_at) < time()) {
            return new \WP_Error('expired_token', __('Token has expired.', 'wplcs'));
        }
        
        // Update last activity
        $this->update_session_activity($session->id);
        
        return $session;
    }
    
    /**
     * Update session activity
     */
    public function update_session_activity($session_id) {
        global $wpdb;
        $table = $this->database->get_table('sessions');
        
        $wpdb->update(
            $table,
            array(
                'last_activity' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', time() + WPLCS_SESSION_TIMEOUT)
            ),
            array('id' => $session_id)
        );
    }
    
    /**
     * Get token sessions
     */
    public function get_token_sessions($token_id, $active_only = true) {
        global $wpdb;
        $table = $this->database->get_table('sessions');
        
        $where_clause = "token_id = %d";
        $params = array($token_id);
        
        if ($active_only) {
            $where_clause .= " AND is_active = 1 AND expires_at > %s";
            $params[] = current_time('mysql');
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC",
            $params
        ));
    }
    
    /**
     * Get user sessions
     */
    public function get_user_sessions($user_id) {
        global $wpdb;
        $sessions_table = $this->database->get_table('sessions');
        $tokens_table = $this->database->get_table('tokens');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, t.user_id 
             FROM {$sessions_table} s
             JOIN {$tokens_table} t ON s.token_id = t.id
             WHERE t.user_id = %d
             ORDER BY s.last_activity DESC",
            $user_id
        ));
    }
    
    /**
     * Deactivate session
     */
    public function deactivate_session($session_id, $user_id = null) {
        global $wpdb;
        $sessions_table = $this->database->get_table('sessions');
        $tokens_table = $this->database->get_table('tokens');
        
        // Verify session belongs to user if user_id provided
        if ($user_id) {
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT s.*, t.user_id 
                 FROM {$sessions_table} s
                 JOIN {$tokens_table} t ON s.token_id = t.id
                 WHERE s.id = %d AND t.user_id = %d",
                $session_id, $user_id
            ));
            
            if (!$session) {
                return false;
            }
        }
        
        $updated = $wpdb->update(
            $sessions_table,
            array(
                'is_active' => 0,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($updated) {
            // Log the deactivation
            $tokens = new Tokens();
            $tokens->log_action($user_id ?: 0, 'session_deactivated', null, 200, $session_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Deactivate all sessions for a token
     */
    public function deactivate_token_sessions($token_id) {
        global $wpdb;
        $table = $this->database->get_table('sessions');
        
        $result = $wpdb->update(
            $table,
            array('is_active' => 0),
            array('token_id' => $token_id)
        );
        
        // Update current nodes count in token
        $tokens_table = $this->database->get_table('tokens');
        $wpdb->update(
            $tokens_table,
            array('current_nodes' => 0),
            array('id' => $token_id)
        );
        
        return $result;
    }
    
    /**
     * Generate unique session ID
     */
    private function generate_session_id() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Log action
     */
    private function log_action($token_id, $session_id, $user_id, $action, $data = array()) {
        global $wpdb;
        $table = $this->database->get_table('logs');
        
        $log_data = array(
            'token_id' => $token_id,
            'session_id' => $session_id,
            'user_id' => $user_id,
            'action' => $action,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'request_data' => !empty($data) ? json_encode($data) : null
        );
        
        $wpdb->insert($table, $log_data);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
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
     * Cleanup expired sessions
     */
    public function cleanup_expired_sessions() {
        global $wpdb;
        $table = $this->database->get_table('sessions');
        
        // Get expired sessions to update token node counts
        $expired_sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT token_id, COUNT(*) as count 
             FROM {$table} 
             WHERE is_active = 1 AND expires_at < %s 
             GROUP BY token_id",
            current_time('mysql')
        ));
        
        // Deactivate expired sessions
        $wpdb->update(
            $table,
            array('is_active' => 0),
            array(),
            array('%d'),
            "expires_at < '" . current_time('mysql') . "'"
        );
        
        // Update token node counts
        $tokens_table = $this->database->get_table('tokens');
        foreach ($expired_sessions as $expired) {
            $active_sessions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE token_id = %d AND is_active = 1",
                $expired->token_id
            ));
            
            $wpdb->update(
                $tokens_table,
                array('current_nodes' => $active_sessions),
                array('id' => $expired->token_id)
            );
        }
    }
    
    /**
     * Get session statistics
     */
    public function get_session_stats($user_id = null, $token_id = null) {
        global $wpdb;
        $sessions_table = $this->database->get_table('sessions');
        $tokens_table = $this->database->get_table('tokens');
        
        $where_clauses = array();
        $params = array(current_time('mysql'));
        
        if ($user_id) {
            $where_clauses[] = 't.user_id = %d';
            $params[] = $user_id;
        }
        
        if ($token_id) {
            $where_clauses[] = 's.token_id = %d';
            $params[] = $token_id;
        }
        
        $where_clause = '';
        if (!empty($where_clauses)) {
            $where_clause = 'AND ' . implode(' AND ', $where_clauses);
        }
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_sessions,
                SUM(CASE WHEN s.is_active = 1 AND s.expires_at > %s THEN 1 ELSE 0 END) as active_sessions,
                COUNT(DISTINCT s.node_domain) as unique_domains,
                COUNT(DISTINCT s.token_id) as tokens_with_sessions
             FROM {$sessions_table} s
             JOIN {$tokens_table} t ON s.token_id = t.id
             WHERE 1=1 {$where_clause}",
            $params
        ));
        
        return $stats;
    }
}