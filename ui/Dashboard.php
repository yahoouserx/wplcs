<?php

namespace WPLCS\UI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * User dashboard for WPLCS
 */
class Dashboard {
    
    /**
     * Initialize dashboard
     */
    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_wplcs_deactivate_token', array($this, 'ajax_deactivate_token'));
        add_action('wp_ajax_wplcs_deactivate_session', array($this, 'ajax_deactivate_session'));
        add_action('wp_ajax_wplcs_refresh_dashboard', array($this, 'ajax_refresh_dashboard'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (is_account_page()) {
            wp_enqueue_style('wplcs-dashboard', WPLCS_ASSETS_URL . 'css/dashboard.css', array(), WPLCS_VERSION);
            wp_enqueue_script('wplcs-dashboard', WPLCS_ASSETS_URL . 'js/dashboard.js', array('jquery'), WPLCS_VERSION, true);
            
            wp_localize_script('wplcs-dashboard', 'wplcs_ajax', array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wplcs_dashboard'),
                'strings' => array(
                    'confirm_deactivate_token' => __('Are you sure you want to deactivate this token?', 'wplcs'),
                    'confirm_deactivate_session' => __('Are you sure you want to remove this session?', 'wplcs'),
                    'loading' => __('Loading...', 'wplcs'),
                    'error' => __('An error occurred. Please try again.', 'wplcs')
                )
            ));
        }
    }
    
    /**
     * Render user dashboard
     */
    public function render_user_dashboard() {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            echo '<p>' . __('Please log in to access your WPLCS console.', 'wplcs') . '</p>';
            return;
        }
        
        $tokens = new \WPLCS\Core\Tokens();
        $sessions = new \WPLCS\Core\Sessions();
        
        $user_tokens = $tokens->get_user_tokens($user_id, false);
        $user_sessions = $sessions->get_user_sessions($user_id, true);
        $token_stats = $tokens->get_token_stats($user_id);
        $session_stats = $sessions->get_session_stats($user_id);
        
        ?>
        <div class="wplcs-dashboard">
            <div class="wplcs-header">
                <h2><?php _e('WPLCS Console', 'wplcs'); ?></h2>
                <button type="button" class="button wplcs-refresh" data-action="refresh">
                    <?php _e('Refresh', 'wplcs'); ?>
                </button>
            </div>
            
            <?php $this->render_stats_overview($token_stats, $session_stats); ?>
            
            <div class="wplcs-tabs">
                <nav class="wplcs-tab-nav">
                    <button class="wplcs-tab-button active" data-tab="tokens">
                        <?php _e('Access Tokens', 'wplcs'); ?>
                    </button>
                    <button class="wplcs-tab-button" data-tab="sessions">
                        <?php _e('Active Sessions', 'wplcs'); ?>
                    </button>
                    <button class="wplcs-tab-button" data-tab="upgrade">
                        <?php _e('Upgrade', 'wplcs'); ?>
                    </button>
                </nav>
                
                <div class="wplcs-tab-content">
                    <div id="wplcs-tab-tokens" class="wplcs-tab-panel active">
                        <?php $this->render_tokens_panel($user_tokens); ?>
                    </div>
                    
                    <div id="wplcs-tab-sessions" class="wplcs-tab-panel">
                        <?php $this->render_sessions_panel($user_sessions); ?>
                    </div>
                    
