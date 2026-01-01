/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/ai-coupon-affiliate-pro
 * Description: AI-powered coupon and affiliate manager for WordPress monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_section', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ai-coupon-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliates', sanitize_textarea_field($_POST['affiliates']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $affiliates = get_option('ai_coupon_affiliates', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON format: {"brand":"url"})</th>
                        <td><textarea name="affiliates" rows="10" class="large-text"><?php echo esc_textarea($affiliates); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Usage:</strong> Use shortcode <code>[ai_coupon_section niche="fashion"]</code> to generate coupons.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
        ), $atts);

        $affiliates = json_decode(get_option('ai_coupon_affiliates', '{}'), true);
        $coupons = array(
            array('code' => 'SAVE20', 'brand' => 'Example Brand', 'link' => isset($affiliates['example']) ? $affiliates['example'] : '#'),
            array('code' => 'DEAL50', 'brand' => 'Shop Now', 'link' => isset($affiliates['shop']) ? $affiliates['shop'] : '#'),
        );

        // Pro feature: AI generation (mocked for free version)
        if (get_option('ai_coupon_api_key') && function_exists('curl_init')) {
            // Mock AI call - in pro, integrate OpenAI for dynamic coupons
            $coupons[] = array('code' => 'AI' . rand(1000,9999), 'brand' => ucfirst($atts['niche']) . ' Deal', 'link' => '#');
        }

        ob_start();
        ?>
        <div class="ai-coupon-section">
            <h3>Exclusive <?php echo esc_html($atts['niche']); ?> Coupons</h3>
            <div class="coupons-grid">
                <?php foreach ($coupons as $coupon): ?>
                <div class="coupon-card">
                    <h4><?php echo esc_html($coupon['brand']); ?></h4>
                    <span class="coupon-code"><?php echo esc_html($coupon['code']); ?></span>
                    <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank">Get Deal (Affiliate)</a>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="pro-upsell">Upgrade to Pro for AI-generated personalized coupons & analytics!</p>
        </div>
        <style>
        .ai-coupon-section { max-width: 800px; margin: 20px 0; }
        .coupons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; text-align: center; background: #f9f9f9; }
        .coupon-code { font-size: 2em; font-weight: bold; color: #e74c3c; display: block; margin: 10px 0; }
        .coupon-btn { display: inline-block; background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .pro-upsell { text-align: center; font-style: italic; color: #666; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Pro upsell notice
function ai_coupon_admin_notice() {
    if (!get_option('ai_coupon_api_key')) {
        echo '<div class="notice notice-info"><p>Unlock AI-powered coupons with <strong>AI Coupon Affiliate Pro</strong> upgrade! <a href="options-general.php?page=ai-coupon-pro">Settings</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_admin_notice');

// Prevent direct access to files
if (!defined('AICAP_VERSION')) {
    define('AICAP_VERSION', '1.0.0');
}
?>