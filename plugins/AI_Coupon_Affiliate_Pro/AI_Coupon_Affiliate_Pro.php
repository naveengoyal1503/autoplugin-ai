/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate manager for WordPress. Generate personalized coupons, affiliate links, and deal pages to monetize your site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_box', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_style('ai-coupon-style');
    }

    public function admin_menu() {
        add_menu_page('AI Coupons', 'AI Coupons', 'manage_options', 'ai-coupon-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            update_option('ai_coupon_data', sanitize_text_field($_POST['coupon_data']));
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        $coupon_data = get_option('ai_coupon_data', 'SAVE 20% with AFF20 - Affiliate Link');
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon Text</th>
                        <td><textarea name="coupon_data" rows="3" cols="50"><?php echo esc_textarea($coupon_data); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="affiliate_link" value="<?php echo esc_attr(get_option('ai_affiliate_link', '')); ?>" size="50" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupon'); ?>
            </form>
            <p>Use shortcode: <code>[ai_coupon_box]</code></p>
            <p><strong>Pro Features:</strong> AI-generated coupons, analytics, unlimited deals. <a href="#pro">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $coupon_data = get_option('ai_coupon_data', 'Exclusive Deal: 50% OFF!');
        $aff_link = get_option('ai_affiliate_link', '#');
        ob_start();
        ?>
        <div class="ai-coupon-box">
            <div class="coupon-code"><?php echo esc_html($coupon_data); ?></div>
            <a href="<?php echo esc_url($aff_link); ?>" class="coupon-btn" target="_blank">Get Deal Now</a>
            <p class="coupon-cta">Limited time offer! Affiliate link tracks commissions.</p>
        </div>
        <style>
        .ai-coupon-box { background: #fff; border: 2px dashed #ff6b35; padding: 20px; text-align: center; max-width: 400px; margin: 20px auto; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .coupon-code { font-size: 24px; font-weight: bold; color: #333; margin-bottom: 15px; background: #f8f9fa; padding: 10px; border-radius: 5px; }
        .coupon-btn { display: inline-block; background: #ff6b35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.3s; }
        .coupon-btn:hover { background: #e55a2b; }
        .coupon-cta { font-size: 14px; color: #666; margin-top: 10px; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ai_coupon_data')) {
            update_option('ai_coupon_data', 'WELCOME20 - Get 20% Off Your First Purchase!');
        }
    }
}

new AICouponAffiliatePro();

// Freemium upsell notice
function ai_coupon_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>AI Coupon Pro:</strong> Unlock AI generation, analytics & more! <a href="https://example.com/pro">Upgrade for $49/year</a></p></div>';
}
add_action('admin_notices', 'ai_coupon_pro_notice');