/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Generate exclusive affiliate coupons, create promo pages, and track commissions effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        add_action('init', array($this, 'register_post_type'));
        add_filter('pre_get_posts', array($this, 'custom_query'));
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function register_post_type() {
        $args = array(
            'public' => true,
            'label' => 'Coupons',
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'coupons'),
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-cart',
        );
        register_post_type('acv_coupon', $args);
    }

    public function admin_menu() {
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['generate_coupon'])) {
            $this->generate_coupon();
        }
        echo '<div class="wrap"><h1>Affiliate Coupon Vault</h1><form method="post">';
        echo '<p><label>Affiliate Link: <input type="url" name="aff_link" required></label></p>';
        echo '<p><label>Code: <input type="text" name="code" required></label></p>';
        echo '<p><label>Discount %: <input type="number" name="discount" required></label></p>';
        echo '<p><label>Expires (days): <input type="number" name="expires" value="30"></label></p>';
        echo '<input type="submit" name="generate_coupon" value="Generate Coupon" class="button-primary">';
        echo '</form>';
        echo '<h2>Pro Upgrade: Unlock unlimited coupons & analytics for $49/year</h2>';
        echo '<a href="https://example.com/pro" class="button button-large">Upgrade Now</a></div>';
    }

    private function generate_coupon() {
        $post = array(
            'post_title' => sanitize_text_field($_POST['code']),
            'post_content' => 'Use code <strong>' . sanitize_text_field($_POST['code']) . '</strong> for ' . intval($_POST['discount']) . '% off! <a href="' . esc_url($_POST['aff_link']) . '" target="_blank">Shop Now (Affiliate)</a>',
            'post_type' => 'acv_coupon',
            'post_status' => 'publish',
        );
        $id = wp_insert_post($post);
        if ($id) {
            update_post_meta($id, 'aff_link', esc_url($_POST['aff_link']));
            update_post_meta($id, 'discount', intval($_POST['discount']));
            update_post_meta($id, 'code', sanitize_text_field($_POST['code']));
            update_post_meta($id, 'expires', time() + (intval($_POST['expires']) * 86400));
            echo '<div class="notice notice-success"><p>Coupon generated! Use [acv_coupon id="' . $id . '"] on any page.</p></div>';
        }
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $id = intval($atts['id']);
        $expires = get_post_meta($id, 'expires', true);
        if ($expires && $expires < time()) {
            return '<p>This coupon has expired.</p>';
        }
        $link = get_post_meta($id, 'aff_link', true);
        $code = get_post_meta($id, 'code', true);
        $discount = get_post_meta($id, 'discount', true);
        $content = get_post_field('post_content', $id);
        return '<div class="acv-coupon" style="border:2px solid #0073aa;padding:20px;background:#f9f9f9;"><h3>Exclusive Deal!</h3>' . $content . '<p><em>Limited time offer. Track your affiliate commissions automatically.</em></p></div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function admin_scripts($hook) {
        if ($hook != 'toplevel_page_acv-vault') return;
        wp_enqueue_script('acv-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0');
    }

    public function custom_query($query) {
        if (!is_admin() && $query->is_main_query() && $query->is_post_type_archive('acv_coupon')) {
            $query->set('posts_per_page', 12);
        }
    }
}

new AffiliateCouponVault();

// Freemium nag
add_action('admin_notices', function() {
    if (!get_option('acv_pro') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Upgrade to Pro for unlimited coupons, analytics, and auto-expiry tracking! <a href="https://example.com/pro">Get Pro ($49)</a></p></div>';
    }
});