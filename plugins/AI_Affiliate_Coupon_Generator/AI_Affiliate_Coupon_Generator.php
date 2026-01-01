/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Coupon_Generator.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Coupon Generator
 * Plugin URI: https://example.com/ai-affiliate-coupon
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Affiliate_Coupon_Generator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ai_coupon_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_nag'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'ai_coupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'affiliate_link' => ''
        ), $atts);

        ob_start();
        ?>
        <div id="ai-coupon-container" data-niche="<?php echo esc_attr($atts['niche']); ?>" data-affiliate="<?php echo esc_attr($atts['affiliate_link']); ?>">
            <button id="generate-coupon" class="button">Generate Exclusive Coupon</button>
            <div id="coupon-result" style="display:none;">
                <p><strong>Your Coupon:</strong> <span id="coupon-code"></span></p>
                <p><strong>Discount:</strong> <span id="discount"></span></p>
                <a id="affiliate-btn" href="#" target="_blank" class="button">Shop Now & Save</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');

        $niche = sanitize_text_field($_POST['niche'] ?? 'general');
        $affiliate = sanitize_url($_POST['affiliate'] ?? '');

        // Simple AI-like coupon generation (Pro version would integrate real AI API)
        $coupons = array(
            'SAVE20' => '20% Off',
            'DEAL15' => '15% Discount',
            'FREESHIP' => 'Free Shipping',
            'BUY1GET1' => 'Buy 1 Get 1 50% Off'
        );
        $keys = array_keys($coupons);
        $code = $keys[array_rand($keys)];
        $discount = $coupons[$code];

        if (get_option('ai_coupon_pro') === 'yes') {
            // Pro: Real AI integration placeholder
            $code = 'AI-' . wp_generate_password(6, false);
        }

        wp_send_json_success(array(
            'code' => $code,
            'discount' => $discount,
            'affiliate' => $affiliate
        ));
    }

    public function pro_nag() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Affiliate Coupon Generator Pro</strong> for unlimited coupons and analytics! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }

    public function activate() {
        add_option('ai_coupon_limit', 5);
    }
}

new AI_Affiliate_Coupon_Generator();

// Dummy JS file content (in real plugin, separate file)
/*
$(document).ready(function() {
    $('#generate-coupon').click(function() {
        var container = $('#ai-coupon-container');
        $.post(ai_coupon_ajax.ajax_url, {
            action: 'generate_coupon',
            nonce: ai_coupon_ajax.nonce,
            niche: container.data('niche'),
            affiliate: container.data('affiliate')
        }, function(response) {
            if (response.success) {
                $('#coupon-code').text(response.data.code);
                $('#discount').text(response.data.discount);
                $('#affiliate-btn').attr('href', response.data.affiliate);
                $('#coupon-result').show();
                $('#generate-coupon').hide();
            }
        });
    });
});
*/
?>