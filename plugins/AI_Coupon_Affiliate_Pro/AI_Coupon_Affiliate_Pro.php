/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: AI-powered coupon generator with affiliate tracking for WordPress sites.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_box', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', "
            jQuery(document).ready(function($) {
                $('.ai-coupon-generate').click(function() {
                    var store = $(this).data('store');
                    var affiliate = 'YOUR_AFFILIATE_ID'; // Replace with dynamic
                    var code = 'SAVE' + Math.floor(Math.random() * 9000 + 1000);
                    var expiry = new Date(Date.now() + 7*24*60*60*1000).toISOString().split('T');
                    $(this).closest('.ai-coupon-box').find('.coupon-code').text(code);
                    $(this).closest('.ai-coupon-box').find('.coupon-link').attr('href', 'https://store.com/?coupon=' + code + '&aff=' + affiliate);
                    $(this).hide();
                    $('.ai-coupon-reveal').show();
                });
            });
        ");
        wp_enqueue_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('ai_coupon_settings', 'ai_coupon_options');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ai_coupon_settings'); ?>
                <?php do_settings_sections('ai_coupon_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Affiliate ID</th>
                        <td><input type="text" name="ai_coupon_options[affiliate_id]" value="<?php echo esc_attr(get_option('ai_coupon_options')['affiliate_id'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Default Stores</th>
                        <td><textarea name="ai_coupon_options[stores]"><?php echo esc_textarea(get_option('ai_coupon_options')['stores'] ?? 'Amazon,Shopify,Walmart'); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
                <p><strong>Upgrade to Pro</strong> for AI generation, unlimited coupons, and analytics. <a href="#" onclick="alert('Pro upgrade: $49/year')">Get Pro</a></p>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'store' => 'Amazon',
            'discount' => '20%',
        ), $atts);

        $options = get_option('ai_coupon_options', array());
        $affiliate = $options['affiliate_id'] ?? 'demo';

        ob_start();
        ?>
        <div class="ai-coupon-box" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9;">
            <h3>Exclusive <?php echo esc_html($atts['store']); ?> Deal: <strong><?php echo esc_html($atts['discount']); ?> OFF</strong></h3>
            <p>Click to generate your unique coupon code!</p>
            <button class="button ai-coupon-generate" data-store="<?php echo esc_attr($atts['store']); ?>">Generate Coupon</button>
            <div style="display:none; margin-top:20px;">
                <p>Your Code: <span class="coupon-code"></span></p>
                <a href="#" class="button coupon-link" target="_blank">Shop Now & Save</a>
                <p>Expires soon! <small>Affiliate link tracks commissions.</small></p>
            </div>
        </div>
        <style>
        .ai-coupon-box { max-width: 400px; margin: 0 auto; }
        .ai-coupon-box .button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('ai_coupon_options', array());
    }
}

new AICouponAffiliatePro();

// Pro teaser
add_action('admin_notices', function() {
    if (!get_option('ai_coupon_pro_activated')) {
        echo '<div class="notice notice-info"><p>Unlock AI features in <strong>AI Coupon Affiliate Pro</strong> - <a href="options-general.php?page=ai-coupon-pro">Upgrade Now</a></p></div>';
    }
});