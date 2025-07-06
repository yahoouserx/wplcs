<?php

namespace WPLCS\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Token management class
 */
class Tokens {
    
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
     * Initialize tokens
     */
    public function init() {
        // Schedule cleanup of expired tokens
        if (!wp_next_scheduled('wplcs_cleanup_expired_tokens')) {
            wp_schedule_event(time(), 'hourly', 'wplcs_cleanup_expired_tokens');
        }
        
        add_action('wplcs_cleanup_expired_tokens', array($this, 'cleanup_expired_tokens'));
    }
    
    /**
     * Generate a new token
     */
    public function generate_token($user_id, $tier_id, $order_id = null, $is_trial = false) {
        global $wpdb;
        
        // Get tier information
        $tier = $this->get_tier($tier_id);
        if (!$tier) {
            return new \WP_Error('invalid_tier', __('Invalid tier specified.', 'wplcs'));
        }
        
        // Generate secure token
        $token = $this->generate_secure_token();
        $token_hash = hash('sha256', $token);
        
        // Calculate expiry date
        $expires_at = date('Y-m-d H:i:s', time() + $tier->duration);
        
        // Prepare token data
        $token_data = array(
            'token_hash' => $token_hash,
            'user_id' => $user_id,
            'order_id' => $order_id,
            'tier_id' => $tier_id,
            'is_trial' => $is_trial ? 1 : 0,
            'max_nodes' => $tier->max_nodes,
            'current_nodes' => 0,
            'expires_at' => $expires_at,
            'is_active' => 1,
            'usage_count' => 0
        );
        
        // Insert token into database
        $table = $this->database->get_table('tokens');
        $result = $wpdb->insert($table, $token_data);
        
        if ($result === false) {
            return new \WP_Error('token_creation_failed', __('Failed to create token.', 'wplcs'));
        }
        
        $token_id = $wpdb->insert_id;
        
        // Log token creation
        $this->log_action($token_id, null, $user_id, 'token_created', array(
            'tier_id' => $tier_id,
            'order_id' => $order_id,
            'is_trial' => $is_trial
        ));
        
        // Return token with metadata
        return array(
            'token' => $token,
            'token_id' => $token_id,
            'expires_at' => $expires_at,
            'max_nodes' => $tier->max_nodes,
            'tier_name' => $tier->name
        );
    }
    
