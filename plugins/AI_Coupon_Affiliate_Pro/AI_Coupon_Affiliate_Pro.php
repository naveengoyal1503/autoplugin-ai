/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: Generate and manage exclusive AI-powered coupon codes for affiliate marketing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('AICAP_VERSION', '1.0.0');
define('AICAP_PATH', plugin_dir_path(__FILE__));
define('AICAP_URL', plugin_dir_url(__FILE__));

// Main plugin class
class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('aicoupon_display', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('aicap-style', AICAP_URL . 'style.css', array(), AICAP_VERSION);
        wp_enqueue_script('aicap-script', AICAP_URL . 'script.js', array('jquery'), AICAP_VERSION, true);
    }

    public function admin_menu() {
        add_menu_page(
            __('AI Coupons', 'ai-coupon-affiliate-pro'),
            __('AI Coupons', 'ai-coupon-affiliate-pro'),
            'manage_options',
            'ai-coupon-pro',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['aicap_save'])) {
            update_option('aicap_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'ai-coupon-affiliate-pro') . '</p></div>';
        }
        $coupons = get_option('aicap_coupons', "Coupon1|Brand1|20% OFF|https://affiliate.link1\nCoupon2|Brand2|10% OFF|https://affiliate.link2");
        ?>
        <div class="wrap">
            <h1><?php _e('AI Coupon Affiliate Pro', 'ai-coupon-affiliate-pro'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Coupons (Format: Code|Brand|Discount|Affiliate Link)', 'ai-coupon-affiliate-pro'); ?></th>
                        <td><textarea name="coupons" rows="10" cols="50" class="large-text"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(__('Save Coupons', 'ai-coupon-affiliate-pro'), 'primary', 'aicap_save'); ?>
            </form>
            <h2><?php _e('Shortcode', 'ai-coupon-affiliate-pro'); ?></h2>
            <p><?php _e('Use <code>[aicoupon_display]</code> to display random coupons on any page.', 'ai-coupon-affiliate-pro'); ?></p>
            <p><strong><?php _e('Pro Features:', 'ai-coupon-affiliate-pro'); ?></strong> <?php _e('Unlimited coupons, AI generation, click tracking. Upgrade now!', 'ai-coupon-affiliate-pro'); ?></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons = explode('\n', get_option('aicap_coupons', ''));
        if (empty($coupons)) return '';
        $coupon = $coupons[array_rand($coupons)];
        $parts = explode('|', $coupon);
        if (count($parts) !== 4) return '';
        list($code, $brand, $discount, $link) = $parts;
        ob_start();
        ?>
        <div class="aicoupon-card">
            <h3><?php echo esc_html($brand); ?> - <?php echo esc_html($discount); ?></h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($code); ?></code></p>
            <a href="<?php echo esc_url($link); ?}" target="_blank" class="aicoupon-btn">Shop Now & Save</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('aicap_coupons')) {
            update_option('aicap_coupons', "WELCOME20|ExampleStore|20% OFF|https://your-affiliate-link.com\nSAVE10|BrandX|10% OFF|https://affiliate.link2");
        }
    }
}

new AICouponAffiliatePro();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.aicoupon-card { border: 2px solid #0073aa; padding: 20px; border-radius: 10px; background: #f9f9f9; text-align: center; max-width: 300px; }
.aicoupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.aicoupon-btn:hover { background: #005a87; }
</style>
<?php });

// Pro upsell notice
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>' . sprintf(__('Upgrade to <a href="https://example.com/pro">AI Coupon Pro</a> for AI generation & analytics!', 'ai-coupon-affiliate-pro'), '') . '</p></div>';
    }
});