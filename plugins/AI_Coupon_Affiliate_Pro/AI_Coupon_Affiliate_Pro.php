/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator with affiliate tracking for WordPress sites.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_ids', sanitize_text_field($_POST['affiliate_ids']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $affiliate_ids = get_option('ai_coupon_affiliate_ids', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate IDs (comma-separated)</th>
                        <td><input type="text" name="affiliate_ids" value="<?php echo esc_attr($affiliate_ids); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation for $49/year. <a href="https://example.com/pro">Buy Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'amount' => '20',
        ), $atts);

        $coupons = $this->generate_coupons($atts['niche'], $atts['amount']);
        $affiliate_ids = get_option('ai_coupon_affiliate_ids', '');

        ob_start();
        ?>
        <div id="ai-coupon-widget" class="ai-coupon-pro" data-niche="<?php echo esc_attr($atts['niche']); ?>">
            <h3>Exclusive Deals & Coupons</h3>
            <div class="coupons-list">
                <?php foreach ($coupons as $coupon): ?>
                    <div class="coupon-item">
                        <h4><?php echo esc_html($coupon['title']); ?></h4>
                        <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                        <p>Save: <?php echo esc_html($coupon['discount']); ?> | Expires: <?php echo esc_html($coupon['expires']); ?></p>
                        <a href="<?php echo esc_url($coupon['link'] . (strpos($coupon['link'], '?') ? '&' : '?') . 'ref=' . $affiliate_ids); ?}" class="coupon-btn" target="_blank">Shop Now & Save</a>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="pro-upsell">Upgrade to Pro for AI-powered real-time coupons!</p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($niche, $count) {
        // Demo coupons - Pro version uses OpenAI API
        $demo_coupons = array(
            array('title' => 'Amazon 25% Off Electronics', 'code' => 'SAVE25', 'discount' => '25%', 'expires' => '2026-02-01', 'link' => 'https://amazon.com'),
            array('title' => 'Shopify $50 Credit', 'code' => 'WP50', 'discount' => '$50', 'expires' => '2026-03-01', 'link' => 'https://shopify.com'),
            array('title' => 'Hostinger 70% Off Hosting', 'code' => 'HOST70', 'discount' => '70%', 'expires' => '2026-01-15', 'link' => 'https://hostinger.com'),
        );
        return array_slice($demo_coupons, 0, $count);
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Assets would be base64 or inline in production single-file, but for demo, assume external
// Inline CSS
/*
.ai-coupon-pro { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
.coupon-item { margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa; }
.coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
.pro-upsell { font-style: italic; color: #666; text-align: center; }
*/
// Inline JS
/*
document.addEventListener('DOMContentLoaded', function() {
    const widgets = document.querySelectorAll('.ai-coupon-pro');
    widgets.forEach(widget => {
        widget.addEventListener('click', '.coupon-btn', function(e) {
            // Track click
            gtag('event', 'coupon_click', {'niche': widget.dataset.niche});
        });
    });
});
*/