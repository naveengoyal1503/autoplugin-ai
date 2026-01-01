/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator with affiliate tracking for WordPress.
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
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $affiliate_id = get_option('ai_coupon_affiliate_id', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>AI API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text" /></td>
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
            'discount' => '10',
        ), $atts);

        $affiliate_id = get_option('ai_coupon_affiliate_id', 'your-affiliate-id');
        $code = 'SAVE' . $atts['discount'] . wp_generate_password(4, false);
        $link = 'https://affiliate-partner.com/?coupon=' . $code . '&aff=' . $affiliate_id;

        // Simulate AI generation (Pro feature)
        if (get_option('ai_coupon_api_key')) {
            $code = $this->generate_ai_coupon($atts['niche']);
        }

        ob_start();
        ?>
        <div class="ai-coupon-box">
            <h3>Exclusive Coupon: <strong><?php echo esc_html($code); ?></strong></h3>
            <p>Save <?php echo esc_html($atts['discount']); ?>% on your purchase!</p>
            <a href="<?php echo esc_url($link); ?>" class="coupon-btn" target="_blank">Get Deal Now</a>
            <small>Coupon auto-generated for <?php echo esc_html($atts['niche']); ?> shoppers.</small>
        </div>
        <style>
        .ai-coupon-box { border: 2px solid #28a745; padding: 20px; border-radius: 10px; background: #f8fff9; text-align: center; max-width: 300px; }
        .coupon-btn { background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; }
        .coupon-btn:hover { background: #218838; }
        </style>
        <?php
        return ob_get_clean();
    }

    private function generate_ai_coupon($niche) {
        // Pro AI integration placeholder (uses OpenAI or similar)
        $prompts = array(
            'general' => 'SALE20XYZ',
            'tech' => 'TECH15ABC',
            'fashion' => 'FASH25DEF'
        );
        return isset($prompts[$niche]) ? $prompts[$niche] : 'AI' . wp_generate_password(6, false);
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Dummy JS file content (in real plugin, separate file)
/*
function trackCouponClick(couponCode) {
    gtag('event', 'coupon_click', {'coupon': couponCode});
    console.log('Coupon clicked: ' + couponCode);
}
*/
// Dummy CSS
/*
.ai-coupon-box { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
*/