<?php
namespace WPLCS\UI;

use WPLCS\Core\Tokens;
use WPLCS\Core\Sessions;

/**
 * WPLCS Shortcodes Handler
 */
class Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('wplcs_account_dashboard', array($this, 'render_account_dashboard'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (is_user_logged_in() && $this->is_wplcs_shortcode_page()) {
            // Enqueue modern theme system for frontend
            wp_enqueue_style('wplcs-theme-frontend', WPLCS_ASSETS_URL . 'css/wplcs-theme.css', array(), WPLCS_VERSION);
            wp_enqueue_style('wplcs-frontend', WPLCS_ASSETS_URL . 'css/wplcs-frontend.css', array('wplcs-theme-frontend'), WPLCS_VERSION);
            wp_enqueue_style('wplcs-dashboard', WPLCS_ASSETS_URL . 'css/dashboard.css', array('wplcs-theme-frontend'), WPLCS_VERSION);
            
            wp_enqueue_script('wplcs-theme-frontend', WPLCS_ASSETS_URL . 'js/wplcs-theme.js', array('jquery'), WPLCS_VERSION, true);
            wp_enqueue_script('wplcs-frontend', WPLCS_ASSETS_URL . 'js/shortcode.js', array('jquery', 'wplcs-theme-frontend'), WPLCS_VERSION, true);
            
            wp_localize_script('wplcs-frontend', 'wplcs_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wplcs_shortcode_nonce'),
                'i18n' => array(
                    'confirm_deactivate' => __('Are you sure you want to deactivate this license from the selected site?', 'wplcs'),
                    'confirm_regenerate' => __('Are you sure you want to regenerate this token? This will invalidate the current token.', 'wplcs'),
                    'processing' => __('Processing...', 'wplcs'),
                    'error' => __('An error occurred. Please try again.', 'wplcs'),
                    'success' => __('Operation completed successfully.', 'wplcs')
                )
            ));
        }
    }
    
    /**
     * Check if current page contains WPLCS shortcode
     */
    private function is_wplcs_shortcode_page() {
        global $post;
        return $post && has_shortcode($post->post_content, 'wplcs_account_dashboard');
    }
    
    /**
     * Render account dashboard shortcode
     */
    public function render_account_dashboard($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="wplcs-notice wplcs-notice-error">' . 
                   __('You must be logged in to view your license dashboard.', 'wplcs') . 
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'show_trials' => 'yes',
            'show_expired' => 'yes',
            'show_upgrades' => 'yes',
            'columns' => '2'
        ), $atts, 'wplcs_account_dashboard');
        
        $user_id = get_current_user_id();
        $tokens = new Tokens();
        $sessions = new Sessions();
        
        // Get user's tokens
        $user_tokens = $tokens->get_user_tokens($user_id);
        $user_sessions = $sessions->get_user_sessions($user_id);
        
        ob_start();
        ?>
        <div class="wplcs-theme-container" data-theme="light">
        <div class="wplcs-account-dashboard" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <div class="wplcs-dashboard-header">
                <h3><?php _e('Your Licenses & Sites', 'wplcs'); ?></h3>
                <div class="wplcs-dashboard-stats">
                    <div class="wplcs-stat">
                        <span class="wplcs-stat-number"><?php echo count($user_tokens); ?></span>
                        <span class="wplcs-stat-label"><?php _e('Active Licenses', 'wplcs'); ?></span>
                    </div>
                    <div class="wplcs-stat">
                        <span class="wplcs-stat-number"><?php echo count($user_sessions); ?></span>
                        <span class="wplcs-stat-label"><?php _e('Connected Sites', 'wplcs'); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (empty($user_tokens)): ?>
                <div class="wplcs-notice wplcs-notice-info">
                    <p><?php _e('You don\'t have any licenses yet. Purchase a license to get started!', 'wplcs'); ?></p>
                </div>
            <?php else: ?>
                <div class="wplcs-licenses-grid">
                    <?php foreach ($user_tokens as $token): ?>
                        <?php $this->render_license_card($token, $user_sessions, $atts); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_upgrades'] === 'yes'): ?>
                <?php $this->render_upgrade_section($user_tokens); ?>
            <?php endif; ?>
        </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render individual license card
     */
    private function render_license_card($token, $user_sessions, $atts) {
        $tokens = new Tokens();
        $tier = $tokens->get_tier($token->tier_id);
        $token_sessions = array_filter($user_sessions, function($session) use ($token) {
            return $session->token_id == $token->id;
        });
        
        $is_expired = strtotime($token->expires_at) < time();
        $is_trial = $tier && $tier->is_trial;
        $days_left = $is_expired ? 0 : ceil((strtotime($token->expires_at) - time()) / DAY_IN_SECONDS);
        
        // Skip expired if not showing them
        if ($is_expired && $atts['show_expired'] === 'no') {
            return;
        }
        
        // Skip trials if not showing them
        if ($is_trial && $atts['show_trials'] === 'no') {
            return;
        }
        
        ?>
        <div class="wplcs-license-card <?php echo $is_expired ? 'expired' : 'active'; ?> <?php echo $is_trial ? 'trial' : ''; ?>">
            <div class="wplcs-license-header">
                <div class="wplcs-license-title">
                    <h4><?php echo $tier ? esc_html($tier->name) : __('Unknown Tier', 'wplcs'); ?></h4>
                    <div class="wplcs-license-badges">
                        <?php if ($is_trial): ?>
                            <span class="wplcs-badge trial"><?php _e('Trial', 'wplcs'); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($is_expired): ?>
                            <span class="wplcs-badge expired"><?php _e('Expired', 'wplcs'); ?></span>
                        <?php else: ?>
                            <span class="wplcs-badge active"><?php _e('Active', 'wplcs'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="wplcs-license-meta">
                    <div class="wplcs-license-expiry">
                        <?php if ($is_expired): ?>
                            <span class="expired"><?php _e('Expired', 'wplcs'); ?> <?php echo human_time_diff(strtotime($token->expires_at), time()); ?> <?php _e('ago', 'wplcs'); ?></span>
                        <?php else: ?>
                            <span class="active"><?php echo sprintf(_n('%d day left', '%d days left', $days_left, 'wplcs'), $days_left); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="wplcs-license-usage">
                        <?php 
                        $max_sites = $tier ? ($tier->max_nodes == -1 ? '∞' : $tier->max_nodes) : '0';
                        $used_sites = count($token_sessions);
                        ?>
                        <span><?php echo sprintf(__('%d / %s sites', 'wplcs'), $used_sites, $max_sites); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="wplcs-license-content">
                <div class="wplcs-token-info">
                    <label><?php _e('License Key:', 'wplcs'); ?></label>
                    <div class="wplcs-token-display">
                        <input type="text" readonly value="<?php echo esc_attr($token->token_hash); ?>" class="wplcs-token-field" />
                        <button type="button" class="wplcs-copy-token button button-small" data-token="<?php echo esc_attr($token->token_hash); ?>">
                            <?php _e('Copy', 'wplcs'); ?>
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($token_sessions)): ?>
                    <div class="wplcs-connected-sites">
                        <h5><?php _e('Connected Sites', 'wplcs'); ?></h5>
                        <div class="wplcs-sites-list">
                            <?php foreach ($token_sessions as $session): ?>
                                <div class="wplcs-site-item">
                                    <div class="wplcs-site-info">
                                        <strong><?php echo esc_html($session->node_domain); ?></strong>
                                        <div class="wplcs-site-meta">
                                            <span class="wplcs-site-ip"><?php echo esc_html($session->node_ip); ?></span>
                                            <span class="wplcs-site-activity"><?php _e('Last active:', 'wplcs'); ?> <?php echo human_time_diff(strtotime($session->last_activity), time()); ?> <?php _e('ago', 'wplcs'); ?></span>
                                        </div>
                                    </div>
                                    <div class="wplcs-site-actions">
                                        <?php if ($session->is_active): ?>
                                            <button type="button" class="button button-small wplcs-deactivate-site" 
                                                    data-session-id="<?php echo $session->id; ?>">
                                                <?php _e('Deactivate', 'wplcs'); ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="wplcs-inactive"><?php _e('Inactive', 'wplcs'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="wplcs-license-actions">
                    <?php if (!$is_expired): ?>
                        <button type="button" class="button wplcs-regenerate-token" data-token-id="<?php echo $token->id; ?>">
                            <?php _e('Regenerate Key', 'wplcs'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($is_expired): ?>
                        <button type="button" class="button button-primary wplcs-renew-license" data-token-id="<?php echo $token->id; ?>">
                            <?php _e('Renew License', 'wplcs'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($tier && !$tier->is_trial): ?>
                        <a href="<?php echo $this->get_upgrade_url($tier->id); ?>" class="button wplcs-upgrade-link">
                            <?php _e('Upgrade', 'wplcs'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render upgrade section
     */
    private function render_upgrade_section($user_tokens) {
        $tokens = new Tokens();
        $available_tiers = $tokens->get_available_upgrade_tiers($user_tokens);
        
        if (empty($available_tiers)) {
            return;
        }
        
        ?>
        <div class="wplcs-upgrade-section">
            <h4><?php _e('Available Upgrades', 'wplcs'); ?></h4>
            <div class="wplcs-upgrade-tiers">
                <?php foreach ($available_tiers as $tier): ?>
                    <div class="wplcs-upgrade-tier">
                        <h5><?php echo esc_html($tier->name); ?></h5>
                        <div class="wplcs-tier-price"><?php echo wc_price($tier->price); ?></div>
                        <div class="wplcs-tier-features">
                            <ul>
                                <li><?php echo sprintf(__('Up to %s sites', 'wplcs'), $tier->max_nodes == -1 ? __('unlimited', 'wplcs') : $tier->max_nodes); ?></li>
                                <li><?php echo sprintf(__('%s duration', 'wplcs'), human_time_diff(0, $tier->duration)); ?></li>
                            </ul>
                        </div>
                        <a href="<?php echo $this->get_upgrade_url($tier->id); ?>" class="button button-primary">
                            <?php _e('Upgrade Now', 'wplcs'); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get upgrade URL for a tier
     */
    private function get_upgrade_url($tier_id) {
        // This would link to a WooCommerce product or custom upgrade page
        return add_query_arg(array(
            'wplcs_upgrade' => $tier_id,
            'redirect_to' => urlencode(get_permalink())
        ), wc_get_cart_url());
    }
}