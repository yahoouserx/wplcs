<?php
namespace WPLCS\Admin;

use WPLCS\Core\Database;

/**
 * Admin Plans Manager
 */
class Admin_Plans {
    
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Database();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_form_submissions'));
        add_action('wp_ajax_wplcs_toggle_tier', array($this, 'ajax_toggle_tier'));
        add_action('wp_ajax_wplcs_delete_tier', array($this, 'ajax_delete_tier'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wplcs-dashboard',
            __('Plans Manager', 'wplcs'),
            __('Plans', 'wplcs'),
            'manage_options',
            'wplcs-plans',
            array($this, 'plans_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wplcs-plans') !== false) {
            wp_enqueue_script('wplcs-plans-admin', WPLCS_PLUGIN_URL . 'assets/admin-plans.js', array('jquery'), WPLCS_VERSION, true);
            wp_localize_script('wplcs-plans-admin', 'wplcs_plans_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wplcs_plans_nonce'),
                'i18n' => array(
                    'confirm_delete' => __('Are you sure you want to delete this tier? This action cannot be undone.', 'wplcs'),
                    'confirm_toggle' => __('Are you sure you want to change the status of this tier?', 'wplcs')
                )
            ));
        }
    }
    
    /**
     * Plans page
     */
    public function plans_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $tier_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        switch ($action) {
            case 'new':
                $this->render_tier_form();
                break;
            case 'edit':
                $this->render_tier_form($tier_id);
                break;
            default:
                $this->render_plans_list();
                break;
        }
    }
    
    /**
     * Render plans list
     */
    private function render_plans_list() {
        global $wpdb;
        $tiers_table = $this->database->get_table('tiers');
        $tiers = $wpdb->get_results("SELECT * FROM {$tiers_table} ORDER BY sort_order ASC");
        
        ?>
        <div class="wrap">
            <h1>
                <?php _e('License Plans Manager', 'wplcs'); ?>
                <a href="<?php echo admin_url('admin.php?page=wplcs-plans&action=new'); ?>" class="page-title-action">
                    <?php _e('Add New Plan', 'wplcs'); ?>
                </a>
            </h1>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($_GET['message']); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="wplcs-plans-overview">
                <div class="wplcs-stats-row">
                    <div class="wplcs-stat-card">
                        <h3><?php echo count($tiers); ?></h3>
                        <p><?php _e('Total Plans', 'wplcs'); ?></p>
                    </div>
                    <div class="wplcs-stat-card">
                        <h3><?php echo count(array_filter($tiers, function($t) { return $t->is_active; })); ?></h3>
                        <p><?php _e('Active Plans', 'wplcs'); ?></p>
                    </div>
                    <div class="wplcs-stat-card">
                        <h3><?php echo count(array_filter($tiers, function($t) { return $t->is_trial; })); ?></h3>
                        <p><?php _e('Trial Plans', 'wplcs'); ?></p>
                    </div>
                </div>
            </div>
            
            <?php if (empty($tiers)): ?>
                <div class="wplcs-empty-state">
                    <h3><?php _e('No plans created yet', 'wplcs'); ?></h3>
                    <p><?php _e('Create your first license plan to get started with WPLCS.', 'wplcs'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=wplcs-plans&action=new'); ?>" class="button button-primary button-large">
                        <?php _e('Create First Plan', 'wplcs'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="wplcs-plans-grid">
                    <?php foreach ($tiers as $tier): ?>
                        <?php $this->render_plan_card($tier); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .wplcs-plans-overview {
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .wplcs-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .wplcs-stat-card {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        
        .wplcs-stat-card h3 {
            font-size: 2.5em;
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        
        .wplcs-stat-card p {
            margin: 0;
            font-weight: 500;
            color: #666;
        }
        
        .wplcs-empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
        }
        
        .wplcs-plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        </style>
        <?php
    }
    
    /**
     * Render plan card
     */
    private function render_plan_card($tier) {
        $wc_products = $this->get_attached_products($tier->id);
        
        ?>
        <div class="wplcs-plan-card <?php echo $tier->is_active ? 'active' : 'inactive'; ?>">
            <div class="wplcs-plan-header">
                <div class="wplcs-plan-title">
                    <h3><?php echo esc_html($tier->name); ?></h3>
                    <div class="wplcs-plan-badges">
                        <?php if ($tier->is_trial): ?>
                            <span class="wplcs-badge trial"><?php _e('Trial', 'wplcs'); ?></span>
                        <?php endif; ?>
                        <span class="wplcs-badge <?php echo $tier->is_active ? 'active' : 'inactive'; ?>">
                            <?php echo $tier->is_active ? __('Active', 'wplcs') : __('Inactive', 'wplcs'); ?>
                        </span>
                    </div>
                </div>
                
                <div class="wplcs-plan-price">
                    <?php echo $tier->price > 0 ? wc_price($tier->price) : __('Free', 'wplcs'); ?>
                </div>
            </div>
            
            <div class="wplcs-plan-details">
                <ul class="wplcs-plan-features">
                    <li><strong><?php _e('Max Sites:', 'wplcs'); ?></strong> 
                        <?php echo $tier->max_nodes == -1 ? __('Unlimited', 'wplcs') : $tier->max_nodes; ?>
                    </li>
                    <li><strong><?php _e('Duration:', 'wplcs'); ?></strong> 
                        <?php echo human_time_diff(0, $tier->duration); ?>
                    </li>
                    <?php if ($tier->description): ?>
                        <li><strong><?php _e('Description:', 'wplcs'); ?></strong> 
                            <?php echo esc_html($tier->description); ?>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <?php if (!empty($wc_products)): ?>
                    <div class="wplcs-attached-products">
                        <h5><?php _e('WooCommerce Products:', 'wplcs'); ?></h5>
                        <ul>
                            <?php foreach ($wc_products as $product): ?>
                                <li>
                                    <a href="<?php echo get_edit_post_link($product->ID); ?>" target="_blank">
                                        <?php echo esc_html($product->post_title); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="wplcs-plan-actions">
                <a href="<?php echo admin_url('admin.php?page=wplcs-plans&action=edit&id=' . $tier->id); ?>" 
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
        
        <style>
        .wplcs-plan-card {
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .wplcs-plan-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .wplcs-plan-card.active {
            border-left: 4px solid #00a32a;
        }
        
        .wplcs-plan-card.inactive {
            opacity: 0.7;
            border-left: 4px solid #c3c4c7;
        }
        
        .wplcs-plan-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .wplcs-plan-title h3 {
            margin: 0 0 10px 0;
            font-size: 1.3em;
        }
        
        .wplcs-plan-badges {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .wplcs-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .wplcs-badge.trial {
            background: #fff3cd;
            color: #856404;
        }
        
        .wplcs-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .wplcs-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .wplcs-plan-price {
            font-size: 1.5em;
            font-weight: bold;
            color: #0073aa;
        }
        
        .wplcs-plan-features {
            list-style: none;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .wplcs-plan-features li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .wplcs-attached-products h5 {
            margin: 15px 0 5px 0;
            font-size: 0.9em;
            text-transform: uppercase;
            color: #666;
        }
        
        .wplcs-attached-products ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .wplcs-attached-products li {
            padding: 5px 0;
        }
        
        .wplcs-plan-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .wplcs-plan-actions .button {
            flex: 1;
            min-width: 80px;
            text-align: center;
        }
        
        .button-danger {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        
        .button-danger:hover {
            background: #c82333;
            border-color: #c82333;
            color: white;
        }
        </style>
        <?php
    }
    
    /**
     * Get attached WooCommerce products
     */
    private function get_attached_products($tier_id) {
        return get_posts(array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => '_wplcs_tier_id',
                    'value' => $tier_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
    }
    
    /**
     * Render tier form
     */
    private function render_tier_form($tier_id = 0) {
        $tier = null;
        $is_edit = false;
        
        if ($tier_id) {
            global $wpdb;
            $tiers_table = $this->database->get_table('tiers');
            $tier = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tiers_table} WHERE id = %d", $tier_id));
            $is_edit = true;
        }
        
        // Get WooCommerce products
        $wc_products = get_posts(array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        ?>
        <div class="wrap">
            <h1>
                <?php echo $is_edit ? __('Edit Plan', 'wplcs') : __('Add New Plan', 'wplcs'); ?>
                <a href="<?php echo admin_url('admin.php?page=wplcs-plans'); ?>" class="page-title-action">
                    <?php _e('Back to Plans', 'wplcs'); ?>
                </a>
            </h1>
            
            <form method="post" action="" class="wplcs-tier-form">
                <?php wp_nonce_field('wplcs_tier_form', 'wplcs_tier_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo $is_edit ? 'edit_tier' : 'add_tier'; ?>" />
                <?php if ($is_edit): ?>
                    <input type="hidden" name="tier_id" value="<?php echo $tier_id; ?>" />
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Plan Name', 'wplcs'); ?></th>
                        <td>
                            <input type="text" name="name" value="<?php echo $tier ? esc_attr($tier->name) : ''; ?>" 
                                   class="regular-text" required />
                            <p class="description"><?php _e('The name of this license plan.', 'wplcs'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Description', 'wplcs'); ?></th>
                        <td>
                            <textarea name="description" class="large-text" rows="3"><?php echo $tier ? esc_textarea($tier->description) : ''; ?></textarea>
                            <p class="description"><?php _e('Optional description for this plan.', 'wplcs'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Price', 'wplcs'); ?></th>
                        <td>
                            <input type="number" name="price" value="<?php echo $tier ? $tier->price : '0'; ?>" 
                                   min="0" step="0.01" class="small-text" />
                            <p class="description"><?php _e('Price in your store currency. Set to 0 for free plans.', 'wplcs'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Maximum Sites', 'wplcs'); ?></th>
                        <td>
                            <input type="number" name="max_nodes" value="<?php echo $tier ? $tier->max_nodes : '1'; ?>" 
                                   min="-1" class="small-text" />
                            <p class="description"><?php _e('Maximum number of sites allowed. Use -1 for unlimited.', 'wplcs'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Duration (Days)', 'wplcs'); ?></th>
                        <td>
                            <input type="number" name="duration" value="<?php echo $tier ? $tier->duration / DAY_IN_SECONDS : '365'; ?>" 
                                   min="1" class="small-text" />
                            <p class="description"><?php _e('License duration in days.', 'wplcs'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Sort Order', 'wplcs'); ?></th>
                        <td>
                            <input type="number" name="sort_order" value="<?php echo $tier ? $tier->sort_order : '0'; ?>" 
                                   min="0" class="small-text" />
                            <p class="description"><?php _e('Display order (lower numbers appear first).', 'wplcs'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Plan Options', 'wplcs'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_trial" value="1" 
                                       <?php checked($tier ? $tier->is_trial : false); ?> />
                                <?php _e('Trial Plan', 'wplcs'); ?>
                            </label>
                            <br />
                            <label>
                                <input type="checkbox" name="is_active" value="1" 
                                       <?php checked($tier ? $tier->is_active : true); ?> />
                                <?php _e('Active', 'wplcs'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('WooCommerce Products', 'wplcs'); ?></th>
                        <td>
                            <?php if (!empty($wc_products)): ?>
                                <select name="wc_products[]" multiple class="wplcs-product-select">
                                    <?php 
                                    $selected_products = array();
                                    if ($tier_id) {
                                        $attached = get_posts(array(
                                            'post_type' => 'product',
                                            'meta_query' => array(
                                                array(
                                                    'key' => '_wplcs_tier_id',
                                                    'value' => $tier_id,
                                                    'compare' => '='
                                                )
                                            ),
                                            'posts_per_page' => -1,
                                            'fields' => 'ids'
                                        ));
                                        $selected_products = $attached;
                                    }
                                    ?>
                                    <?php foreach ($wc_products as $product): ?>
                                        <option value="<?php echo $product->ID; ?>" 
                                                <?php selected(in_array($product->ID, $selected_products)); ?>>
                                            <?php echo esc_html($product->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Select WooCommerce products to attach to this plan. Hold Ctrl/Cmd to select multiple.', 'wplcs'); ?></p>
                            <?php else: ?>
                                <p class="description"><?php _e('No WooCommerce products found. Create products first, then edit this plan to attach them.', 'wplcs'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" 
                           value="<?php echo $is_edit ? __('Update Plan', 'wplcs') : __('Create Plan', 'wplcs'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=wplcs-plans'); ?>" class="button">
                        <?php _e('Cancel', 'wplcs'); ?>
                    </a>
                </p>
            </form>
        </div>
        
        <style>
        .wplcs-tier-form {
            background: white;
            padding: 20px;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
        }
        
        .wplcs-product-select {
            width: 100%;
            min-height: 120px;
        }
        </style>
        <?php
    }
    
    /**
     * Handle form submissions
     */
    public function handle_form_submissions() {
        if (!isset($_POST['wplcs_tier_nonce']) || !wp_verify_nonce($_POST['wplcs_tier_nonce'], 'wplcs_tier_form')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $action = sanitize_text_field($_POST['action']);
        
        if ($action === 'add_tier') {
            $this->add_tier();
        } elseif ($action === 'edit_tier') {
            $this->edit_tier();
        }
    }
    
    /**
     * Add tier
     */
    private function add_tier() {
        global $wpdb;
        $tiers_table = $this->database->get_table('tiers');
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'price' => floatval($_POST['price']),
            'max_nodes' => intval($_POST['max_nodes']),
            'duration' => intval($_POST['duration']) * DAY_IN_SECONDS,
            'sort_order' => intval($_POST['sort_order']),
            'is_trial' => isset($_POST['is_trial']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'features' => json_encode(array()),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($tiers_table, $data);
        
        if ($result) {
            $tier_id = $wpdb->insert_id;
            $this->update_product_attachments($tier_id);
            
            wp_redirect(admin_url('admin.php?page=wplcs-plans&message=' . urlencode(__('Plan created successfully!', 'wplcs'))));
            exit;
        }
    }
    
    /**
     * Edit tier
     */
    private function edit_tier() {
        global $wpdb;
        $tiers_table = $this->database->get_table('tiers');
        $tier_id = intval($_POST['tier_id']);
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'price' => floatval($_POST['price']),
            'max_nodes' => intval($_POST['max_nodes']),
            'duration' => intval($_POST['duration']) * DAY_IN_SECONDS,
            'sort_order' => intval($_POST['sort_order']),
            'is_trial' => isset($_POST['is_trial']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->update($tiers_table, $data, array('id' => $tier_id));
        
        if ($result !== false) {
            $this->update_product_attachments($tier_id);
            
            wp_redirect(admin_url('admin.php?page=wplcs-plans&message=' . urlencode(__('Plan updated successfully!', 'wplcs'))));
            exit;
        }
    }
    
    /**
     * Update product attachments
     */
    private function update_product_attachments($tier_id) {
        // Clear existing attachments
        $existing_products = get_posts(array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => '_wplcs_tier_id',
                    'value' => $tier_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($existing_products as $product_id) {
            delete_post_meta($product_id, '_wplcs_tier_id');
        }
        
        // Add new attachments
        if (isset($_POST['wc_products']) && is_array($_POST['wc_products'])) {
            foreach ($_POST['wc_products'] as $product_id) {
                update_post_meta(intval($product_id), '_wplcs_tier_id', $tier_id);
            }
        }
    }
    
    /**
     * AJAX toggle tier
     */
    public function ajax_toggle_tier() {
        if (!wp_verify_nonce($_POST['nonce'], 'wplcs_plans_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wplcs'));
        }
        
        $tier_id = intval($_POST['tier_id']);
        $is_active = intval($_POST['is_active']);
        $new_status = $is_active ? 0 : 1;
        
        global $wpdb;
        $tiers_table = $this->database->get_table('tiers');
        
        $result = $wpdb->update(
            $tiers_table,
            array('is_active' => $new_status, 'updated_at' => current_time('mysql')),
            array('id' => $tier_id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'new_status' => $new_status,
                'message' => $new_status ? __('Plan activated.', 'wplcs') : __('Plan deactivated.', 'wplcs')
            ));
        } else {
            wp_send_json_error(__('Failed to update plan status.', 'wplcs'));
        }
    }
    
    /**
     * AJAX delete tier
     */
    public function ajax_delete_tier() {
        if (!wp_verify_nonce($_POST['nonce'], 'wplcs_plans_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wplcs'));
        }
        
        $tier_id = intval($_POST['tier_id']);
        
        // Check if tier has active tokens
        global $wpdb;
        $tokens_table = $this->database->get_table('tokens');
        $active_tokens = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tokens_table} WHERE tier_id = %d AND is_active = 1",
            $tier_id
        ));
        
        if ($active_tokens > 0) {
            wp_send_json_error(__('Cannot delete plan with active licenses.', 'wplcs'));
        }
        
        $tiers_table = $this->database->get_table('tiers');
        $result = $wpdb->delete($tiers_table, array('id' => $tier_id));
        
        if ($result) {
            // Remove product attachments
            $attached_products = get_posts(array(
                'post_type' => 'product',
                'meta_query' => array(
                    array(
                        'key' => '_wplcs_tier_id',
                        'value' => $tier_id,
                        'compare' => '='
                    )
                ),
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            
            foreach ($attached_products as $product_id) {
                delete_post_meta($product_id, '_wplcs_tier_id');
            }
            
            wp_send_json_success(__('Plan deleted successfully.', 'wplcs'));
        } else {
            wp_send_json_error(__('Failed to delete plan.', 'wplcs'));
        }
    }
}