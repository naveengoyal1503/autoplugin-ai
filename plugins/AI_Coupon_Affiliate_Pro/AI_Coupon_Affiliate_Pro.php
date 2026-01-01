/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/ai-coupon-pro
 * Description: AI-powered coupon generator for affiliate marketing. Generate, track, and monetize custom coupons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-pro', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-pro', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ai_coupon_generate'])) {
            $affiliate_link = sanitize_text_field($_POST['affiliate_link']);
            $offer_desc = sanitize_text_field($_POST['offer_desc']);
            $coupon_code = $this->generate_coupon_code();
            update_option('ai_coupon_last_code', $coupon_code);
            update_option('ai_coupon_link', $affiliate_link);
            echo '<div class="notice notice-success"><p>Coupon generated: ' . $coupon_code . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="affiliate_link" value="<?php echo esc_attr(get_option('ai_coupon_link', '')); ?>" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th>Offer Description</th>
                        <td><textarea name="offer_desc" class="large-text"><?php echo esc_textarea(get_option('ai_coupon_offer', '')); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Generate AI Coupon', 'primary', 'ai_coupon_generate'); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited generations, analytics, and more for $49/year.</p>
        </div>
        <?php
    }

    private function generate_coupon_code() {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = 'SAVE' . substr(str_shuffle($chars), 0, 8);
        return $code;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 'default'
        ), $atts);

        $coupon_code = get_option('ai_coupon_last_code', $this->generate_coupon_code());
        $link = get_option('ai_coupon_link', '#');
        $offer = get_option('ai_coupon_offer', 'Exclusive Deal!');

        ob_start();
        ?>
        <div class="ai-coupon-pro-container">
            <div class="coupon-card">
                <h3><?php echo esc_html($offer); ?></h3>
                <div class="coupon-code"><?php echo esc_html($coupon_code); ?></div>
                <a href="<?php echo esc_url($link . (strpos($link, '?') !== false ? '&' : '?') . 'coupon=' . $coupon_code); ?>" class="coupon-btn" target="_blank">Redeem Now (Affiliate)</a>
                <p class="coupon-tracking" data-coupon="<?php echo esc_attr($coupon_code); ?>">Tracked clicks: <span class="click-count">0</span></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Pro upsell notice
function ai_coupon_pro_notice() {
    if (!get_option('ai_coupon_pro_dismissed')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Coupon Affiliate Pro</strong> for advanced AI features and analytics! <a href="https://example.com/pro" target="_blank">Get Pro ($49)</a> | <a href="?ai_coupon_dismiss=1">Dismiss</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_pro_notice');

if (isset($_GET['ai_coupon_dismiss'])) {
    update_option('ai_coupon_pro_dismissed', 1);
}