                    <div id="wplcs-tab-upgrade" class="wplcs-tab-panel">
                        <?php $this->render_upgrade_panel(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render stats overview
     */
    private function render_stats_overview($token_stats, $session_stats) {
        ?>
        <div class="wplcs-stats-grid">
            <div class="wplcs-stat-card">
                <div class="wplcs-stat-value"><?php echo intval($token_stats->active_tokens); ?></div>
                <div class="wplcs-stat-label"><?php _e('Active Tokens', 'wplcs'); ?></div>
            </div>
            
            <div class="wplcs-stat-card">
                <div class="wplcs-stat-value"><?php echo intval($session_stats->active_sessions); ?></div>
                <div class="wplcs-stat-label"><?php _e('Active Sessions', 'wplcs'); ?></div>
            </div>
            
            <div class="wplcs-stat-card">
                <div class="wplcs-stat-value"><?php echo intval($session_stats->unique_domains); ?></div>
                <div class="wplcs-stat-label"><?php _e('Connected Domains', 'wplcs'); ?></div>
            </div>
            
            <div class="wplcs-stat-card">
                <div class="wplcs-stat-value"><?php echo intval($token_stats->total_usage); ?></div>
                <div class="wplcs-stat-label"><?php _e('Total API Calls', 'wplcs'); ?></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render tokens panel
     */
    private function render_tokens_panel($user_tokens) {
        if (empty($user_tokens)) {
            echo '<div class="wplcs-empty-state">';
            echo '<p>' . __('No tokens found. Purchase a product to get started.', 'wplcs') . '</p>';
            echo '<a href="' . wc_get_page_permalink('shop') . '" class="button button-primary">' . __('Browse Products', 'wplcs') . '</a>';
            echo '</div>';
            return;
        }
        
        ?>
        <div class="wplcs-tokens-grid">
            <?php foreach ($user_tokens as $token): ?>
                <div class="wplcs-token-card <?php echo $token->is_active ? 'active' : 'inactive'; ?>">
                    <div class="wplcs-token-header">
                        <h3><?php echo esc_html($token->tier_name); ?></h3>
                        <span class="wplcs-token-status <?php echo $this->get_token_status_class($token); ?>">
                            <?php echo $this->get_token_status_text($token); ?>
                        </span>
                    </div>
                    
                    <div class="wplcs-token-details">
                        <div class="wplcs-detail-row">
                            <span class="wplcs-detail-label"><?php _e('Token ID:', 'wplcs'); ?></span>
                            <span class="wplcs-detail-value">#<?php echo $token->id; ?></span>
                        </div>
                        
                        <div class="wplcs-detail-row">
                            <span class="wplcs-detail-label"><?php _e('Max Nodes:', 'wplcs'); ?></span>
                            <span class="wplcs-detail-value">
                                <?php echo $token->max_nodes == -1 ? __('Unlimited', 'wplcs') : $token->max_nodes; ?>
                            </span>
                        </div>
                        
                        <div class="wplcs-detail-row">
                            <span class="wplcs-detail-label"><?php _e('Active Nodes:', 'wplcs'); ?></span>
                            <span class="wplcs-detail-value"><?php echo intval($token->active_sessions); ?></span>
                        </div>
                        
                        <div class="wplcs-detail-row">
                            <span class="wplcs-detail-label"><?php _e('Expires:', 'wplcs'); ?></span>
                            <span class="wplcs-detail-value">
                                <?php echo date_i18n(get_option('date_format'), strtotime($token->expires_at)); ?>
                            </span>
                        </div>
                        
                        <div class="wplcs-detail-row">
                            <span class="wplcs-detail-label"><?php _e('Usage Count:', 'wplcs'); ?></span>
                            <span class="wplcs-detail-value"><?php echo intval($token->usage_count); ?></span>
                        </div>
                        
                        <?php if ($token->is_trial): ?>
                            <div class="wplcs-trial-badge">
                                <?php _e('Trial', 'wplcs'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="wplcs-token-actions">
                        <?php if ($token->is_active && strtotime($token->expires_at) > time()): ?>
                            <button type="button" class="button button-secondary wplcs-copy-token" 
                                    data-token="<?php echo esc_attr($this->get_masked_token($token->id)); ?>">
                                <?php _e('View Token', 'wplcs'); ?>
                            </button>
                            <button type="button" class="button button-danger wplcs-deactivate-token" 
                                    data-token-id="<?php echo $token->id; ?>">
                                <?php _e('Deactivate', 'wplcs'); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo wc_get_page_permalink('shop'); ?>" class="button button-primary">
                                <?php _e('Renew/Upgrade', 'wplcs'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render sessions panel
     */
    private function render_sessions_panel($user_sessions) {
        if (empty($user_sessions)) {
            echo '<div class="wplcs-empty-state">';
            echo '<p>' . __('No active sessions found.', 'wplcs') . '</p>';
            echo '</div>';
            return;
        }
        
        ?>
        <div class="wplcs-sessions-table-wrapper">
            <table class="wplcs-sessions-table">
                <thead>
                    <tr>
                        <th><?php _e('Domain', 'wplcs'); ?></th>
                        <th><?php _e('Tier', 'wplcs'); ?></th>
                        <th><?php _e('IP Address', 'wplcs'); ?></th>
                        <th><?php _e('Last Activity', 'wplcs'); ?></th>
                        <th><?php _e('Expires', 'wplcs'); ?></th>
                        <th><?php _e('Actions', 'wplcs'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_sessions as $session): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($session->node_domain); ?></strong>
                            </td>
                            <td><?php echo esc_html($session->tier_name); ?></td>
                            <td><?php echo esc_html($session->node_ip); ?></td>
                            <td>
                                <?php echo human_time_diff(strtotime($session->last_activity), time()) . ' ' . __('ago', 'wplcs'); ?>
                            </td>
                            <td>
                                <?php echo human_time_diff(time(), strtotime($session->expires_at)); ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small button-danger wplcs-deactivate-session" 
                                        data-session-id="<?php echo $session->id; ?>">
                                    <?php _e('Remove', 'wplcs'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render upgrade panel
     */
    private function render_upgrade_panel() {
        $tokens = new \WPLCS\Core\Tokens();
        $tiers = $tokens->get_tiers();
        
        ?>
        <div class="wplcs-upgrade-section">
            <h3><?php _e('Available Tiers', 'wplcs'); ?></h3>
            <p><?php _e('Upgrade your access level to get more features and higher limits.', 'wplcs'); ?></p>
            
            <div class="wplcs-tiers-grid">
                <?php foreach ($tiers as $tier): ?>
                    <div class="wplcs-tier-card">
                        <div class="wplcs-tier-header">
                            <h4><?php echo esc_html($tier->name); ?></h4>
                            <?php if ($tier->price > 0): ?>
                                <div class="wplcs-tier-price">
                                    <?php echo wc_price($tier->price); ?>
                                </div>
                            <?php else: ?>
                                <div class="wplcs-tier-price">
                                    <?php _e('Free', 'wplcs'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="wplcs-tier-features">
                            <ul>
                                <li>
                                    <strong><?php _e('Max Nodes:', 'wplcs'); ?></strong>
                                    <?php echo $tier->max_nodes == -1 ? __('Unlimited', 'wplcs') : $tier->max_nodes; ?>
                                </li>
                                <li>
                                    <strong><?php _e('Duration:', 'wplcs'); ?></strong>
                                    <?php echo human_time_diff(0, $tier->duration); ?>
                                </li>
                                <?php
                                $features = json_decode($tier->features, true);
                                if ($features):
                                    foreach ($features as $feature):
                                ?>
                                    <li><?php echo esc_html(ucwords(str_replace('_', ' ', $feature))); ?></li>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </ul>
                        </div>
                        
                        <div class="wplcs-tier-action">
                            <a href="<?php echo wc_get_page_permalink('shop'); ?>" class="button button-primary">
                                <?php _e('Get Started', 'wplcs'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get token status class
     */
    private function get_token_status_class($token) {
        if (!$token->is_active) {
            return 'inactive';
        }
        
        if (strtotime($token->expires_at) < time()) {
            return 'expired';
        }
        
        return 'active';
    }
    
    /**
     * Get token status text
     */
    private function get_token_status_text($token) {
        if (!$token->is_active) {
            return __('Inactive', 'wplcs');
        }
        
        if (strtotime($token->expires_at) < time()) {
            return __('Expired', 'wplcs');
        }
        
        return __('Active', 'wplcs');
    }
    
    /**
     * Get masked token for security
     */
    private function get_masked_token($token_id) {
        // For security, we don't show the actual token in frontend
        // Instead, we show a reference that can be used to reveal it via AJAX
        return 'token_' . $token_id;
    }
    
    /**
     * AJAX: Deactivate token
     */
    public function ajax_deactivate_token() {
        check_ajax_referer('wplcs_dashboard', 'nonce');
        
        $token_id = intval($_POST['token_id']);
        $user_id = get_current_user_id();
        
        if (!$token_id || !$user_id) {
            wp_send_json_error(__('Invalid request.', 'wplcs'));
        }
        
        $tokens = new \WPLCS\Core\Tokens();
        $result = $tokens->deactivate_token($token_id, $user_id);
        
        if ($result) {
            wp_send_json_success(__('Token deactivated successfully.', 'wplcs'));
        } else {
            wp_send_json_error(__('Failed to deactivate token.', 'wplcs'));
        }
    }
    
    /**
     * AJAX: Deactivate session
     */
    public function ajax_deactivate_session() {
        check_ajax_referer('wplcs_dashboard', 'nonce');
        
        $session_id = intval($_POST['session_id']);
        $user_id = get_current_user_id();
        
        if (!$session_id || !$user_id) {
            wp_send_json_error(__('Invalid request.', 'wplcs'));
        }
        
        $sessions = new \WPLCS\Core\Sessions();
        $result = $sessions->deactivate_session($session_id, $user_id);
        
        if ($result) {
            wp_send_json_success(__('Session removed successfully.', 'wplcs'));
        } else {
            wp_send_json_error(__('Failed to remove session.', 'wplcs'));
        }
    }
    
    /**
     * AJAX: Refresh dashboard
     */
    public function ajax_refresh_dashboard() {
        check_ajax_referer('wplcs_dashboard', 'nonce');
        
        ob_start();
        $this->render_user_dashboard();
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
}