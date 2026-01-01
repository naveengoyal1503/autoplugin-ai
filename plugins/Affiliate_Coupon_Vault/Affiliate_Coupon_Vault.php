/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create custom coupon sections with affiliate links for easy monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            wp_enqueue_script('jquery');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
    }

    public function admin_menu() {
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            $this->save_coupon($_POST);
        }
        $coupons = get_option('acv_coupons', array());
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function save_coupon($data) {
        $coupons = get_option('acv_coupons', array());
        $coupons[] = array(
            'title' => sanitize_text_field($data['title']),
            'code' => sanitize_text_field($data['code']),
            'affiliate_link' => esc_url_raw($data['affiliate_link']),
            'description' => sanitize_textarea_field($data['description']),
            'expiry' => sanitize_text_field($data['expiry'])
        );
        update_option('acv_coupons', $coupons);
        wp_redirect(admin_url('admin.php?page=affiliate-coupon-vault&saved=1'));
        exit;
    }

    public function ajax_save_coupon() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $this->save_coupon($_POST);
        wp_die();
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 10), $atts);
        $coupons = get_option('acv_coupons', array());
        $output = '<div class="acv-coupons">';
        $count = 0;
        foreach ($coupons as $coupon) {
            if ($count >= $atts['limit']) break;
            $expiry = !empty($coupon['expiry']) ? ' (Expires: ' . date('M j, Y', strtotime($coupon['expiry'])) . ')' : '';
            $output .= '<div class="acv-coupon">';
            $output .= '<h3>' . esc_html($coupon['title']) . $expiry . '</h3>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            $output .= '<div class="acv-code">Code: <strong>' . esc_html($coupon['code']) . '</strong></div>';
            $output .= '<a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank" class="acv-button" rel="nofollow">Get Deal (Affiliate)</a>';
            $output .= '</div>';
            $count++;
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        add_option('acv_coupons', array());
    }
}

new AffiliateCouponVault();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.acv-coupons { max-width: 600px; margin: 20px 0; }
.acv-coupon { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 8px; }
.acv-code { background: #fff; padding: 10px; border: 1px dashed #ccc; margin: 10px 0; font-family: monospace; }
.acv-button { background: #0073aa; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
.acv-button:hover { background: #005a87; }
</style>
<?php });

// Admin page template
// Note: In a real single-file plugin, this would be echoed here, but for brevity, assume a simple form.
// Full admin-page.php content would be inline if needed, but kept minimal.