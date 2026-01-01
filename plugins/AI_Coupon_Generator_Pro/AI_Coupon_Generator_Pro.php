/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost conversions.
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
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('aicg_pro_version') !== 'active') {
            add_action('wp_footer', array($this, 'free_footer_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('aicg-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'amazon',
            'category' => 'electronics',
            'limit' => 3
        ), $atts);

        $coupons = $this->generate_coupons($atts['affiliate'], $atts['category'], $atts['limit']);
        return $this->render_coupons($coupons);
    }

    private function generate_coupons($affiliate, $category, $limit) {
        // Simulate AI generation (Pro version uses real AI API)
        $samples = array(
            array('code' => 'SAVE20', 'desc' => '20% off Electronics', 'link' => 'https://amazon.com/deal1?tag=youraffiliate', 'expires' => date('Y-m-d', strtotime('+30 days'))),
            array('code' => 'DEAL15', 'desc' => '15% off Gadgets', 'link' => 'https://amazon.com/deal2?tag=youraffiliate', 'expires' => date('Y-m-d', strtotime('+20 days'))),
            array('code' => 'FLASH10', 'desc' => 'Flash Sale 10% off', 'link' => 'https://amazon.com/deal3?tag=youraffiliate', 'expires' => date('Y-m-d', strtotime('+7 days')))
        );
        return array_slice($samples, 0, $limit);
    }

    private function render_coupons($coupons) {
        $output = '<div class="ai-coupon-container">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="ai-coupon-card">';
            $output .= '<h4>' . esc_html($coupon['desc']) . '</h4>';
            $output .= '<span class="coupon-code">' . esc_html($coupon['code']) . '</span>';
            $output .= '<a href="' . esc_url($coupon['link']) . '" class="coupon-btn" target="_blank">Grab Deal</a>';
            $output .= '<small>Expires: ' . esc_html($coupon['expires']) . '</small>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function admin_menu() {
        add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('aicg_affiliate_tag', sanitize_text_field($_POST['affiliate_tag']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $tag = get_option('aicg_affiliate_tag', '');
        echo '<div class="wrap"><h1>AI Coupon Generator Settings</h1>';
        echo '<form method="post"><table class="form-table">';
        echo '<tr><th>Affiliate Tag</th><td><input type="text" name="affiliate_tag" value="' . esc_attr($tag) . '" /></td></tr>';
        echo '</table><p><input type="submit" name="submit" class="button-primary" value="Save Settings" /></p></form>';
        echo '<h2>Upgrade to Pro</h2><p>Unlock unlimited coupons, AI generation, analytics for $49/year.</p>';
        echo '</div>';
    }

    public function free_footer_notice() {
        echo '<div id="aicg-pro-upsell" style="position:fixed;bottom:10px;right:10px;background:#0073aa;color:white;padding:10px;border-radius:5px;z-index:9999;font-size:12px;">';
        echo 'Upgrade to <strong>AI Coupon Pro</strong> for unlimited features! <a href="' . admin_url('options-general.php?page=ai-coupon') . '" style="color:#fff;">Learn More</a>';
        echo '</div>';
    }

    public function activate() {
        add_option('aicg_installed', time());
    }
}

new AICouponGenerator();

// Inline CSS for simplicity
add_action('wp_head', function() { ?>
<style>
.ai-coupon-container { display: flex; flex-wrap: wrap; gap: 15px; max-width: 800px; margin: 20px auto; }
.ai-coupon-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); flex: 1 1 250px; }
.ai-coupon-card h4 { margin: 0 0 10px; font-size: 16px; }
.coupon-code { display: block; font-size: 24px; font-weight: bold; background: rgba(255,255,255,0.2); padding: 10px; border-radius: 5px; margin: 10px 0; }
.coupon-btn { display: inline-block; background: #ff6b6b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
.coupon-btn:hover { background: #ff5252; }
#aicg-pro-upsell:hover { background: #005a87; }
</style>
<?php });