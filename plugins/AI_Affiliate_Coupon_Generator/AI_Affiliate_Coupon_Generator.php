<?php
/**
 * Plugin Name: AI Affiliate Coupon Generator
 * Plugin URI: https://example.com/ai-affiliate-coupon
 * Description: Automatically generates and displays personalized affiliate coupon codes with AI-optimized descriptions to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-affiliate-coupon
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIAffiliateCouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_coupon', array($this, 'save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'save_coupon'));
        add_shortcode('ai_coupon_display', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-affiliate-coupon', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('ai-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Affiliate Coupons', 'AI Coupons', 'manage_options', 'ai-affiliate-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_pro', isset($_POST['pro_version']) ? 'activated' : '');
        }
        $pro = get_option('ai_coupon_pro');
        echo '<div class="wrap"><h1>AI Affiliate Coupon Generator</h1><form method="post">';
        echo '<p><label>Enter License Key for Pro: <input type="text" name="pro_version" /></label> <input type="submit" name="submit" value="Activate Pro" class="button-primary" /></p></form>';
        if ($pro) {
            echo '<p><strong>Pro Version Activated! Unlimited coupons and AI features unlocked.</strong></p>';
        }
        echo '<p>Use shortcode [ai_coupon_display] to show coupons on any page/post.</p>';
        echo '</div>';
    }

    public function save_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        $coupons = get_option('ai_coupons', array());
        $new_coupon = array(
            'code' => sanitize_text_field($_POST['code']),
            'description' => sanitize_textarea_field($_POST['description']),
            'affiliate_link' => esc_url_raw($_POST['link']),
            'discount' => sanitize_text_field($_POST['discount']),
            'expires' => sanitize_text_field($_POST['expires'])
        );
        $coupons[] = $new_coupon;
        if (count($coupons) > 5 && !get_option('ai_coupon_pro')) {
            wp_send_json_error('Upgrade to Pro for unlimited coupons!');
        }
        update_option('ai_coupons', $coupons);
        wp_send_json_success('Coupon saved!');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('ai_coupons', array());
        if (get_option('ai_coupon_pro')) {
            $coupons[] = $this->generate_ai_coupon();
        }
        ob_start();
        echo '<div class="ai-coupon-container">';
        foreach ($coupons as $coupon) {
            $ai_desc = $this->ai_optimize_description($coupon['description']);
            echo '<div class="coupon-box">';
            echo '<h3>' . esc_html($coupon['code']) . ' - ' . esc_html($coupon['discount']) . ' OFF</h3>';
            echo '<p>' . esc_html($ai_desc) . '</p>';
            echo '<p>Expires: ' . esc_html($coupon['expires']) . '</p>';
            echo '<a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank" class="coupon-btn" rel="nofollow">Get Deal Now (Affiliate Link)</a>';
            echo '</div>';
        }
        echo '<p class="pro-upsell">' . (!$pro ? 'Upgrade to Pro for AI-generated coupons & unlimited storage!' : '') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    private function generate_ai_coupon() {
        $templates = array(
            array('code' => 'SAVE20', 'discount' => '20%', 'expires' => '2026-12-31', 'link' => 'https://example-affiliate.com/?ref=yourid'),
            array('code' => 'DEAL50', 'discount' => '50% Off', 'expires' => '2026-06-30', 'link' => 'https://example-affiliate.com/?ref=yourid')
        );
        return $templates[array_rand($templates)];
    }

    private function ai_optimize_description($desc) {
        // Simple AI-like optimization: capitalize, add urgency
        return strtoupper(substr($desc, 0, 1)) . substr($desc, 1) . ' - Limited time! Claim now to save big.';
    }

    public function activate() {
        add_option('ai_coupons', array());
    }
}

new AIAffiliateCouponGenerator();

// Dummy assets (in real plugin, create assets folder)
/*
Create folders: /assets/script.js with:

jQuery(document).ready(function($) {
    $('#add-coupon').click(function() {
        $.post(ajax_object.ajax_url, {
            action: 'save_coupon',
            nonce: ajax_object.nonce,
            code: $('#coupon-code').val(),
            description: $('#description').val(),
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Coupon_Generator.php
            link: $('#link').val(),
            discount: $('#discount').val(),
            expires: $('#expires').val()
        }, function(response) {
            alert(response.data);
        });
    });
});

Create /assets/style.css:
.ai-coupon-container { max-width: 600px; }
.coupon-box { border: 1px solid #ddd; padding: 20px; margin: 10px 0; background: #f9f9f9; }
.coupon-btn { background: #ff6600; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.pro-upsell { color: #0073aa; font-weight: bold; }
*/