/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: Automatically generates and displays personalized, affiliate-tracked coupon codes.
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
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('aicg_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(document).ready(function($) { $(".generate-coupon").click(function() { var btn = $(this); btn.prop("disabled", true).text("Generating..."); $.post(ajaxurl, {action: "generate_coupon", product: $("#product").val()}, function(data) { btn.prop("disabled", false).text("Generate New"); $("#coupon-code").text(data.code); $("#coupon-link").attr("href", data.link); $("#coupon-display").show(); }); }); });');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product' => 'default-product',
            'affiliate' => 'your-affiliate-id',
            'discount' => '20'
        ), $atts);

        $product = sanitize_text_field($atts['product']);
        $affiliate = sanitize_text_field($atts['affiliate']);
        $discount = intval($atts['discount']);

        ob_start();
        ?>
        <div class="ai-coupon-generator" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9;">
            <h3>🔥 Get Your Exclusive <strong><?php echo esc_html($product); ?></strong> Coupon!</h3>
            <p>Save up to <strong><?php echo $discount; ?>% OFF</strong> instantly.</p>
            <input type="hidden" id="product" value="<?php echo esc_attr($product); ?>">
            <button class="generate-coupon button button-primary" style="padding: 10px 20px; font-size: 16px;">Generate My Coupon</button>
            <div id="coupon-display" style="display: none; margin-top: 20px;">
                <div style="background: #fff; padding: 15px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p><strong>Your Coupon Code:</strong><br><span id="coupon-code" style="font-size: 24px; color: #007cba; font-weight: bold; letter-spacing: 2px;"></span></p>
                    <p><a id="coupon-link" href="#" target="_blank" class="button button-large button-success" style="background: #46b450; color: white; text-decoration: none;">🛒 Redeem Now & Save</a></p>
                    <small style="color: #666;">Affiliate links help support this tool. Thanks!</small>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        $product = sanitize_text_field($_POST['product'] ?? 'default');
        $code = 'SAVE' . $product . rand(1000, 9999);
        $link = 'https://example-shop.com/' . $product . '?coupon=' . $code . '&aff=' . get_option('aicg_affiliate_id', 'your-id');
        wp_send_json(array('code' => $code, 'link' => $link));
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>AI Coupon Generator Pro:</strong> Upgrade to unlock unlimited coupons, analytics, and custom branding! <a href="https://example.com/pro" target="_blank">Get Pro Now</a></p></div>';
    }

    public function activate() {
        add_option('aicg_affiliate_id', 'your-affiliate-id');
        add_option('aicg_pro', 'no');
    }
}

new AICouponGenerator();

// Pro upsell page
add_action('admin_menu', function() {
    add_options_page('AI Coupon Pro', 'Coupon Pro', 'manage_options', 'ai-coupon-pro', function() {
        echo '<div class="wrap"><h1>Upgrade to Pro</h1><p>Limited to 10 coupons/day? Get <strong>unlimited</strong> features for $49/year!</p><a href="https://example.com/buy-pro" class="button button-primary button-large">Buy Pro Now</a></div>';
    });
});