    /**
     * Validate token
     */
    public function validate_token($token) {
        if (empty($token)) {
            return new \WP_Error('empty_token', __('Token is required.', 'wplcs'));
        }
        
        global $wpdb;
        $table = $this->database->get_table('tokens');
        $token_hash = hash('sha256', $token);
        
        $token_data = $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, tier.name as tier_name, tier.features 
             FROM {$table} t 
             JOIN {$this->database->get_table('tiers')} tier ON t.tier_id = tier.id 
             WHERE t.token_hash = %s AND t.is_active = 1",
            $token_hash
        ));
        
        if (!$token_data) {
            return new \WP_Error('invalid_token', __('Invalid or inactive token.', 'wplcs'));
        }
        
        // Check if token is expired
        if (strtotime($token_data->expires_at) < time()) {
            return new \WP_Error('expired_token', __('Token has expired.', 'wplcs'));
        }
        
        // Update last used timestamp and usage count
        $this->update_token_usage($token_data->id);
        
        return $token_data;
    }
    
    /**
     * Update token usage
     */
    public function update_token_usage($token_id) {
        global $wpdb;
        $table = $this->database->get_table('tokens');
        
        $wpdb->update(
            $table,
            array(
                'last_used_at' => current_time('mysql'),
                'usage_count' => new \stdClass() // This will be handled in the query
            ),
            array('id' => $token_id)
        );
        
        // Use raw query for increment
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET usage_count = usage_count + 1 WHERE id = %d",
            $token_id
        ));
    }
    
    /**
     * Get user tokens
     */
    public function get_user_tokens($user_id) {
        global $wpdb;
        $tokens_table = $this->database->get_table('tokens');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tokens_table} WHERE user_id = %d AND is_active = 1 ORDER BY created_at DESC",
            $user_id
        ));
    }
    
    /**
     * Deactivate token
     */
    public function deactivate_token($token_id, $user_id = null) {
        global $wpdb;
        $table = $this->database->get_table('tokens');
        
        $where = array('id' => $token_id);
        if ($user_id) {
            $where['user_id'] = $user_id;
        }
        
        $result = $wpdb->update(
            $table,
            array('is_active' => 0),
            $where
        );
        
        if ($result !== false) {
            // Deactivate all sessions for this token
            $sessions_table = $this->database->get_table('sessions');
            $wpdb->update(
                $sessions_table,
                array('is_active' => 0),
                array('token_id' => $token_id)
            );
            
            // Log token deactivation
            $this->log_action($token_id, null, $user_id, 'token_deactivated');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Upgrade token
     */
    public function upgrade_token($token_id, $new_tier_id, $order_id = null) {
        global $wpdb;
        
        // Get new tier information
        $new_tier = $this->get_tier($new_tier_id);
        if (!$new_tier) {
            return new \WP_Error('invalid_tier', __('Invalid tier specified.', 'wplcs'));
        }
        
        // Get current token
        $table = $this->database->get_table('tokens');
        $current_token = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $token_id
        ));
        
        if (!$current_token) {
            return new \WP_Error('token_not_found', __('Token not found.', 'wplcs'));
        }
        
        // Calculate new expiry (extend from current expiry or now, whichever is later)
        $current_expiry = strtotime($current_token->expires_at);
        $now = time();
        $base_time = max($current_expiry, $now);
        $new_expires_at = date('Y-m-d H:i:s', $base_time + $new_tier->duration);
        
        // Update token
        $update_data = array(
            'tier_id' => $new_tier_id,
            'max_nodes' => $new_tier->max_nodes,
            'expires_at' => $new_expires_at,
            'is_trial' => 0 // Upgrade removes trial status
        );
        
        if ($order_id) {
            $update_data['order_id'] = $order_id;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $token_id)
        );
        
        if ($result !== false) {
            // Log token upgrade
            $this->log_action($token_id, null, $current_token->user_id, 'token_upgraded', array(
                'old_tier_id' => $current_token->tier_id,
                'new_tier_id' => $new_tier_id,
                'order_id' => $order_id
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get tier information
     */
    public function get_tier($tier_id) {
        global $wpdb;
        $tiers_table = $this->database->get_table('tiers');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tiers_table} WHERE id = %d",
            $tier_id
        ));
    }
    
    /**
     * Get all tiers
     */
    public function get_tiers() {
        global $wpdb;
        $tiers_table = $this->database->get_table('tiers');
        
        return $wpdb->get_results(
            "SELECT * FROM {$tiers_table} ORDER BY sort_order ASC, price ASC"
        );
    }
    
    /**
     * Generate secure token
     */
    private function generate_secure_token() {
        return bin2hex(random_bytes(WPLCS_TOKEN_LENGTH / 2));
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
     * Cleanup expired tokens
     */
    public function cleanup_expired_tokens() {
        global $wpdb;
        $table = $this->database->get_table('tokens');
        
        // Deactivate expired tokens
        $wpdb->update(
            $table,
            array('is_active' => 0),
            array(),
            array('%d'),
            "expires_at < '" . current_time('mysql') . "'"
        );
        
        // Cleanup old logs (older than 90 days)
        $logs_table = $this->database->get_table('logs');
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$logs_table} WHERE created_at < %s",
            date('Y-m-d H:i:s', time() - (90 * DAY_IN_SECONDS))
        ));
    }
    
    /**
     * Get token statistics
     */
    public function get_token_stats($user_id = null) {
        global $wpdb;
        $table = $this->database->get_table('tokens');
        
        $where_clause = '';
        $params = array();
        
        if ($user_id) {
            $where_clause = 'WHERE user_id = %d';
            $params[] = $user_id;
        }
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_tokens,
                SUM(CASE WHEN is_active = 1 AND expires_at > %s THEN 1 ELSE 0 END) as active_tokens,
                SUM(CASE WHEN expires_at <= %s THEN 1 ELSE 0 END) as expired_tokens,
                SUM(usage_count) as total_usage
             FROM {$table} {$where_clause}",
            array_merge([current_time('mysql'), current_time('mysql')], $params)
        ));
        
        return $stats;
    }
    
    /**
     * Get available upgrade tiers for user
     */
    public function get_available_upgrade_tiers($user_tokens) {
        global $wpdb;
        $tiers_table = $this->database->get_table('tiers');
        
        // Get user's current highest tier
        $current_tier_ids = array();
        foreach ($user_tokens as $token) {
            $current_tier_ids[] = $token->tier_id;
        }
        
        if (empty($current_tier_ids)) {
            // User has no tokens, show all tiers
            return $wpdb->get_results(
                "SELECT * FROM {$tiers_table} WHERE is_active = 1 ORDER BY price ASC"
            );
        }
        
        // Get tiers with higher price than current ones
        $current_tier_ids_str = implode(',', array_map('intval', $current_tier_ids));
        $max_price = $wpdb->get_var(
            "SELECT MAX(price) FROM {$tiers_table} WHERE id IN ({$current_tier_ids_str})"
        );
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tiers_table} WHERE is_active = 1 AND price > %f ORDER BY price ASC",
            $max_price ?: 0
        ));
    }
    
    /**
     * Regenerate token
     */
    public function regenerate_token($token_id, $user_id = null) {
        global $wpdb;
        $tokens_table = $this->database->get_table('tokens');
        
        // Verify token belongs to user if user_id provided
        if ($user_id) {
            $token = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tokens_table} WHERE id = %d AND user_id = %d",
                $token_id, $user_id
            ));
            
            if (!$token) {
                return false;
            }
        }
        
        // Generate new token
        $new_token = $this->generate_token_hash();
        
        $updated = $wpdb->update(
            $tokens_table,
            array(
                'token_hash' => $new_token,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $token_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($updated) {
            // Log the regeneration
            $this->log_action($user_id ?: 0, 'token_regenerated', null, 200, $token_id);
            return $new_token;
        }
        
        return false;
    }
}