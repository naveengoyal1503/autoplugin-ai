/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate plugin for WordPress monetization.
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
        add_shortcode('ai_coupon_section', array($this, 'coupon_shortcode'));
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
            update_option('ai_coupon_affiliates', sanitize_textarea_field($_POST['affiliates']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $affiliates = get_option('ai_coupon_affiliates', "Amazon:your-affiliate-id\nEbay:your-ebay-id");
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro Feature)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="affiliates" rows="5" class="large-text"><?php echo esc_textarea($affiliates); ?></textarea><br />
                        Format: Network:affiliate-id (one per line)</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for unlimited coupons, analytics, and premium templates. <a href="https://example.com/pro" target="_blank">Get Pro Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'count' => 5
        ), $atts);

        $coupons = $this->generate_coupons($atts['niche'], $atts['count']);
        ob_start();
        ?>
        <div class="ai-coupon-section">
            <h3>Exclusive Deals & Coupons</h3>
            <div class="coupons-grid">
                <?php foreach ($coupons as $coupon): ?>
                    <div class="coupon-card">
                        <h4><?php echo esc_html($coupon['title']); ?></h4>
                        <p><?php echo esc_html($coupon['description']); ?></p>
                        <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
                        <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank">Shop Now & Save</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($niche, $count) {
        // Simulate AI generation (Pro: integrate OpenAI)
        $samples = array(
            array('title' => '50% Off Electronics', 'description' => 'Limited time deal on top gadgets.', 'code' => 'SAVE50', 'link' => $this->get_affiliate_link('Amazon', $niche)),
            array('title' => 'Free Shipping on Fashion', 'description' => 'Use code for all orders.', 'code' => 'FREESHIP', 'link' => $this->get_affiliate_link('Ebay', $niche)),
            array('title' => '20% Off Software', 'description' => 'Perfect for bloggers.', 'code' => 'WP20', 'link' => $this->get_affiliate_link('Amazon', $niche)),
            array('title' => 'Buy One Get One Free', 'description' => 'On selected items.', 'code' => 'BOGO', 'link' => $this->get_affiliate_link('Ebay', $niche)),
            array('title' => '$10 Off First Purchase', 'description' => 'New customer exclusive.', 'code' => 'WELCOME10', 'link' => $this->get_affiliate_link('Amazon', $niche))
        );
        return array_slice($samples, 0, $count);
    }

    private function get_affiliate_link($network, $niche) {
        $affiliates = get_option('ai_coupon_affiliates', '');
        $lines = explode("\n", $affiliates);
        foreach ($lines as $line) {
            list($net, $id) = explode(':', trim($line), 2);
            if (strtolower($net) === strtolower($network)) {
                return "https://" . strtolower($network) . ".com/deal?tag=" . $id . "&niche=" . urlencode($niche);
            }
        }
        return '#';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Pro upsell notice
function ai_coupon_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>AI Coupon Affiliate Pro</strong> full features: Real AI generation, analytics & more. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'ai_coupon_pro_notice');

// CSS
/* Add to assets/style.css */
/* .ai-coupon-section { max-width: 1200px; margin: 0 auto; padding: 20px; }
.coupons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; }
.coupon-code { background: #ff6b35; color: white; padding: 10px; font-weight: bold; text-align: center; margin: 10px 0; }
.coupon-btn { display: block; background: #007cba; color: white; text-decoration: none; padding: 12px; text-align: center; border-radius: 5px; } */

// JS
/* Add to assets/script.js */
/* jQuery(document).ready(function($) {
    $('.coupon-btn').on('click', function() {
        $(this).text('Copied! Shop Now');
    });
}); */