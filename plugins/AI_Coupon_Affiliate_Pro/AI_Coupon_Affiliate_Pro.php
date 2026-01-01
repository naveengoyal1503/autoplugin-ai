/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: AI-powered coupon and affiliate manager with auto-generation, tracking, and display.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_generate_coupon', [$this, 'ajax_generate_coupon']);
        add_action('wp_ajax_nopriv_generate_coupon', [$this, 'ajax_generate_coupon']);
        add_shortcode('ai_coupon_box', [$this, 'coupon_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (get_option('aicoupon_pro') !== 'pro') {
            add_action('admin_notices', [$this, 'pro_notice']);
        }
        wp_localize_script('jquery', 'aicoupon_ajax', ['ajaxurl' => admin_url('admin-ajax.php')]);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                $(".generate-coupon").click(function() {
                    var store = $("input[name=\'store\']").val();
                    $.post(aicoupon_ajax.ajaxurl, {action: "generate_coupon", store: store}, function(data) {
                        $("#coupon-output").html("<strong>Code: " + data.code + "</strong> <small>Save " + data.discount + "% - Affiliate: " + data.afflink + "</small>");
                    });
                });
            });
        ');
    }

    public function ajax_generate_coupon() {
        $store = sanitize_text_field($_POST['store'] ?? 'Generic');
        $codes = ['SAVE20', 'DEAL30', 'PRO50', 'FLASH25'];
        $discounts = [20, 30, 50, 25];
        $code = $codes[array_rand($codes)];
        $discount = $discounts[array_rand($discounts)];
        $afflink = 'https://affiliate.example.com/?ref=' . md5($store);
        wp_send_json(['code' => $code, 'discount' => $discount . '%', 'afflink' => $afflink]);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(['store' => 'Amazon'], $atts);
        ob_start();
        echo '<div class="ai-coupon-box" style="border:1px solid #ddd; padding:20px; margin:10px 0;">
                <h4>Coupon for ' . esc_html($atts['store']) . '</h4>
                <input type="text" name="store" value="' . esc_attr($atts['store']) . '" style="width:100%; margin-bottom:10px;">
                <button class="generate-coupon button" style="background:#0073aa; color:white; border:none; padding:10px;">Generate Coupon</button>
                <div id="coupon-output" style="margin-top:10px; font-size:18px;"></div>
              </div>';
        return ob_get_clean();
    }

    public function activate() {
        add_option('aicoupon_pro', 'free');
        flush_rewrite_rules();
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Coupon Affiliate Pro</strong> for AI-powered coupons and analytics!</p></div>';
    }
}

new AICouponAffiliatePro();

// Pro check function (simplified)
function is_aicoupon_pro() {
    return get_option('aicoupon_pro') === 'pro';
}
?>