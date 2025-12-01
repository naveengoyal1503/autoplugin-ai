<?php
/*
Plugin Name: ContentVault Pro
Plugin URI: https://contentvault-pro.local
Description: Membership, paywall, and digital product management for WordPress monetization
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentVault_Pro.php
License: GPL v2 or later
Text Domain: contentvault-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTVAULT_PRO_VERSION', '1.0.0');
define('CONTENTVAULT_PRO_PATH', plugin_dir_path(__FILE__));
define('CONTENTVAULT_PRO_URL', plugin_dir_url(__FILE__));

class ContentVaultPro {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init();
    }

    private function init() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('init', array($this, 'register_post_types'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        add_shortcode('contentvault_paywall', array($this, 'render_paywall_shortcode'));
        add_shortcode('contentvault_products', array($this, 'render_products_shortcode'));
        add_shortcode('contentvault_member_content', array($this, 'render_member_content_shortcode'));
        add_action('wp_ajax_cv_purchase_product', array($this, 'handle_product_purchase'));
        add_action('wp_ajax_nopriv_cv_purchase_product', array($this, 'handle_product_purchase'));
        add_filter('the_content', array($this, 'filter_post_content'));
        add_action('wp_footer', array($this, 'load_stripe_js'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cv_memberships (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            membership_tier varchar(100) NOT NULL,
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime,
            status varchar(50) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;

        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cv_purchases (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            transaction_id varchar(255),
            amount decimal(10, 2),
            purchase_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY product_id (product_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('contentvault_pro_db_version', CONTENTVAULT_PRO_VERSION);
    }

    public function deactivate() {
        wp_clear_scheduled_hook('contentvault_pro_check_subscriptions');
    }

    public function register_post_types() {
        register_post_type('cv_product', array(
            'labels' => array(
                'name' => __('Digital Products', 'contentvault-pro'),
                'singular_name' => __('Digital Product', 'contentvault-pro'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-cart',
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('ContentVault Pro', 'contentvault-pro'),
            __('ContentVault', 'contentvault-pro'),
            'manage_options',
            'contentvault-pro',
            array($this, 'render_admin_dashboard'),
            'dashicons-lock',
            99
        );

        add_submenu_page(
            'contentvault-pro',
            __('Settings', 'contentvault-pro'),
            __('Settings', 'contentvault-pro'),
            'manage_options',
            'contentvault-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'contentvault-pro',
            __('Members', 'contentvault-pro'),
            __('Members', 'contentvault-pro'),
            'manage_options',
            'contentvault-members',
            array($this, 'render_members_page')
        );
    }

    public function render_admin_dashboard() {
        global $wpdb;
        $total_members = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cv_memberships WHERE status = 'active'");
        $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}cv_purchases");
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('ContentVault Pro Dashboard', 'contentvault-pro'); ?></h1>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3><?php echo esc_html__('Active Members', 'contentvault-pro'); ?></h3>
                    <p style="font-size: 32px; margin: 10px 0; color: #0073aa;"><?php echo intval($total_members); ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3><?php echo esc_html__('Total Revenue', 'contentvault-pro'); ?></h3>
                    <p style="font-size: 32px; margin: 10px 0; color: #28a745;"><?php echo '$' . number_format(floatval($total_revenue), 2); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('contentvault_settings_nonce');
            update_option('contentvault_stripe_key', sanitize_text_field($_POST['stripe_key'] ?? ''));
            update_option('contentvault_stripe_secret', sanitize_text_field($_POST['stripe_secret'] ?? ''));
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'contentvault-pro') . '</p></div>';
        }

        $stripe_key = get_option('contentvault_stripe_key', '');
        $stripe_secret = get_option('contentvault_stripe_secret', '');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('ContentVault Pro Settings', 'contentvault-pro'); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('contentvault_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="stripe_key"><?php echo esc_html__('Stripe Publishable Key', 'contentvault-pro'); ?></label></th>
                        <td><input type="text" id="stripe_key" name="stripe_key" value="<?php echo esc_attr($stripe_key); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="stripe_secret"><?php echo esc_html__('Stripe Secret Key', 'contentvault-pro'); ?></label></th>
                        <td><input type="password" id="stripe_secret" name="stripe_secret" value="<?php echo esc_attr($stripe_secret); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_members_page() {
        global $wpdb;
        $members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cv_memberships ORDER BY start_date DESC");
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Members', 'contentvault-pro'); ?></h1>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('User ID', 'contentvault-pro'); ?></th>
                        <th><?php echo esc_html__('Membership Tier', 'contentvault-pro'); ?></th>
                        <th><?php echo esc_html__('Start Date', 'contentvault-pro'); ?></th>
                        <th><?php echo esc_html__('Status', 'contentvault-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member) : ?>
                        <tr>
                            <td><?php echo intval($member->user_id); ?></td>
                            <td><?php echo esc_html($member->membership_tier); ?></td>
                            <td><?php echo esc_html($member->start_date); ?></td>
                            <td><?php echo esc_html($member->status); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function add_meta_boxes() {
        add_meta_box(
            'cv_product_settings',
            __('Product Settings', 'contentvault-pro'),
            array($this, 'render_product_meta_box'),
            'cv_product',
            'normal',
            'high'
        );

        add_meta_box(
            'cv_content_restrictions',
            __('Content Restrictions', 'contentvault-pro'),
            array($this, 'render_content_restrictions_meta_box'),
            'post',
            'normal',
            'high'
        );
    }

    public function render_product_meta_box($post) {
        $price = get_post_meta($post->ID, '_cv_product_price', true);
        $file_url = get_post_meta($post->ID, '_cv_product_file', true);
        wp_nonce_field('cv_product_nonce', 'cv_product_nonce');
        ?>
        <label for="cv_product_price"><?php echo esc_html__('Price ($)', 'contentvault-pro'); ?></label>
        <input type="number" id="cv_product_price" name="cv_product_price" value="<?php echo esc_attr($price); ?>" step="0.01" style="width: 100%; padding: 8px; margin: 10px 0;">
        <label for="cv_product_file"><?php echo esc_html__('Download File URL', 'contentvault-pro'); ?></label>
        <input type="url" id="cv_product_file" name="cv_product_file" value="<?php echo esc_attr($file_url); ?>" style="width: 100%; padding: 8px; margin: 10px 0;">
        <?php
    }

    public function render_content_restrictions_meta_box($post) {
        $restriction_type = get_post_meta($post->ID, '_cv_restriction_type', true);
        $membership_tiers = get_post_meta($post->ID, '_cv_membership_tiers', true);
        wp_nonce_field('cv_restrictions_nonce', 'cv_restrictions_nonce');
        ?>
        <label for="cv_restriction_type"><?php echo esc_html__('Restriction Type', 'contentvault-pro'); ?></label>
        <select id="cv_restriction_type" name="cv_restriction_type" style="width: 100%; padding: 8px; margin: 10px 0;">
            <option value="none" <?php selected($restriction_type, 'none'); ?>><?php echo esc_html__('None', 'contentvault-pro'); ?></option>
            <option value="members_only" <?php selected($restriction_type, 'members_only'); ?>><?php echo esc_html__('Members Only', 'contentvault-pro'); ?></option>
            <option value="tier_specific" <?php selected($restriction_type, 'tier_specific'); ?>><?php echo esc_html__('Specific Tiers', 'contentvault-pro'); ?></option>
        </select>
        <label for="cv_membership_tiers"><?php echo esc_html__('Allowed Tiers', 'contentvault-pro'); ?></label>
        <input type="text" id="cv_membership_tiers" name="cv_membership_tiers" value="<?php echo esc_attr($membership_tiers); ?>" placeholder="gold, silver, platinum" style="width: 100%; padding: 8px; margin: 10px 0;">
        <?php
    }

    public function save_post_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (isset($_POST['cv_product_nonce']) && wp_verify_nonce($_POST['cv_product_nonce'], 'cv_product_nonce')) {
            update_post_meta($post_id, '_cv_product_price', floatval($_POST['cv_product_price'] ?? 0));
            update_post_meta($post_id, '_cv_product_file', esc_url($_POST['cv_product_file'] ?? ''));
        }

        if (isset($_POST['cv_restrictions_nonce']) && wp_verify_nonce($_POST['cv_restrictions_nonce'], 'cv_restrictions_nonce')) {
            update_post_meta($post_id, '_cv_restriction_type', sanitize_text_field($_POST['cv_restriction_type'] ?? 'none'));
            update_post_meta($post_id, '_cv_membership_tiers', sanitize_text_field($_POST['cv_membership_tiers'] ?? ''));
        }
    }

    public function filter_post_content($content) {
        if (is_singular('post') && is_main_query()) {
            $restriction_type = get_post_meta(get_the_ID(), '_cv_restriction_type', true);
            if ($restriction_type === 'members_only' && !$this->is_member(get_current_user_id())) {
                return '<p><strong>' . esc_html__('This content is for members only.', 'contentvault-pro') . '</strong></p>' . do_shortcode('[contentvault_paywall post_id="' . get_the_ID() . '"]');
            }
        }
        return $content;
    }

    public function render_paywall_shortcode($atts) {
        $atts = shortcode_atts(array('post_id' => get_the_ID()), $atts);
        $post_id = intval($atts['post_id']);

        if ($this->is_member(get_current_user_id())) {
            return '';
        }

        ob_start();
        ?>
        <div class="cv-paywall" style="background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">
            <h3><?php echo esc_html__('Premium Content', 'contentvault-pro'); ?></h3>
            <p><?php echo esc_html__('Join our membership to access this content.', 'contentvault-pro'); ?></p>
            <button class="cv-membership-button" onclick="alert('Membership signup coming soon')" style="background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;"><?php echo esc_html__('Join Now', 'contentvault-pro'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_products_shortcode() {
        $products = get_posts(array('post_type' => 'cv_product', 'numberposts' => -1));
        ob_start();
        ?>
        <div class="cv-products" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
            <?php foreach ($products as $product) :
                $price = get_post_meta($product->ID, '_cv_product_price', true);
                ?>
                <div class="cv-product" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <?php if (has_post_thumbnail($product->ID)) : ?>
                        <div style="margin-bottom: 10px;"><?php echo get_the_post_thumbnail($product->ID, 'medium', array('style' => 'width: 100%; height: auto; border-radius: 5px;')); ?></div>
                    <?php endif; ?>
                    <h3><?php echo esc_html($product->post_title); ?></h3>
                    <p><?php echo wp_kses_post(wp_trim_words($product->post_content, 15)); ?></p>
                    <p style="font-size: 24px; color: #28a745; margin: 10px 0;"><strong>$<?php echo esc_html(number_format($price, 2)); ?></strong></p>
                    <button class="cv-purchase-btn" data-product-id="<?php echo intval($product->ID); ?>" style="background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%;"><?php echo esc_html__('Purchase', 'contentvault-pro'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_member_content_shortcode($atts) {
        if (!$this->is_member(get_current_user_id())) {
            return '<p>' . esc_html__('You must be a member to see this content.', 'contentvault-pro') . '</p>';
        }
        return '';
    }

    public function handle_product_purchase() {
        check_ajax_referer('cv_purchase_nonce', 'nonce');
        $product_id = intval($_POST['product_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$user_id || !$product_id) {
            wp_send_json_error('Invalid request');
        }

        global $wpdb;
        $price = get_post_meta($product_id, '_cv_product_price', true);

        $wpdb->insert(
            $wpdb->prefix . 'cv_purchases',
            array(
                'user_id' => $user_id,
                'product_id' => $product_id,
                'amount' => $price,
                'transaction_id' => 'TXN_' . time(),
            ),
            array('%d', '%d', '%f', '%s')
        );

        wp_send_json_success(array('message' => 'Purchase successful'));
    }

    public function load_stripe_js() {
        if (is_page() || is_single()) {
            wp_enqueue_script('stripe', 'https://js.stripe.com/v3/', array(), '3.0', true);
        }
    }

    public function enqueue_admin_scripts($hook_suffix) {
        wp_enqueue_style('cv-admin-style', CONTENTVAULT_PRO_URL . 'css/admin.css', array(), CONTENTVAULT_PRO_VERSION);
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('cv-frontend-style', CONTENTVAULT_PRO_URL . 'css/frontend.css', array(), CONTENTVAULT_PRO_VERSION);
        wp_enqueue_script('cv-frontend-script', CONTENTVAULT_PRO_URL . 'js/frontend.js', array('jquery'), CONTENTVAULT_PRO_VERSION, true);
        wp_localize_script('cv-frontend-script', 'cvProData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cv_purchase_nonce'),
        ));
    }

    private function is_member($user_id) {
        if (!$user_id) return false;
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}cv_memberships WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        return !empty($result);
    }
}

ContentVaultPro::getInstance();
?>