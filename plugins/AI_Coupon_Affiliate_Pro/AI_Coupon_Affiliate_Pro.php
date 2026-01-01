/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator with affiliate tracking for WordPress.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('ai_coupon_box', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('aicoupon_pro_key') && !function_exists('is_pro')) {
            // Pro check
        }
        add_menu_page('AI Coupons', 'AI Coupons', 'manage_options', 'ai-coupons', array($this, 'admin_page'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(document).ready(function($){ $(".generate-coupon").click(function(){ var store=$(this).data("store"); $.post(ajaxurl,{action:"generate_coupon",store:store},function(resp){ $("#coupon-output").html("<strong>Coupon: "+resp.code+"</strong> <br/>Save "+resp.discount+"% - Affiliate: "+resp.afflink); }); }); });');
    }

    public function ajax_generate_coupon() {
        $store = sanitize_text_field($_POST['store']);
        $coupons = array(
            'amazon' => array('code' => 'SAVE20', 'discount' => '20', 'afflink' => 'https://amazon.com/?tag=youraffiliate'),
            'shopify' => array('code' => 'SHOP15', 'discount' => '15', 'afflink' => 'https://shopify.com/?aff=yourid'),
            'walmart' => array('code' => 'WAL10', 'discount' => '10', 'afflink' => 'https://walmart.com/?aff=yourid')
        );
        $coupon = isset($coupons[$store]) ? $coupons[$store] : array('code' => 'DEMO10', 'discount' => '10', 'afflink' => '#');
        wp_send_json($coupon);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('store' => 'amazon'), $atts);
        ob_start();
        echo '<div class="ai-coupon-box"><h3>Grab Your ' . strtoupper($atts['store']) . ' Deal!</h3><button class="button generate-coupon" data-store="' . esc_attr($atts['store']) . '">Generate Coupon</button><div id="coupon-output"></div><p><a href="#" target="_blank">Shop Now (Affiliate Link)</a></p></div>';
        echo '<style>.ai-coupon-box { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; border-radius: 10px; background: #f9f9f9; text-align: center; }.ai-coupon-box .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }</style>';
        return ob_get_clean();
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>AI Coupon Affiliate Pro</h1><p>Upgrade to Pro for AI-generated custom coupons and analytics. <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p><h2>Usage</h2><p>Use shortcode: [ai_coupon_box store="amazon"]</p></div>';
    }

    public function activate() {
        add_option('aicoupon_version', '1.0.0');
    }
}

new AICouponAffiliatePro();

// Pro upsell notice
function aicoupon_admin_notice() {
    if (!get_option('aicoupon_pro_key')) {
        echo '<div class="notice notice-info"><p>Unlock AI features in <strong>AI Coupon Affiliate Pro</strong> - <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'aicoupon_admin_notice');