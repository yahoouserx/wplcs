<?php

namespace WPLCS\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin panel for WPLCS
 */
class Admin_Panel {
    
    /**
     * Initialize admin panel
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_wplcs_admin_search_tokens', array($this, 'ajax_search_tokens'));
        add_action('wp_ajax_wplcs_admin_toggle_token', array($this, 'ajax_toggle_token'));
        add_action('wp_ajax_wplcs_admin_save_tier', array($this, 'ajax_save_tier'));
        add_action('wp_ajax_wplcs_admin_delete_tier', array($this, 'ajax_delete_tier'));
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        add_menu_page(
            __('WPLCS', 'wplcs'),
            __('WPLCS', 'wplcs'),
            'manage_options',
            'wplcs',
            array($this, 'dashboard_page'),
            'dashicons-shield-alt',
            30
        );
        
        add_submenu_page(
            'wplcs',
            __('Dashboard', 'wplcs'),
            __('Dashboard', 'wplcs'),
            'manage_options',
            'wplcs',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'wplcs',
            __('Tokens', 'wplcs'),
            __('Tokens', 'wplcs'),
            'manage_options',
            'wplcs-tokens',
            array($this, 'tokens_page')
        );
        
        add_submenu_page(
            'wplcs',
            __('Sessions', 'wplcs'),
            __('Sessions', 'wplcs'),
            'manage_options',
            'wplcs-sessions',
            array($this, 'sessions_page')
        );
        
        add_submenu_page(
            'wplcs',
            __('Tiers', 'wplcs'),
            __('Tiers', 'wplcs'),
            'manage_options',
            'wplcs-tiers',
            array($this, 'tiers_page')
        );
        
        add_submenu_page(
            'wplcs',
            __('Resources', 'wplcs'),
            __('Resources', 'wplcs'),
            'manage_options',
            'wplcs-resources',
            array($this, 'resources_page')
        );
        
        add_submenu_page(
            'wplcs',
            __('Logs', 'wplcs'),
            __('Logs', 'wplcs'),
            'manage_options',
            'wplcs-logs',
            array($this, 'logs_page')
        );
        
        add_submenu_page(
            'wplcs',
            __('Settings', 'wplcs'),
            __('Settings', 'wplcs'),
            'manage_options',
            'wplcs-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wplcs') === false) {
            return;
        }
        
        wp_enqueue_style('wplcs-admin', WPLCS_ASSETS_URL . 'css/admin.css', array(), WPLCS_VERSION);
        wp_enqueue_script('wplcs-admin', WPLCS_ASSETS_URL . 'js/admin.js', array('jquery', 'wp-util'), WPLCS_VERSION, true);
        
        wp_localize_script('wplcs-admin', 'wplcs_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wplcs_admin'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this?', 'wplcs'),
                'loading' => __('Loading...', 'wplcs'),
                'error' => __('An error occurred. Please try again.', 'wplcs'),
                'success' => __('Action completed successfully.', 'wplcs')
            )
        ));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wplcs_settings', 'wplcs_settings', array($this, 'sanitize_settings'));
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $tokens = new \WPLCS\Core\Tokens();
        $sessions = new \WPLCS\Core\Sessions();
        
        $token_stats = $tokens->get_token_stats();
        $session_stats = $sessions->get_session_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('WPLCS Dashboard', 'wplcs'); ?></h1>
            
            <div class="wplcs-admin-stats">
                <div class="wplcs-stat-boxes">
                    <div class="wplcs-stat-box">
                        <h3><?php echo intval($token_stats->total_tokens); ?></h3>
                        <p><?php _e('Total Tokens', 'wplcs'); ?></p>
                    </div>
                    <div class="wplcs-stat-box">
                        <h3><?php echo intval($token_stats->active_tokens); ?></h3>
                        <p><?php _e('Active Tokens', 'wplcs'); ?></p>
                    </div>
                    <div class="wplcs-stat-box">
                        <h3><?php echo intval($session_stats->active_sessions); ?></h3>
                        <p><?php _e('Active Sessions', 'wplcs'); ?></p>
                    </div>
                    <div class="wplcs-stat-box">
                        <h3><?php echo intval($session_stats->unique_domains); ?></h3>
                        <p><?php _e('Unique Domains', 'wplcs'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="wplcs-admin-widgets">
                <div class="wplcs-widget">
                    <h3><?php _e('Recent Activity', 'wplcs'); ?></h3>
                    <?php $this->render_recent_activity(); ?>
                </div>
                
                <div class="wplcs-widget">
                    <h3><?php _e('System Status', 'wplcs'); ?></h3>
                    <?php $this->render_system_status(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Tokens page
     */
    public function tokens_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        
        switch ($action) {
            case 'view':
                $this->render_token_details();
                break;
            default:
                $this->render_tokens_list();
                break;
        }
    }
    
    /**
     * Sessions page
     */
    public function sessions_page() {
        $this->render_sessions_list();
    }
    
    /**
     * Tiers page
     */
    public function tiers_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        
        switch ($action) {
            case 'edit':
            case 'new':
                $this->render_tier_form();
                break;
            default:
                $this->render_tiers_list();
                break;
        }
    }
    
    /**
     * Resources page
     */
    public function resources_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Resource Management', 'wplcs'); ?></h1>
            
            <div class="wplcs-resources-manager">
                <div class="wplcs-upload-section">
                    <h3><?php _e('Upload New Resource', 'wplcs'); ?></h3>
                    <form id="wplcs-upload-form" enctype="multipart/form-data">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Resource Name', 'wplcs'); ?></th>
                                <td><input type="text" name="resource_name" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Resource Type', 'wplcs'); ?></th>
                                <td>
                                    <select name="resource_type">
                                        <option value="file"><?php _e('File', 'wplcs'); ?></option>
                                        <option value="data"><?php _e('Data', 'wplcs'); ?></option>
                                        <option value="url"><?php _e('URL', 'wplcs'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('File', 'wplcs'); ?></th>
                                <td><input type="file" name="resource_file" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Assign to Tiers', 'wplcs'); ?></th>
                                <td>
                                    <?php
                                    $tokens = new \WPLCS\Core\Tokens();
                                    $tiers = $tokens->get_tiers();
                                    foreach ($tiers as $tier):
                                    ?>
                                        <label>
                                            <input type="checkbox" name="tiers[]" value="<?php echo $tier->id; ?>" />
                                            <?php echo esc_html($tier->name); ?>
                                        </label><br />
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="<?php _e('Upload Resource', 'wplcs'); ?>" />
                        </p>
                    </form>
                </div>
                
                <div class="wplcs-resources-list">
                    <h3><?php _e('Existing Resources', 'wplcs'); ?></h3>
                    <?php $this->render_resources_table(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Logs page
     */
    public function logs_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Activity Logs', 'wplcs'); ?></h1>
            
            <div class="wplcs-logs-filters">
                <form method="get">
                    <input type="hidden" name="page" value="wplcs-logs" />
                    
                    <select name="action_filter">
                        <option value=""><?php _e('All Actions', 'wplcs'); ?></option>
                        <option value="token_created"><?php _e('Token Created', 'wplcs'); ?></option>
                        <option value="session_created"><?php _e('Session Created', 'wplcs'); ?></option>
                        <option value="resource_access"><?php _e('Resource Access', 'wplcs'); ?></option>
                    </select>
                    
                    <input type="date" name="date_from" value="<?php echo isset($_GET['date_from']) ? esc_attr($_GET['date_from']) : ''; ?>" />
                    <input type="date" name="date_to" value="<?php echo isset($_GET['date_to']) ? esc_attr($_GET['date_to']) : ''; ?>" />
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'wplcs'); ?>" />
                </form>
            </div>
            
            <?php $this->render_logs_table(); ?>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $settings = get_option('wplcs_settings', array());
        
        ?>
        <div class="wrap">
            <h1><?php _e('WPLCS Settings', 'wplcs'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('wplcs_settings', 'wplcs_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Default Session Timeout (seconds)', 'wplcs'); ?></th>
                        <td>
                            <input type="number" name="wplcs_settings[session_timeout]" 
                                   value="<?php echo isset($settings['session_timeout']) ? $settings['session_timeout'] : WPLCS_SESSION_TIMEOUT; ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Rate Limit (requests per hour)', 'wplcs'); ?></th>
                        <td>
                            <input type="number" name="wplcs_settings[rate_limit]" 
                                   value="<?php echo isset($settings['rate_limit']) ? $settings['rate_limit'] : WPLCS_MAX_API_REQUESTS_PER_HOUR; ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Allowed Origins', 'wplcs'); ?></th>
                        <td>
                            <textarea name="wplcs_settings[allowed_origins]" rows="5" class="large-text"><?php 
                                echo isset($settings['allowed_origins']) ? esc_textarea(implode("\n", $settings['allowed_origins'])) : ''; 
                            ?></textarea>
                            <p class="description"><?php _e('One origin per line. Leave empty to allow any origin.', 'wplcs'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Enable Debug Mode', 'wplcs'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wplcs_settings[debug_mode]" value="1" 
                                       <?php checked(isset($settings['debug_mode']) && $settings['debug_mode']); ?> />
                                <?php _e('Enable detailed logging and debug information', 'wplcs'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render recent activity
     */
    private function render_recent_activity() {
        global $wpdb;
        $database = new \WPLCS\Core\Database();
        $logs_table = $database->get_table('logs');
        
        $recent_logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$logs_table} 
             ORDER BY created_at DESC 
             LIMIT %d",
            10
        ));
        
        if (empty($recent_logs)) {
            echo '<p>' . __('No recent activity.', 'wplcs') . '</p>';
            return;
        }
        
        echo '<ul class="wplcs-activity-list">';
        foreach ($recent_logs as $log) {
            echo '<li>';
            echo '<strong>' . esc_html(ucwords(str_replace('_', ' ', $log->action))) . '</strong> ';
            if ($log->resource) {
                echo '(' . esc_html($log->resource) . ') ';
            }
            echo '<span class="wplcs-activity-time">' . human_time_diff(strtotime($log->created_at), time()) . ' ' . __('ago', 'wplcs') . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Render system status
     */
    private function render_system_status() {
        $database = new \WPLCS\Core\Database();
        $tables_exist = $database->tables_exist();
        
        echo '<ul class="wplcs-status-list">';
        echo '<li><span class="wplcs-status-indicator ' . ($tables_exist ? 'green' : 'red') . '"></span> ' . __('Database Tables', 'wplcs') . '</li>';
        echo '<li><span class="wplcs-status-indicator ' . (class_exists('WooCommerce') ? 'green' : 'red') . '"></span> ' . __('WooCommerce', 'wplcs') . '</li>';
        echo '<li><span class="wplcs-status-indicator ' . (version_compare(PHP_VERSION, '7.4', '>=') ? 'green' : 'red') . '"></span> ' . __('PHP Version', 'wplcs') . ' (' . PHP_VERSION . ')</li>';
        echo '<li><span class="wplcs-status-indicator green"></span> ' . __('Plugin Version', 'wplcs') . ' (' . WPLCS_VERSION . ')</li>';
        echo '</ul>';
    }
    
    /**
     * Render tokens list
     */
    private function render_tokens_list() {
        ?>
        <div class="wrap">
            <h1><?php _e('Token Management', 'wplcs'); ?></h1>
            
            <div class="wplcs-search-box">
                <input type="search" id="wplcs-token-search" placeholder="<?php _e('Search tokens...', 'wplcs'); ?>" />
                <button type="button" class="button" id="wplcs-search-btn"><?php _e('Search', 'wplcs'); ?></button>
            </div>
            
            <div id="wplcs-tokens-table">
                <?php $this->render_tokens_table(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render tokens table
     */
    private function render_tokens_table() {
        global $wpdb;
        $database = new \WPLCS\Core\Database();
        $tokens_table = $database->get_table('tokens');
        $tiers_table = $database->get_table('tiers');
        
        $tokens = $wpdb->get_results(
            "SELECT t.*, tier.name as tier_name, u.user_login, u.display_name
             FROM {$tokens_table} t
             JOIN {$tiers_table} tier ON t.tier_id = tier.id
             JOIN {$wpdb->users} u ON t.user_id = u.ID
             ORDER BY t.created_at DESC
             LIMIT 50"
        );
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'wplcs'); ?></th>
                    <th><?php _e('User', 'wplcs'); ?></th>
                    <th><?php _e('Tier', 'wplcs'); ?></th>
                    <th><?php _e('Status', 'wplcs'); ?></th>
                    <th><?php _e('Nodes', 'wplcs'); ?></th>
                    <th><?php _e('Usage', 'wplcs'); ?></th>
                    <th><?php _e('Expires', 'wplcs'); ?></th>
                    <th><?php _e('Actions', 'wplcs'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tokens as $token): ?>
                    <tr>
                        <td><?php echo $token->id; ?></td>
                        <td>
                            <strong><?php echo esc_html($token->display_name); ?></strong><br />
                            <small><?php echo esc_html($token->user_login); ?></small>
                        </td>
                        <td><?php echo esc_html($token->tier_name); ?></td>
                        <td>
                            <?php if ($token->is_active && strtotime($token->expires_at) > time()): ?>
                                <span class="wplcs-status active"><?php _e('Active', 'wplcs'); ?></span>
                            <?php elseif (strtotime($token->expires_at) <= time()): ?>
                                <span class="wplcs-status expired"><?php _e('Expired', 'wplcs'); ?></span>
                            <?php else: ?>
                                <span class="wplcs-status inactive"><?php _e('Inactive', 'wplcs'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $token->current_nodes; ?> / 
                            <?php echo $token->max_nodes == -1 ? __('∞', 'wplcs') : $token->max_nodes; ?>
                        </td>
                        <td><?php echo intval($token->usage_count); ?></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($token->expires_at)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=wplcs-tokens&action=view&id=' . $token->id); ?>" 
                               class="button button-small"><?php _e('View', 'wplcs'); ?></a>
                            <button type="button" class="button button-small wplcs-toggle-token" 
                                    data-token-id="<?php echo $token->id; ?>"
                                    data-active="<?php echo $token->is_active; ?>">
                                <?php echo $token->is_active ? __('Deactivate', 'wplcs') : __('Activate', 'wplcs'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['wplcs_settings_nonce'], 'wplcs_settings')) {
            return;
        }
        
        $settings = $_POST['wplcs_settings'];
        
        // Process allowed origins
        if (isset($settings['allowed_origins'])) {
            $origins = explode("\n", $settings['allowed_origins']);
            $settings['allowed_origins'] = array_filter(array_map('trim', $origins));
        }
        
        update_option('wplcs_settings', $settings);
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'wplcs') . '</p></div>';
    }
    
    /**
     * AJAX: Search tokens
     */
    public function ajax_search_tokens() {
        check_ajax_referer('wplcs_admin', 'nonce');
        
        $search = sanitize_text_field($_POST['search']);
        // Implementation for token search
        
        ob_start();
        $this->render_tokens_table();
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX: Toggle token status
     */
    public function ajax_toggle_token() {
        check_ajax_referer('wplcs_admin', 'nonce');
        
        $token_id = intval($_POST['token_id']);
        $active = intval($_POST['active']);
        
        global $wpdb;
        $database = new \WPLCS\Core\Database();
        $tokens_table = $database->get_table('tokens');
        
        $result = $wpdb->update(
            $tokens_table,
            array('is_active' => $active ? 0 : 1),
            array('id' => $token_id)
        );
        
        if ($result !== false) {
            wp_send_json_success(__('Token status updated.', 'wplcs'));
        } else {
            wp_send_json_error(__('Failed to update token status.', 'wplcs'));
        }
    }
}