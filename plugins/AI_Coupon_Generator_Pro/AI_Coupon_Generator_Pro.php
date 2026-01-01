/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: Automatically generates unique, personalized coupon codes and affiliate deals for WordPress sites, boosting conversions and revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-generator-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponGeneratorPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        }
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_ai-coupon-pro') return;
        wp_enqueue_script('jquery');
    }

    public function admin_menu() {
        add_menu_page(
            'AI Coupon Pro',
            'AI Coupon Pro',
            'manage_options',
            'ai-coupon-pro',
            array($this, 'admin_page'),
            'dashicons-cart',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['generate_coupon'])) {
            $this->generate_coupon();
        }
        echo '<div class="wrap"><h1>AI Coupon Generator Pro</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Coupon Prefix</th>
                    <td><input type="text" name="prefix" value="SAVE" /></td>
                </tr>
                <tr>
                    <th>Discount %</th>
                    <td><input type="number" name="discount" value="20" /></td>
                </tr>
            </table>
            <p><input type="submit" name="generate_coupon" class="button-primary" value="Generate Coupon" /></p>
        </form>';
        if (isset($_POST['coupon_code'])) {
            echo '<div class="notice notice-success"><p><strong>New Coupon:</strong> ' . esc_html($_POST['coupon_code']) . '<br>Affiliate Link: <a href="' . esc_url($_POST['affiliate_link']) . '" target="_blank">' . esc_url($_POST['affiliate_link']) . '</a></p></div>';
        }
        echo '<h2>Usage</h2><p>Use shortcode: [ai_coupon code="YOURCODE" link="AFFLINK"]</p></div>';
    }

    private function generate_coupon() {
        $prefix = sanitize_text_field($_POST['prefix']);
        $discount = intval($_POST['discount']);
        $code = $prefix . wp_generate_uuid4() . substr(md5(uniqid()), 0, 4);
        $affiliate_link = 'https://example-affiliate.com/?coupon=' . $code . '&aff=yourid';
        echo '<input type="hidden" name="coupon_code" value="' . $code . '" />
              <input type="hidden" name="affiliate_link" value="' . $affiliate_link . '" />';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => 'SAVE10',
            'link' => '#',
            'discount' => '10%'
        ), $atts);

        ob_start();
        echo '<div style="border: 2px dashed #007cba; padding: 20px; text-align: center; background: #f9f9f9;">
                <h3>Exclusive Coupon!</h3>
                <p><strong>Code: ' . esc_html($atts['code']) . '</strong></p>
                <p>Save <strong>' . esc_html($atts['discount']) . ' OFF</strong></p>
                <a href="' . esc_url($atts['link']) . '" target="_blank" class="button" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none;">Redeem Now</a>
              </div>';
        return ob_get_clean();
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponGeneratorPro();

// Pro upsell notice
function ai_coupon_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Coupon Generator Pro</strong> for AI-powered coupons, analytics, and unlimited generation! <a href="https://example.com/pro" target="_blank">Get Pro ($49/yr)</a></p></div>';
}
add_action('admin_notices', 'ai_coupon_pro_notice');
?>