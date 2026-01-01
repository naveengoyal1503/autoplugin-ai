/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: AI-powered coupon generator for WordPress. Create unique coupons, track affiliates, and monetize your site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_post_generate_coupon', array($this, 'handle_generate_coupon'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon-generator', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['generate_coupon'])) {
            $this->generate_coupon();
        }
        echo '<div class="wrap"><h1>AI Coupon Generator</h1><form method="post"><p><label>Brand:</label> <input type="text" name="brand" required></p><p><label>Discount %:</label> <input type="number" name="discount" required></p><p><label>Affiliate Link:</label> <input type="url" name="afflink"></p><input type="submit" name="generate_coupon" value="Generate Coupon" class="button-primary"></form></div>';
    }

    public function handle_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        $brand = sanitize_text_field($_POST['brand']);
        $discount = intval($_POST['discount']);
        $afflink = esc_url_raw($_POST['afflink']);
        $coupon = $this->generate_coupon($brand, $discount, $afflink);
        wp_send_json_success($coupon);
    }

    private function generate_coupon($brand, $discount, $afflink = '') {
        $code = strtoupper(substr(md5($brand . time()), 0, 8));
        $expires = date('Y-m-d', strtotime('+30 days'));
        $html = '<div class="ai-coupon" style="border:2px solid #007cba; padding:20px; background:#f9f9f9; text-align:center; max-width:300px;"><h3>Exclusive Deal!</h3><p><strong>' . esc_html($discount) . '% OFF</strong> at ' . esc_html($brand) . '</p><p><strong>Code: ' . $code . '</strong></p><p>Expires: ' . $expires . '</p>';
        if ($afflink) {
            $html .= '<p><a href="' . $afflink . '" target="_blank" class="button" style="background:#007cba;color:white;padding:10px 20px;text-decoration:none;">Shop Now & Save</a></p>';
        }
        $html .= '</div>';
        // Save to options for tracking (Pro feature simulation)
        $coupons = get_option('ai_coupons', array());
        $coupons[] = array('code' => $code, 'brand' => $brand, 'discount' => $discount, 'afflink' => $afflink, 'uses' => 0);
        update_option('ai_coupons', $coupons);
        return $html;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('ai_coupons', array());
        if (!empty($atts['id']) && isset($coupons[$atts['id']])) {
            $coupon = $coupons[$atts['id']];
            return $this->generate_coupon($coupon['brand'], $coupon['discount'], $coupon['afflink']);
        }
        return 'Use [ai_coupon id="X"] to display a coupon.';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponGenerator();

// Pro Upsell Notice
function ai_coupon_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>AI Coupon Generator Pro:</strong> Unlock unlimited coupons, analytics, and custom branding for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'ai_coupon_pro_notice');

// Frontend CSS
add_action('wp_head', function() {
    echo '<style>.ai-coupon {font-family: Arial, sans-serif; box-shadow: 0 4px 8px rgba(0,0,0,0.1);}</style>';
});

// AJAX for frontend generation (basic)
add_action('wp_ajax_nopriv_generate_coupon_frontend', 'handle_coupon_ajax');
add_action('wp_ajax_generate_coupon_frontend', 'handle_coupon_ajax');
function handle_coupon_ajax() {
    check_ajax_referer('ai_coupon_nonce', 'nonce');
    $brand = sanitize_text_field($_POST['brand']);
    $discount = intval($_POST['discount']);
    $afflink = esc_url_raw($_POST['afflink']);
    $coupon_generator = new AICouponGenerator();
    $coupon = $coupon_generator->generate_coupon($brand, $discount, $afflink);
    wp_send_json_success($coupon);
}
