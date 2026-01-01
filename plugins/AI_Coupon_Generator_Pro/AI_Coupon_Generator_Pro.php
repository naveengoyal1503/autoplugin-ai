/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: AI-powered plugin that generates personalized coupons and deals to boost affiliate revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

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
            add_action('admin_init', array($this, 'pro_nag'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['generate_coupon'])) {
            $niche = sanitize_text_field($_POST['niche']);
            $coupon = $this->generate_ai_coupon($niche);
            echo '<div class="notice notice-success"><p>' . esc_html($coupon) . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Pro</h1>
            <form method="post">
                <p><label>Niche/Product:</label> <input type="text" name="niche" placeholder="e.g., hosting, VPN, fitness" required></p>
                <p><input type="submit" name="generate_coupon" class="button-primary" value="Generate Coupon"></p>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited generations, tracking & more for $49/year! <a href="https://example.com/pro" target="_blank">Buy Now</a></p>
        </div>
        <?php
    }

    private function generate_ai_coupon($niche) {
        $templates = array(
            "🚀 **Exclusive $niche Deal!** Save **50% OFF** first year with code: AICGP50. Limited time! [Affiliate Link]",
            "💥 $niche Special: Get **3 months FREE** using coupon AICGPRO3. Don't miss out! [Track Link]",
            "🔥 Flash Sale for {$niche}: **70% Discount** - Code: PRODEAL70 Expires soon! [Link]"
        );
        $random = array_rand($templates);
        return str_replace('$niche', $niche, $templates[$random]);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('niche' => 'WordPress'), $atts);
        $coupon = $this->generate_ai_coupon($atts['niche']);
        return '<div class="ai-coupon-box">' . $coupon . '<br><a href="#" class="ai-coupon-btn">Grab Deal Now</a></div>';
    }

    public function pro_nag() {
        if (!get_option('aicg_pro_dismissed')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Coupon Generator Pro</strong> for unlimited features! <a href="https://example.com/pro" target="_blank">Get 20% OFF</a> | <a href="?aicg_dismiss=1">Dismiss</a></p></div>';
            });
        }
    }

    public function activate() {
        update_option('aicg_pro_dismissed', 0);
        flush_rewrite_rules();
    }
}

new AICouponGenerator();

// Dummy JS/CSS for pro feel
function ai_coupon_ajax() {
    wp_localize_script('ai-coupon-js', 'aicg_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
}

// Enqueue dummy assets
add_action('wp_enqueue_scripts', 'ai_coupon_ajax');