<?php
/*
Plugin Name: WP Coupon Vault
Description: Create, manage, and display exclusive coupons and deals for your audience.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/

if (!defined('ABSPATH')) exit;

// Register custom post type for coupons
define('WP_COUPON_VAULT_VERSION', '1.0');

class WPCouponVault {
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_box'));
        add_action('save_post', array($this, 'save_coupon_meta'));
        add_shortcode('coupon_vault', array($this, 'display_coupons_shortcode'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function register_coupon_post_type() {
        $args = array(
            'public' => true,
            'label' => 'Coupons',
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-tag',
            'show_in_rest' => true,
        );
        register_post_type('wpcv_coupon', $args);
    }

    public function add_coupon_meta_box() {
        add_meta_box(
            'wpcv_coupon_meta',
            'Coupon Details',
            array($this, 'render_coupon_meta_box'),
            'wpcv_coupon',
            'normal',
            'high'
        );
    }

    public function render_coupon_meta_box($post) {
        wp_nonce_field('wpcv_save_coupon_meta', 'wpcv_coupon_nonce');
        $code = get_post_meta($post->ID, '_wpcv_code', true);
        $expiry = get_post_meta($post->ID, '_wpcv_expiry', true);
        $url = get_post_meta($post->ID, '_wpcv_url', true);
        $store = get_post_meta($post->ID, '_wpcv_store', true);
        ?>
        <p>
            <label for="wpcv_code">Coupon Code:</label>
            <input type="text" id="wpcv_code" name="wpcv_code" value="<?php echo esc_attr($code); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="wpcv_expiry">Expiry Date (YYYY-MM-DD):</label>
            <input type="date" id="wpcv_expiry" name="wpcv_expiry" value="<?php echo esc_attr($expiry); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="wpcv_url">Affiliate URL:</label>
            <input type="url" id="wpcv_url" name="wpcv_url" value="<?php echo esc_attr($url); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="wpcv_store">Store Name:</label>
            <input type="text" id="wpcv_store" name="wpcv_store" value="<?php echo esc_attr($store); ?>" style="width: 100%;" />
        </p>
        <?php
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['wpcv_coupon_nonce']) || !wp_verify_nonce($_POST['wpcv_coupon_nonce'], 'wpcv_save_coupon_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array('wpcv_code', 'wpcv_expiry', 'wpcv_url', 'wpcv_store');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_wpcv_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function display_coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'store' => '',
        ), $atts);

        $args = array(
            'post_type' => 'wpcv_coupon',
            'posts_per_page' => $atts['limit'],
            'meta_query' => array(),
        );

        if (!empty($atts['store'])) {
            $args['meta_query'][] = array(
                'key' => '_wpcv_store',
                'value' => $atts['store'],
                'compare' => 'LIKE'
            );
        }

        $coupons = new WP_Query($args);
        $output = '<div class="wpcv-coupon-list">';
        while ($coupons->have_posts()) {
            $coupons->the_post();
            $code = get_post_meta(get_the_ID(), '_wpcv_code', true);
            $expiry = get_post_meta(get_the_ID(), '_wpcv_expiry', true);
            $url = get_post_meta(get_the_ID(), '_wpcv_url', true);
            $store = get_post_meta(get_the_ID(), '_wpcv_store', true);
            $output .= '<div class="wpcv-coupon">
                <h3>' . get_the_title() . '</h3>
                <p><strong>Store:</strong> ' . esc_html($store) . '</p>
                <p><strong>Coupon Code:</strong> <span class="wpcv-code">' . esc_html($code) . '</span></p>
                <p><strong>Expires:</strong> ' . esc_html($expiry) . '</p>
                <a href="' . esc_url($url) . '" target="_blank" class="wpcv-claim-btn">Claim Deal</a>
            </div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    }

    public function add_admin_menu() {
        add_menu_page(
            'Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'wpcv-dashboard',
            array($this, 'admin_dashboard'),
            'dashicons-tag',
            20
        );
    }

    public function admin_dashboard() {
        echo '<div class="wrap"><h1>Coupon Vault Dashboard</h1><p>Manage your coupons from the Coupons section in the sidebar.</p></div>';
    }

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_wpcv-dashboard' !== $hook) return;
        wp_enqueue_style('wpcv-admin-style', plugin_dir_url(__FILE__) . 'admin.css');
    }
}

new WPCouponVault();

// Activation hook
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
