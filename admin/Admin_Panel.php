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
        
        // Enqueue modern theme system
        wp_enqueue_style('wplcs-theme', WPLCS_ASSETS_URL . 'css/wplcs-theme.css', array(), WPLCS_VERSION);
        wp_enqueue_style('wplcs-admin', WPLCS_ASSETS_URL . 'css/admin.css', array('wplcs-theme'), WPLCS_VERSION);
        wp_enqueue_style('wplcs-dashboard', WPLCS_ASSETS_URL . 'css/dashboard.css', array('wplcs-theme'), WPLCS_VERSION);
        
        wp_enqueue_script('wplcs-theme', WPLCS_ASSETS_URL . 'js/wplcs-theme.js', array('jquery'), WPLCS_VERSION, true);
        wp_enqueue_script('wplcs-admin', WPLCS_ASSETS_URL . 'js/admin.js', array('jquery', 'wp-util', 'wplcs-theme'), WPLCS_VERSION, true);
        
        wp_localize_script('wplcs-admin', 'wplcs_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wplcs_admin'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this?', 'wplcs'),
                'loading' => __('Loading...', 'wplcs'),
                'error' => __('An error occurred. Please try again.', 'wplcs'),
                'success' => __('Action completed successfully.', 'wplcs'),
                'theme_switched' => __('Theme switched successfully', 'wplcs')
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
        <div class="wrap wplcs-dashboard-modern wplcs-theme-container">
            <div class="wplcs-dashboard-header">
                <div>
                    <h1 class="wplcs-dashboard-title"><?php _e('WPLCS Dashboard', 'wplcs'); ?></h1>
                    <p class="wplcs-dashboard-subtitle"><?php _e('WordPress License Control System', 'wplcs'); ?></p>
                </div>
                <div class="wplcs-dashboard-actions">
                    <button class="wplcs-btn wplcs-btn-secondary" id="wplcs-refresh-dashboard" data-tooltip="<?php _e('Refresh Data', 'wplcs'); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="m3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                        <?php _e('Refresh', 'wplcs'); ?>
                    </button>
                </div>
            </div>
            
            <div class="wplcs-stats-grid-modern">
                <div class="wplcs-stat-card-modern wplcs-fade-in">
                    <div class="wplcs-stat-header">
                        <span class="wplcs-stat-label"><?php _e('Total Tokens', 'wplcs'); ?></span>
                        <svg class="wplcs-stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21,15 16,10 5,21"></polyline>
                        </svg>
                    </div>
                    <div class="wplcs-stat-value"><?php echo number_format(intval($token_stats->total_tokens)); ?></div>
                    <div class="wplcs-stat-change positive">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        </svg>
                        <?php _e('All time', 'wplcs'); ?>
                    </div>
                </div>
                
                <div class="wplcs-stat-card-modern wplcs-fade-in">
                    <div class="wplcs-stat-header">
                        <span class="wplcs-stat-label"><?php _e('Active Tokens', 'wplcs'); ?></span>
                        <svg class="wplcs-stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M12 1v6m0 6v6"></path>
                            <path d="m9 9 3-3 3 3"></path>
                            <path d="m9 15 3 3 3-3"></path>
                        </svg>
                    </div>
                    <div class="wplcs-stat-value"><?php echo number_format(intval($token_stats->active_tokens)); ?></div>
                    <div class="wplcs-stat-change positive">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        </svg>
                        <?php echo round(($token_stats->active_tokens / max($token_stats->total_tokens, 1)) * 100, 1); ?>% <?php _e('of total', 'wplcs'); ?>
                    </div>
                </div>
                
                <div class="wplcs-stat-card-modern wplcs-fade-in">
                    <div class="wplcs-stat-header">
                        <span class="wplcs-stat-label"><?php _e('Active Sessions', 'wplcs'); ?></span>
                        <svg class="wplcs-stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="wplcs-stat-value"><?php echo number_format(intval($session_stats->active_sessions)); ?></div>
                    <div class="wplcs-stat-change positive">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12,6 12,12 16,14"></polyline>
                        </svg>
                        <?php _e('Currently online', 'wplcs'); ?>
                    </div>
                </div>
                
                <div class="wplcs-stat-card-modern wplcs-fade-in">
                    <div class="wplcs-stat-header">
                        <span class="wplcs-stat-label"><?php _e('Unique Domains', 'wplcs'); ?></span>
                        <svg class="wplcs-stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                    </div>
                    <div class="wplcs-stat-value"><?php echo number_format(intval($session_stats->unique_domains)); ?></div>
                    <div class="wplcs-stat-change positive">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                        </svg>
                        <?php _e('Registered', 'wplcs'); ?>
                    </div>
                </div>
            </div>
            
            <div class="wplcs-nav-tabs">
                <button class="wplcs-nav-tab active" data-tab="recent-activity"><?php _e('Recent Activity', 'wplcs'); ?></button>
                <button class="wplcs-nav-tab" data-tab="system-status"><?php _e('System Status', 'wplcs'); ?></button>
                <button class="wplcs-nav-tab" data-tab="quick-stats"><?php _e('Quick Stats', 'wplcs'); ?></button>
            </div>
            
            <div class="wplcs-tab-content">
                <div class="wplcs-tab-panel active" id="recent-activity">
                    <div class="wplcs-card">
                        <div class="wplcs-card-header">
                            <h3 class="wplcs-card-title"><?php _e('Recent Activity', 'wplcs'); ?></h3>
                            <span class="wplcs-badge wplcs-badge-info">
                                <span class="wplcs-badge-dot"></span>
                                <?php _e('Live', 'wplcs'); ?>
                            </span>
                        </div>
                        <?php $this->render_recent_activity(); ?>
                    </div>
                </div>
                
                <div class="wplcs-tab-panel" id="system-status">
                    <div class="wplcs-card">
                        <div class="wplcs-card-header">
                            <h3 class="wplcs-card-title"><?php _e('System Status', 'wplcs'); ?></h3>
                            <span class="wplcs-badge wplcs-badge-success">
                                <span class="wplcs-badge-dot"></span>
                                <?php _e('Healthy', 'wplcs'); ?>
                            </span>
                        </div>
                        <?php $this->render_system_status(); ?>
                    </div>
                </div>
                
                <div class="wplcs-tab-panel" id="quick-stats">
                    <div class="wplcs-card">
                        <div class="wplcs-card-header">
                            <h3 class="wplcs-card-title"><?php _e('Quick Statistics', 'wplcs'); ?></h3>
                        </div>
                        <?php $this->render_quick_stats(); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab functionality
            $('.wplcs-nav-tab').on('click', function() {
                const tabId = $(this).data('tab');
                
                $('.wplcs-nav-tab').removeClass('active');
                $('.wplcs-tab-panel').removeClass('active');
                
                $(this).addClass('active');
                $('#' + tabId).addClass('active');
            });
            
            // Refresh functionality
            $('#wplcs-refresh-dashboard').on('click', function() {
                const $btn = $(this);
                $btn.addClass('wplcs-loading');
                
                setTimeout(() => {
                    location.reload();
                }, 1000);
            });
        });
        </script>
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
        
        echo '<div class="wplcs-status-grid">';
        
        // Database Status
        echo '<div class="wplcs-status-item">';
        echo '<div class="wplcs-status-header">';
        echo '<span class="wplcs-badge ' . ($tables_exist ? 'wplcs-badge-success' : 'wplcs-badge-error') . '">';
        echo '<span class="wplcs-badge-dot"></span>';
        echo ($tables_exist ? __('Online', 'wplcs') : __('Offline', 'wplcs'));
        echo '</span>';
        echo '<span class="wplcs-status-label">' . __('Database Tables', 'wplcs') . '</span>';
        echo '</div>';
        echo '</div>';
        
        // WooCommerce Status
        echo '<div class="wplcs-status-item">';
        echo '<div class="wplcs-status-header">';
        echo '<span class="wplcs-badge ' . (class_exists('WooCommerce') ? 'wplcs-badge-success' : 'wplcs-badge-error') . '">';
        echo '<span class="wplcs-badge-dot"></span>';
        echo (class_exists('WooCommerce') ? __('Active', 'wplcs') : __('Missing', 'wplcs'));
        echo '</span>';
        echo '<span class="wplcs-status-label">' . __('WooCommerce', 'wplcs') . '</span>';
        echo '</div>';
        echo '</div>';
        
        // PHP Version Status
        echo '<div class="wplcs-status-item">';
        echo '<div class="wplcs-status-header">';
        echo '<span class="wplcs-badge ' . (version_compare(PHP_VERSION, '7.4', '>=') ? 'wplcs-badge-success' : 'wplcs-badge-warning') . '">';
        echo '<span class="wplcs-badge-dot"></span>';
        echo PHP_VERSION;
        echo '</span>';
        echo '<span class="wplcs-status-label">' . __('PHP Version', 'wplcs') . '</span>';
        echo '</div>';
        echo '</div>';
        
        // Plugin Version
        echo '<div class="wplcs-status-item">';
        echo '<div class="wplcs-status-header">';
        echo '<span class="wplcs-badge wplcs-badge-info">';
        echo '<span class="wplcs-badge-dot"></span>';
        echo WPLCS_VERSION;
        echo '</span>';
        echo '<span class="wplcs-status-label">' . __('Plugin Version', 'wplcs') . '</span>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render quick stats
     */
    private function render_quick_stats() {
        global $wpdb;
        $database = new \WPLCS\Core\Database();
        
        // Get additional statistics
        $tokens_table = $database->get_table('tokens');
        $sessions_table = $database->get_table('sessions');
        $logs_table = $database->get_table('logs');
        
        // Expired tokens count
        $expired_tokens = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tokens_table} WHERE expires_at < %s",
            current_time('mysql')
        ));
        
        // Sessions today
        $sessions_today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$sessions_table} WHERE DATE(created_at) = %s",
            current_time('Y-m-d')
        ));
        
        // Recent activities count
        $recent_activities = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$logs_table} WHERE created_at >= %s",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ));
        
        echo '<div class="wplcs-quick-stats-grid">';
        
        echo '<div class="wplcs-quick-stat">';
        echo '<div class="wplcs-quick-stat-number">' . number_format($expired_tokens) . '</div>';
        echo '<div class="wplcs-quick-stat-label">' . __('Expired Tokens', 'wplcs') . '</div>';
        echo '</div>';
        
        echo '<div class="wplcs-quick-stat">';
        echo '<div class="wplcs-quick-stat-number">' . number_format($sessions_today) . '</div>';
        echo '<div class="wplcs-quick-stat-label">' . __('Sessions Today', 'wplcs') . '</div>';
        echo '</div>';
        
        echo '<div class="wplcs-quick-stat">';
        echo '<div class="wplcs-quick-stat-number">' . number_format($recent_activities) . '</div>';
        echo '<div class="wplcs-quick-stat-label">' . __('Activities (24h)', 'wplcs') . '</div>';
        echo '</div>';
        
        echo '<div class="wplcs-quick-stat">';
        echo '<div class="wplcs-quick-stat-number">' . number_format(get_option('wplcs_total_requests', 0)) . '</div>';
        echo '<div class="wplcs-quick-stat-label">' . __('Total API Requests', 'wplcs') . '</div>';
        echo '</div>';
        
        echo '</div>';
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
    
    /**
     * Render sessions list
     */
    private function render_sessions_list() {
        global $wpdb;
        $database = new \WPLCS\Core\Database();
        $sessions_table = $database->get_table('sessions');
        $tokens_table = $database->get_table('tokens');
        $tiers_table = $database->get_table('tiers');
        
        // Get filter values
        $user_filter = isset($_GET['user_filter']) ? sanitize_text_field($_GET['user_filter']) : '';
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        
        // Build query
        $where_clauses = array('1=1');
        $params = array();
        
        if ($user_filter) {
            $where_clauses[] = "(u.user_login LIKE %s OR u.display_name LIKE %s)";
            $params[] = '%' . $user_filter . '%';
            $params[] = '%' . $user_filter . '%';
        }
        
        if ($status_filter === 'active') {
            $where_clauses[] = "s.is_active = 1 AND s.expires_at > %s";
            $params[] = current_time('mysql');
        } elseif ($status_filter === 'expired') {
            $where_clauses[] = "s.expires_at <= %s";
            $params[] = current_time('mysql');
        }
        
        $where_clause = implode(' AND ', $where_clauses);
        
        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, t.user_id, tier.name as tier_name, u.user_login, u.display_name
             FROM {$sessions_table} s
             JOIN {$tokens_table} t ON s.token_id = t.id
             JOIN {$tiers_table} tier ON t.tier_id = tier.id
             JOIN {$wpdb->users} u ON t.user_id = u.ID
             WHERE {$where_clause}
             ORDER BY s.last_activity DESC
             LIMIT 100",
            $params
        ));
        
        ?>
        <div class="wrap">
            <h1><?php _e('Session Management', 'wplcs'); ?></h1>
            
            <!-- Filters -->
            <div class="wplcs-filters">
                <form method="get">
                    <input type="hidden" name="page" value="wplcs-sessions" />
                    
                    <input type="search" name="user_filter" placeholder="<?php _e('Search users...', 'wplcs'); ?>" 
                           value="<?php echo esc_attr($user_filter); ?>" />
                    
                    <select name="status_filter">
                        <option value=""><?php _e('All Sessions', 'wplcs'); ?></option>
                        <option value="active" <?php selected($status_filter, 'active'); ?>><?php _e('Active', 'wplcs'); ?></option>
                        <option value="expired" <?php selected($status_filter, 'expired'); ?>><?php _e('Expired', 'wplcs'); ?></option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'wplcs'); ?>" />
                    <?php if ($user_filter || $status_filter): ?>
                        <a href="<?php echo admin_url('admin.php?page=wplcs-sessions'); ?>" class="button"><?php _e('Clear', 'wplcs'); ?></a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Sessions Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('User', 'wplcs'); ?></th>
                        <th><?php _e('Domain', 'wplcs'); ?></th>
                        <th><?php _e('Tier', 'wplcs'); ?></th>
                        <th><?php _e('IP Address', 'wplcs'); ?></th>
                        <th><?php _e('Status', 'wplcs'); ?></th>
                        <th><?php _e('Last Activity', 'wplcs'); ?></th>
                        <th><?php _e('Expires', 'wplcs'); ?></th>
                        <th><?php _e('Actions', 'wplcs'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="8"><?php _e('No sessions found.', 'wplcs'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($session->display_name); ?></strong><br />
                                    <small><?php echo esc_html($session->user_login); ?></small>
                                </td>
                                <td><?php echo esc_html($session->node_domain); ?></td>
                                <td><?php echo esc_html($session->tier_name); ?></td>
                                <td><?php echo esc_html($session->node_ip); ?></td>
                                <td>
                                    <?php if ($session->is_active && strtotime($session->expires_at) > time()): ?>
                                        <span class="wplcs-status active"><?php _e('Active', 'wplcs'); ?></span>
                                    <?php else: ?>
                                        <span class="wplcs-status expired"><?php _e('Expired', 'wplcs'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo human_time_diff(strtotime($session->last_activity), time()) . ' ' . __('ago', 'wplcs'); ?></td>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($session->expires_at)); ?></td>
                                <td>
                                    <?php if ($session->is_active): ?>
                                        <button type="button" class="button button-small button-danger wplcs-deactivate-session" 
                                                data-session-id="<?php echo $session->id; ?>">
                                            <?php _e('Deactivate', 'wplcs'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render tiers list
     */
    private function render_tiers_list() {
        global $wpdb;
        $database = new \WPLCS\Core\Database();
        $tiers_table = $database->get_table('tiers');
        
        $tiers = $wpdb->get_results("SELECT * FROM {$tiers_table} ORDER BY sort_order ASC");
        
        ?>
        <div class="wrap">
            <h1>
                <?php _e('License Tiers', 'wplcs'); ?>
                <a href="<?php echo admin_url('admin.php?page=wplcs-tiers&action=new'); ?>" class="page-title-action">
                    <?php _e('Add New Tier', 'wplcs'); ?>
                </a>
            </h1>
            
            <div class="wplcs-tiers-grid">
                <?php foreach ($tiers as $tier): ?>
                    <div class="wplcs-tier-card <?php echo $tier->is_active ? 'active' : 'inactive'; ?>">
                        <div class="wplcs-tier-header">
                            <h3><?php echo esc_html($tier->name); ?></h3>
                            <span class="wplcs-tier-status <?php echo $tier->is_active ? 'active' : 'inactive'; ?>">
                                <?php echo $tier->is_active ? __('Active', 'wplcs') : __('Inactive', 'wplcs'); ?>
                            </span>
                        </div>
                        
                        <div class="wplcs-tier-details">
                            <div class="wplcs-tier-price">
                                <?php echo $tier->price > 0 ? wc_price($tier->price) : __('Free', 'wplcs'); ?>
                            </div>
                            
                            <ul class="wplcs-tier-features">
                                <li><strong><?php _e('Max Sites:', 'wplcs'); ?></strong> 
                                    <?php echo $tier->max_nodes == -1 ? __('Unlimited', 'wplcs') : $tier->max_nodes; ?>
                                </li>
                                <li><strong><?php _e('Duration:', 'wplcs'); ?></strong> 
                                    <?php echo human_time_diff(0, $tier->duration); ?>
                                </li>
                                <li><strong><?php _e('Trial:', 'wplcs'); ?></strong> 
                                    <?php echo $tier->is_trial ? __('Yes', 'wplcs') : __('No', 'wplcs'); ?>
                                </li>
                                <?php
                                $features = json_decode($tier->features, true);
                                if ($features && is_array($features)):
                                    foreach ($features as $feature):
                                ?>
                                    <li><?php echo esc_html(ucwords(str_replace('_', ' ', $feature))); ?></li>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </ul>
                        </div>
                        
                        <div class="wplcs-tier-actions">
                            <a href="<?php echo admin_url('admin.php?page=wplcs-tiers&action=edit&id=' . $tier->id); ?>" 
                               class="button button-primary"><?php _e('Edit', 'wplcs'); ?></a>
                            
                            <button type="button" class="button wplcs-toggle-tier" 
                                    data-tier-id="<?php echo $tier->id; ?>"
                                    data-active="<?php echo $tier->is_active; ?>">
                                <?php echo $tier->is_active ? __('Deactivate', 'wplcs') : __('Activate', 'wplcs'); ?>
                            </button>
                            
                            <button type="button" class="button button-danger wplcs-delete-tier" 
                                    data-tier-id="<?php echo $tier->id; ?>">
                                <?php _e('Delete', 'wplcs'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .wplcs-tiers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .wplcs-tier-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            position: relative;
        }
        
        .wplcs-tier-card.active {
            border-left: 4px solid #00a32a;
        }
        
        .wplcs-tier-card.inactive {
            opacity: 0.7;
            border-left: 4px solid #ddd;
        }
        
        .wplcs-tier-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .wplcs-tier-header h3 {
            margin: 0;
        }
        
        .wplcs-tier-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .wplcs-tier-status.active {
            background: #d4edda;
            color: #155724;
        }
        
        .wplcs-tier-status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .wplcs-tier-price {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 15px;
        }
        
        .wplcs-tier-features {
            list-style: none;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .wplcs-tier-features li {
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .wplcs-tier-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .wplcs-tier-actions .button {
            flex: 1;
            min-width: 80px;
        }
        </style>
        <?php
    }
    
    /**
     * Render resources table
     */
    private function render_resources_table() {
        $resources = get_option('wplcs_resources', array());
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Name', 'wplcs'); ?></th>
                    <th><?php _e('Type', 'wplcs'); ?></th>
                    <th><?php _e('Size', 'wplcs'); ?></th>
                    <th><?php _e('Version', 'wplcs'); ?></th>
                    <th><?php _e('Assigned Tiers', 'wplcs'); ?></th>
                    <th><?php _e('Actions', 'wplcs'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($resources)): ?>
                    <tr>
                        <td colspan="6"><?php _e('No resources found. Upload some resources to get started.', 'wplcs'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($resources as $resource_id => $resource): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($resource['name']); ?></strong>
                                <?php if (!empty($resource['description'])): ?>
                                    <br><small><?php echo esc_html($resource['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="wplcs-resource-type"><?php echo esc_html(ucfirst($resource['type'])); ?></span>
                            </td>
                            <td>
                                <?php 
                                if (isset($resource['size']) && $resource['size'] > 0) {
                                    echo size_format($resource['size']);
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td><?php echo isset($resource['version']) ? esc_html($resource['version']) : '1.0.0'; ?></td>
                            <td>
                                <?php
                                $assigned_tiers = array();
                                $tokens = new \WPLCS\Core\Tokens();
                                $all_tiers = $tokens->get_tiers();
                                
                                foreach ($all_tiers as $tier) {
                                    $tier_resources = get_option('wplcs_tier_resources_' . $tier->id, array());
                                    foreach ($tier_resources as $tier_resource) {
                                        if ($tier_resource['id'] === $resource_id) {
                                            $assigned_tiers[] = $tier->name;
                                            break;
                                        }
                                    }
                                }
                                
                                echo !empty($assigned_tiers) ? implode(', ', $assigned_tiers) : __('None', 'wplcs');
                                ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small wplcs-edit-resource" 
                                        data-resource-id="<?php echo esc_attr($resource_id); ?>">
                                    <?php _e('Edit', 'wplcs'); ?>
                                </button>
                                
                                <button type="button" class="button button-small button-danger wplcs-delete-resource" 
                                        data-resource-id="<?php echo esc_attr($resource_id); ?>">
                                    <?php _e('Delete', 'wplcs'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <style>
        .wplcs-resource-type {
            display: inline-block;
            padding: 2px 8px;
            background: #f0f0f0;
            border-radius: 3px;
            font-size: 12px;
        }
        </style>
        <?php
    }
    
    /**
     * Render logs table
     */
    private function render_logs_table() {
        global $wpdb;
        $database = new \WPLCS\Core\Database();
        $logs_table = $database->get_table('logs');
        
        // Get filter values
        $action_filter = isset($_GET['action_filter']) ? sanitize_text_field($_GET['action_filter']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        
        // Build query
        $where_clauses = array('1=1');
        $params = array();
        
        if ($action_filter) {
            $where_clauses[] = "action = %s";
            $params[] = $action_filter;
        }
        
        if ($date_from) {
            $where_clauses[] = "DATE(created_at) >= %s";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $where_clauses[] = "DATE(created_at) <= %s";
            $params[] = $date_to;
        }
        
        $where_clause = implode(' AND ', $where_clauses);
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.user_login, u.display_name
             FROM {$logs_table} l
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             WHERE {$where_clause}
             ORDER BY l.created_at DESC
             LIMIT 200",
            $params
        ));
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date/Time', 'wplcs'); ?></th>
                    <th><?php _e('User', 'wplcs'); ?></th>
                    <th><?php _e('Action', 'wplcs'); ?></th>
                    <th><?php _e('Resource', 'wplcs'); ?></th>
                    <th><?php _e('IP Address', 'wplcs'); ?></th>
                    <th><?php _e('Response', 'wplcs'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6"><?php _e('No logs found.', 'wplcs'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at)); ?></td>
                            <td>
                                <?php if ($log->user_login): ?>
                                    <strong><?php echo esc_html($log->display_name); ?></strong><br />
                                    <small><?php echo esc_html($log->user_login); ?></small>
                                <?php else: ?>
                                    <em><?php _e('Unknown', 'wplcs'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="wplcs-action-<?php echo esc_attr(str_replace('_', '-', $log->action)); ?>">
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $log->action))); ?>
                                </span>
                            </td>
                            <td><?php echo $log->resource ? esc_html($log->resource) : '—'; ?></td>
                            <td><?php echo esc_html($log->ip_address); ?></td>
                            <td>
                                <?php if ($log->response_code): ?>
                                    <span class="wplcs-response-code response-<?php echo intval($log->response_code); ?>">
                                        <?php echo intval($log->response_code); ?>
                                    </span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                                
                                <?php if ($log->error_message): ?>
                                    <br><small class="wplcs-error"><?php echo esc_html($log->error_message); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <style>
        .wplcs-response-code {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .response-200 { background: #d4edda; color: #155724; }
        .response-400 { background: #fff3cd; color: #856404; }
        .response-401, .response-403 { background: #f8d7da; color: #721c24; }
        .response-404 { background: #e2e3e5; color: #383d41; }
        .response-429 { background: #d1ecf1; color: #0c5460; }
        .response-500 { background: #f8d7da; color: #721c24; }
        
        .wplcs-error {
            color: #dc3545;
            font-style: italic;
        }
        
        .wplcs-action-token-created { color: #28a745; }
        .wplcs-action-session-created { color: #007bff; }
        .wplcs-action-resource-access { color: #6f42c1; }
        .wplcs-action-token-deactivated { color: #dc3545; }
        .wplcs-action-session-deactivated { color: #fd7e14; }
        </style>
        <?php
    }
}