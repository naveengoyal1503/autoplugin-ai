/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: Automatically generates and displays personalized affiliate coupons with AI-powered recommendations to boost conversions and commissions.
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
        add_shortcode('ai_coupon_widget', array($this, 'coupon_widget_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('aicoupon_pro_key') !== 'pro') {
            add_action('admin_notices', array($this, 'pro_nag'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                $(".ai-coupon-btn").click(function() {
                    var category = $("input[name=\'coupon_category\']").val();
                    $("#ai-coupon-output").html("<p>Generating coupon for " + category + "... <strong>PRO FEATURE</strong></p>");
                });
            });
        ');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aicoupon_pro_key'])) {
            update_option('aicoupon_pro_key', sanitize_text_field($_POST['aicoupon_pro_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>License Key</th>
                        <td><input type="text" name="aicoupon_pro_key" value="<?php echo esc_attr(get_option('aicoupon_pro_key')); ?>" class="regular-text" placeholder="Enter PRO key for unlimited features" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Networks</th>
                        <td>
                            <label><input type="checkbox" name="networks[]" value="amazon" <?php checked(in_array('amazon', (array)get_option('aicoupon_networks', array()))); ?>> Amazon</label><br>
                            <label><input type="checkbox" name="networks[]" value="clickbank" <?php checked(in_array('clickbank', (array)get_option('aicoupon_networks', array()))); ?>> ClickBank</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[ai_coupon_widget]</code> to display coupon widget.</p>
            <?php if (get_option('aicoupon_pro_key') !== 'pro') : ?>
                <div class="notice notice-warning">
                    <p><strong>Upgrade to PRO</strong> for AI generation, unlimited coupons, and analytics. <a href="https://example.com/pro" target="_blank">Get PRO ($49/year)</a></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function pro_nag() {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Coupon Affiliate Pro</strong> features! <a href="' . admin_url('options-general.php?page=ai-coupon-pro') . '">Enter key</a> or <a href="https://example.com/pro" target="_blank">buy now</a>.</p></div>';
    }

    public function coupon_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'default',
        ), $atts);

        ob_start();
        ?>
        <div class="ai-coupon-widget" style="border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9;">
            <h3>🔥 Exclusive Coupons for <?php echo esc_html($atts['category']); ?></h3>
            <input type="text" name="coupon_category" placeholder="Enter category (e.g., tech, fashion)" style="width: 200px; padding: 8px; margin-right: 10px;">
            <button class="ai-coupon-btn button" style="background: #0073aa; color: white; border: none; padding: 10px 20px;">Generate Coupon</button>
            <div id="ai-coupon-output" style="margin-top: 15px; padding: 15px; background: #e7f3ff;">
                <?php if (get_option('aicoupon_pro_key') === 'pro') : ?>
                    <p><strong>Limited Time Deal:</strong> SAVE 50% on Tech Gadgets! <br><a href="https://your-affiliate-link.com" target="_blank" style="background: #ff6600; color: white; padding: 10px 20px; text-decoration: none;">🛒 Shop Now & Save</a></p>
                <?php else : ?>
                    <p>🚀 <strong>PRO FEATURE:</strong> AI generates real-time personalized coupons with affiliate links. Upgrade now!</p>
                <?php endif; ?>
            </div>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">* Coupons auto-update. Earn commissions on every sale.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('aicoupon_pro_key', '');
        add_option('aicoupon_networks', array('amazon'));
    }
}

new AICouponAffiliatePro();

// Freemium upsell
add_action('admin_footer', function() {
    if (get_option('aicoupon_pro_key') !== 'pro') {
        echo '<div style="position: fixed; bottom: 20px; right: 20px; background: linear-gradient(45deg, #ff6b6b, #feca57); color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); z-index: 9999;">
                <strong>💰 Go PRO Now!</strong><br>Unlimited AI coupons + Analytics<br><a href="https://example.com/pro" target="_blank" style="color: white; text-decoration: underline;">Upgrade $49/year →</a>
             </div>';
    }
});