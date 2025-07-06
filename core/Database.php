<?php

namespace WPLCS\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database handler for WPLCS
 */
class Database {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Table names
     */
    private $tables = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->tables = array(
            'tokens' => $wpdb->prefix . 'wplcs_tokens',
            'sessions' => $wpdb->prefix . 'wplcs_sessions',
            'tiers' => $wpdb->prefix . 'wplcs_tiers',
            'logs' => $wpdb->prefix . 'wplcs_logs',
            'rate_limits' => $wpdb->prefix . 'wplcs_rate_limits'
        );
    }
    
    /**
     * Initialize database
     */
    public function init() {
        add_action('plugins_loaded', array($this, 'check_db_version'));
    }
    
    /**
     * Check database version and upgrade if needed
     */
    public function check_db_version() {
        $installed_version = get_option('wplcs_db_version', '0');
        
        if (version_compare($installed_version, self::DB_VERSION, '<')) {
            $this->create_tables();
            update_option('wplcs_db_version', self::DB_VERSION);
        }
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tokens table
        $sql_tokens = "CREATE TABLE {$this->tables['tokens']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            token_hash varchar(255) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            order_id bigint(20) unsigned DEFAULT NULL,
            tier_id bigint(20) unsigned NOT NULL,
            is_trial tinyint(1) DEFAULT 0,
            max_nodes int(11) DEFAULT 1,
            current_nodes int(11) DEFAULT 0,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            is_active tinyint(1) DEFAULT 1,
            last_used_at datetime DEFAULT NULL,
            usage_count bigint(20) DEFAULT 0,
            metadata text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token_hash (token_hash),
            KEY user_id (user_id),
            KEY order_id (order_id),
            KEY tier_id (tier_id),
            KEY expires_at (expires_at),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Sessions table - removed foreign key constraint to prevent issues
        $sql_sessions = "CREATE TABLE {$this->tables['sessions']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            token_id bigint(20) unsigned NOT NULL,
            session_id varchar(255) NOT NULL,
            node_domain varchar(255) NOT NULL,
            node_ip varchar(45) NOT NULL,
            user_agent text DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            metadata text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY token_id (token_id),
            KEY node_domain (node_domain),
            KEY node_ip (node_ip),
            KEY is_active (is_active),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Tiers table
        $sql_tiers = "CREATE TABLE {$this->tables['tiers']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            max_nodes int(11) DEFAULT 1,
            duration bigint(20) DEFAULT 2592000,
            is_trial tinyint(1) DEFAULT 0,
            features text DEFAULT NULL,
            price decimal(10,2) DEFAULT 0.00,
            sort_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        // Logs table
        $sql_logs = "CREATE TABLE {$this->tables['logs']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            token_id bigint(20) unsigned DEFAULT NULL,
            session_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            action varchar(100) NOT NULL,
            resource varchar(255) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            request_data text DEFAULT NULL,
            response_code int(11) DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY token_id (token_id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at),
            KEY ip_address (ip_address)
        ) $charset_collate;";
        
        // Rate limits table
        $sql_rate_limits = "CREATE TABLE {$this->tables['rate_limits']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            identifier varchar(255) NOT NULL,
            requests_count int(11) DEFAULT 1,
            window_start datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY identifier (identifier),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create tables with error suppression
        @dbDelta($sql_tiers);     // Create tiers first
        @dbDelta($sql_tokens);    // Then tokens
        @dbDelta($sql_sessions);  // Then sessions
        @dbDelta($sql_logs);      // Then logs
        @dbDelta($sql_rate_limits); // Finally rate limits
        
        // Create indexes separately to avoid issues
        $this->create_indexes();
    }
    
    /**
     * Create additional indexes
     */
    private function create_indexes() {
        global $wpdb;
        
        // Skip index creation during activation to avoid potential issues
        // Indexes will be created on first use if needed
        return;
        
        // Additional indexes for performance - disabled during activation
        /*
        $indexes = array(
            "CREATE INDEX idx_tokens_user_active ON {$this->tables['tokens']} (user_id, is_active)",
            "CREATE INDEX idx_tokens_expires_active ON {$this->tables['tokens']} (expires_at, is_active)",
            "CREATE INDEX idx_sessions_token_active ON {$this->tables['sessions']} (token_id, is_active)",
            "CREATE INDEX idx_logs_token_action ON {$this->tables['logs']} (token_id, action)",
            "CREATE INDEX idx_logs_user_created ON {$this->tables['logs']} (user_id, created_at)"
        );
        
        foreach ($indexes as $index_sql) {
            @$wpdb->query($index_sql);
        }
        */
    }
    
    /**
     * Drop all tables
     */
    public function drop_tables() {
        global $wpdb;
        
        // Disable foreign key checks
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
        
        foreach (array_reverse($this->tables) as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        // Re-enable foreign key checks
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
        
        delete_option('wplcs_db_version');
    }
    
    /**
     * Get table name
     */
    public function get_table($table_key) {
        return isset($this->tables[$table_key]) ? $this->tables[$table_key] : null;
    }
    
    /**
     * Get all tables
     */
    public function get_tables() {
        return $this->tables;
    }
    
    /**
     * Check if tables exist
     */
    public function tables_exist() {
        global $wpdb;
        
        foreach ($this->tables as $table) {
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table
            ));
            
            if ($table_exists !== $table) {
                return false;
            }
        }
        
        return true;
    }
}