<?php
namespace WPLCS\Admin;

use WPLCS\Core\Database;
use WPLCS\Core\Tokens;
use WPLCS\Core\Sessions;

/**
 * Admin Users Manager
 */
class Admin_Users {
    
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Database();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_wplcs_deactivate_user_token', array($this, 'ajax_deactivate_user_token'));
        add_action('wp_ajax_wplcs_reset_user_sessions', array($this, 'ajax_reset_user_sessions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wplcs-dashboard',
            __('Users', 'wplcs'),
            __('Users', 'wplcs'),
            'manage_options',
            'wplcs-users',
            array($this, 'users_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wplcs-users') !== false) {
            wp_enqueue_script('wplcs-users-admin', WPLCS_PLUGIN_URL . 'assets/admin-users.js', array('jquery'), WPLCS_VERSION, true);
            wp_localize_script('wplcs-users-admin', 'wplcs_users_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wplcs_users_nonce'),
                'i18n' => array(
                    'confirm_deactivate' => __('Are you sure you want to deactivate this user\'s token?', 'wplcs'),
                    'confirm_reset' => __('Are you sure you want to reset all sessions for this user?', 'wplcs')
                )
            ));
        }
    }
    
    /**
     * Users page
     */
    public function users_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        switch ($action) {
            case 'view':
                $this->render_user_details($user_id);
                break;
            default:
                $this->render_users_list();
                break;
        }
    }
    
    /**
     * Render users list
     */
    private function render_users_list() {
        global $wpdb;
        
        // Get pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Get search term
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Build query
        $tokens_table = $this->database->get_table('tokens');
        $sessions_table = $this->database->get_table('sessions');
        $tiers_table = $this->database->get_table('tiers');
        
        $where_clause = "1=1";
        $search_params = array();
        
        if ($search) {
            $where_clause .= " AND (u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)";
            $search_term = '%' . $search . '%';
            $search_params = array($search_term, $search_term, $search_term);
        }
        
        // Get users with tokens
        $users_query = "
            SELECT DISTINCT u.ID, u.user_login, u.user_email, u.display_name, u.user_registered,
                   COUNT(DISTINCT t.id) as token_count,
                   COUNT(DISTINCT CASE WHEN t.is_active = 1 AND t.expires_at > NOW() THEN t.id END) as active_tokens,
                   COUNT(DISTINCT s.id) as session_count,
                   COUNT(DISTINCT CASE WHEN s.is_active = 1 AND s.expires_at > NOW() THEN s.id END) as active_sessions,
                   MAX(s.last_activity) as last_activity,
                   GROUP_CONCAT(DISTINCT tier.name SEPARATOR ', ') as tiers
            FROM {$wpdb->users} u
            LEFT JOIN {$tokens_table} t ON u.ID = t.user_id
            LEFT JOIN {$sessions_table} s ON t.id = s.token_id
            LEFT JOIN {$tiers_table} tier ON t.tier_id = tier.id
            WHERE {$where_clause}
            GROUP BY u.ID
            HAVING token_count > 0
            ORDER BY last_activity DESC, u.user_registered DESC
            LIMIT %d OFFSET %d
        ";
        
        $params = array_merge($search_params, array($per_page, $offset));
        $users = $wpdb->get_results($wpdb->prepare($users_query, $params));
        
        // Get total count for pagination
        $total_query = "
            SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            JOIN {$tokens_table} t ON u.ID = t.user_id
            WHERE {$where_clause}
        ";
        
        $total_users = $wpdb->get_var($wpdb->prepare($total_query, $search_params));
        $total_pages = ceil($total_users / $per_page);
        
        ?>
        <div class="wrap">
            <h1>
                <?php _e('WPLCS Users', 'wplcs'); ?>
                <span class="subtitle"><?php echo sprintf(__('(%d users with licenses)', 'wplcs'), $total_users); ?></span>
            </h1>
            
            <!-- Search Form -->
            <div class="wplcs-search-form">
                <form method="get" action="">
                    <input type="hidden" name="page" value="wplcs-users" />
                    <p class="search-box">
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                               placeholder="<?php _e('Search users...', 'wplcs'); ?>" />
                        <input type="submit" class="button" value="<?php _e('Search', 'wplcs'); ?>" />
                        <?php if ($search): ?>
                            <a href="<?php echo admin_url('admin.php?page=wplcs-users'); ?>" class="button">
                                <?php _e('Clear', 'wplcs'); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            <!-- Users Table -->
            <table class="wp-list-table widefat fixed striped wplcs-users-table">
                <thead>
                    <tr>
                        <th class="column-user"><?php _e('User', 'wplcs'); ?></th>
                        <th class="column-tokens"><?php _e('Tokens', 'wplcs'); ?></th>
                        <th class="column-sessions"><?php _e('Sessions', 'wplcs'); ?></th>
                        <th class="column-tiers"><?php _e('Tiers', 'wplcs'); ?></th>
                        <th class="column-activity"><?php _e('Last Activity', 'wplcs'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'wplcs'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="no-items">
                                <?php _e('No users found with licenses.', 'wplcs'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="column-user">
                                    <div class="user-info">
                                        <strong class="user-name">
                                            <a href="<?php echo admin_url('admin.php?page=wplcs-users&action=view&user_id=' . $user->ID); ?>">
                                                <?php echo esc_html($user->display_name); ?>
                                            </a>
                                        </strong>
                                        <div class="user-meta">
                                            <div class="user-login"><?php echo esc_html($user->user_login); ?></div>
                                            <div class="user-email">
                                                <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                                    <?php echo esc_html($user->user_email); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="column-tokens">
                                    <div class="token-stats">
                                        <span class="total-tokens"><?php echo intval($user->token_count); ?></span>
                                        <span class="active-tokens">(<?php echo intval($user->active_tokens); ?> <?php _e('active', 'wplcs'); ?>)</span>
                                    </div>
                                </td>
                                <td class="column-sessions">
                                    <div class="session-stats">
                                        <span class="total-sessions"><?php echo intval($user->session_count); ?></span>
                                        <span class="active-sessions">(<?php echo intval($user->active_sessions); ?> <?php _e('active', 'wplcs'); ?>)</span>
                                    </div>
                                </td>
                                <td class="column-tiers">
                                    <div class="user-tiers">
                                        <?php echo $user->tiers ? esc_html($user->tiers) : '<em>' . __('None', 'wplcs') . '</em>'; ?>
                                    </div>
                                </td>
                                <td class="column-activity">
                                    <?php if ($user->last_activity): ?>
                                        <time datetime="<?php echo esc_attr($user->last_activity); ?>">
                                            <?php echo human_time_diff(strtotime($user->last_activity), time()) . ' ' . __('ago', 'wplcs'); ?>
                                        </time>
                                    <?php else: ?>
                                        <em><?php _e('Never', 'wplcs'); ?></em>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <div class="row-actions">
                                        <a href="<?php echo admin_url('admin.php?page=wplcs-users&action=view&user_id=' . $user->ID); ?>" 
                                           class="button button-small">
                                            <?php _e('View', 'wplcs'); ?>
                                        </a>
                                        
                                        <?php if ($user->active_sessions > 0): ?>
                                            <button type="button" class="button button-small wplcs-reset-sessions" 
                                                    data-user-id="<?php echo $user->ID; ?>">
                                                <?php _e('Reset Sessions', 'wplcs'); ?>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo get_edit_user_link($user->ID); ?>" class="button button-small">
                                            <?php _e('Edit User', 'wplcs'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $page_links = paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page,
                            'type' => 'array'
                        ));
                        
                        if ($page_links) {
                            echo '<span class="pagination-links">' . implode('', $page_links) . '</span>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .wplcs-users-table .column-user { width: 25%; }
        .wplcs-users-table .column-tokens { width: 10%; }
        .wplcs-users-table .column-sessions { width: 10%; }
        .wplcs-users-table .column-tiers { width: 20%; }
        .wplcs-users-table .column-activity { width: 15%; }
        .wplcs-users-table .column-actions { width: 20%; }
        
        .user-info .user-name { font-size: 14px; }
        .user-meta { margin-top: 5px; }
        .user-meta div { font-size: 13px; color: #666; }
        .user-email a { text-decoration: none; }
        
        .token-stats, .session-stats {
            font-weight: 600;
        }
        
        .active-tokens, .active-sessions {
            font-size: 12px;
            color: #666;
            font-weight: normal;
        }
        
        .row-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .row-actions .button {
            min-width: auto;
            padding: 4px 8px;
        }
        
        .wplcs-search-form {
            margin: 20px 0;
        }
        
        .wplcs-search-form .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .wplcs-search-form input[type="search"] {
            width: 300px;
        }
        </style>
        <?php
    }
    
    /**
     * Render user details
     */
    private function render_user_details($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_die(__('User not found.', 'wplcs'));
        }
        
        $tokens = new Tokens();
        $sessions = new Sessions();
        
        $user_tokens = $tokens->get_user_tokens($user_id);
        $user_sessions = $sessions->get_user_sessions($user_id);
        
        ?>
        <div class="wrap">
            <h1>
                <?php echo sprintf(__('User: %s', 'wplcs'), esc_html($user->display_name)); ?>
                <a href="<?php echo admin_url('admin.php?page=wplcs-users'); ?>" class="page-title-action">
                    <?php _e('Back to Users', 'wplcs'); ?>
                </a>
            </h1>
            
            <div class="wplcs-user-details">
                <!-- User Info Card -->
                <div class="wplcs-user-info-card">
                    <h2><?php _e('User Information', 'wplcs'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Username', 'wplcs'); ?></th>
                            <td><?php echo esc_html($user->user_login); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Email', 'wplcs'); ?></th>
                            <td><a href="mailto:<?php echo esc_attr($user->user_email); ?>"><?php echo esc_html($user->user_email); ?></a></td>
                        </tr>
                        <tr>
                            <th><?php _e('Display Name', 'wplcs'); ?></th>
                            <td><?php echo esc_html($user->display_name); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Registered', 'wplcs'); ?></th>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?></td>
                        </tr>
                    </table>
                    
                    <p>
                        <a href="<?php echo get_edit_user_link($user_id); ?>" class="button button-primary">
                            <?php _e('Edit User in WordPress', 'wplcs'); ?>
                        </a>
                    </p>
                </div>
                
                <!-- Tokens -->
                <div class="wplcs-user-tokens">
                    <h2><?php _e('User Tokens', 'wplcs'); ?></h2>
                    
                    <?php if (empty($user_tokens)): ?>
                        <p><?php _e('This user has no tokens.', 'wplcs'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Token', 'wplcs'); ?></th>
                                    <th><?php _e('Tier', 'wplcs'); ?></th>
                                    <th><?php _e('Status', 'wplcs'); ?></th>
                                    <th><?php _e('Expires', 'wplcs'); ?></th>
                                    <th><?php _e('Actions', 'wplcs'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_tokens as $token): ?>
                                    <?php 
                                    $tier = $tokens->get_tier($token->tier_id);
                                    $is_expired = strtotime($token->expires_at) < time();
                                    ?>
                                    <tr>
                                        <td>
                                            <code><?php echo esc_html(substr($token->token_hash, 0, 20) . '...'); ?></code>
                                        </td>
                                        <td><?php echo $tier ? esc_html($tier->name) : __('Unknown', 'wplcs'); ?></td>
                                        <td>
                                            <?php if ($token->is_active && !$is_expired): ?>
                                                <span class="wplcs-status active"><?php _e('Active', 'wplcs'); ?></span>
                                            <?php elseif ($is_expired): ?>
                                                <span class="wplcs-status expired"><?php _e('Expired', 'wplcs'); ?></span>
                                            <?php else: ?>
                                                <span class="wplcs-status inactive"><?php _e('Inactive', 'wplcs'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date_i18n(get_option('date_format'), strtotime($token->expires_at)); ?></td>
                                        <td>
                                            <?php if ($token->is_active): ?>
                                                <button type="button" class="button button-small wplcs-deactivate-token" 
                                                        data-token-id="<?php echo $token->id; ?>">
                                                    <?php _e('Deactivate', 'wplcs'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Sessions -->
                <div class="wplcs-user-sessions">
                    <h2><?php _e('User Sessions', 'wplcs'); ?></h2>
                    
                    <?php if (empty($user_sessions)): ?>
                        <p><?php _e('This user has no sessions.', 'wplcs'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Domain', 'wplcs'); ?></th>
                                    <th><?php _e('IP Address', 'wplcs'); ?></th>
                                    <th><?php _e('Status', 'wplcs'); ?></th>
                                    <th><?php _e('Last Activity', 'wplcs'); ?></th>
                                    <th><?php _e('Expires', 'wplcs'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_sessions as $session): ?>
                                    <?php $is_expired = strtotime($session->expires_at) < time(); ?>
                                    <tr>
                                        <td><?php echo esc_html($session->node_domain); ?></td>
                                        <td><?php echo esc_html($session->node_ip); ?></td>
                                        <td>
                                            <?php if ($session->is_active && !$is_expired): ?>
                                                <span class="wplcs-status active"><?php _e('Active', 'wplcs'); ?></span>
                                            <?php elseif ($is_expired): ?>
                                                <span class="wplcs-status expired"><?php _e('Expired', 'wplcs'); ?></span>
                                            <?php else: ?>
                                                <span class="wplcs-status inactive"><?php _e('Inactive', 'wplcs'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo human_time_diff(strtotime($session->last_activity), time()) . ' ' . __('ago', 'wplcs'); ?></td>
                                        <td><?php echo date_i18n(get_option('date_format'), strtotime($session->expires_at)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .wplcs-user-details {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }
        
        .wplcs-user-info-card,
        .wplcs-user-tokens,
        .wplcs-user-sessions {
            background: white;
            padding: 20px;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
        }
        
        .wplcs-user-info-card h2,
        .wplcs-user-tokens h2,
        .wplcs-user-sessions h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .wplcs-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .wplcs-status.active {
            background: #d4edda;
            color: #155724;
        }
        
        .wplcs-status.expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        .wplcs-status.inactive {
            background: #e2e3e5;
            color: #383d41;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX deactivate user token
     */
    public function ajax_deactivate_user_token() {
        if (!wp_verify_nonce($_POST['nonce'], 'wplcs_users_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wplcs'));
        }
        
        $token_id = intval($_POST['token_id']);
        
        global $wpdb;
        $tokens_table = $this->database->get_table('tokens');
        
        $result = $wpdb->update(
            $tokens_table,
            array('is_active' => 0, 'updated_at' => current_time('mysql')),
            array('id' => $token_id)
        );
        
        if ($result !== false) {
            wp_send_json_success(__('Token deactivated successfully.', 'wplcs'));
        } else {
            wp_send_json_error(__('Failed to deactivate token.', 'wplcs'));
        }
    }
    
    /**
     * AJAX reset user sessions
     */
    public function ajax_reset_user_sessions() {
        if (!wp_verify_nonce($_POST['nonce'], 'wplcs_users_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wplcs'));
        }
        
        $user_id = intval($_POST['user_id']);
        
        global $wpdb;
        $sessions_table = $this->database->get_table('sessions');
        $tokens_table = $this->database->get_table('tokens');
        
        // Deactivate all sessions for this user
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$sessions_table} s 
             JOIN {$tokens_table} t ON s.token_id = t.id 
             SET s.is_active = 0, s.updated_at = %s 
             WHERE t.user_id = %d",
            current_time('mysql'),
            $user_id
        ));
        
        if ($result !== false) {
            wp_send_json_success(__('All user sessions have been reset.', 'wplcs'));
        } else {
            wp_send_json_error(__('Failed to reset user sessions.', 'wplcs'));
        }
    }
}