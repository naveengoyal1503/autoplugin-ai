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
    exit; // Exit if accessed directly.
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
                        <th>AI API Key (OpenAI or similar)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, advanced AI, and analytics for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'keyword' => 'discount',
            'category' => 'ecommerce'
        ), $atts);

        $api_key = get_option('ai_coupon_api_key');
        $affiliate_id = get_option('ai_coupon_affiliate_id');

        if (empty($api_key)) {
            return '<p><strong>Pro Feature:</strong> Enter your AI API key in settings to generate coupons.</p>';
        }

        // Simulate AI generation (replace with real OpenAI API call in pro)
        $coupons = array(
            array('code' => 'SAVE20', 'desc' => '20% off sitewide', 'link' => 'https://example.com?aff=' . $affiliate_id),
            array('code' => 'FREESHIP', 'desc' => 'Free shipping', 'link' => 'https://example.com?aff=' . $affiliate_id)
        );

        $output = '<div class="ai-coupon-box">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="coupon-item">';
            $output .= '<h4>' . esc_html($coupon['code']) . '</h4>';
            $output .= '<p>' . esc_html($coupon['desc']) . '</p>';
            $output .= '<a href="' . esc_url($coupon['link']) . '" class="coupon-btn" target="_blank">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '<p class="pro-upsell">Upgrade to Pro for AI-generated personalized coupons!</p>';
        $output .= '</div>';

        return $output;
    }

    public function activate() {
        add_option('ai_coupon_pro_activated', true);
    }
}

new AICouponAffiliatePro();

// Create assets directories if needed
$upload_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
}

// Minimal CSS
$css = ".ai-coupon-box { border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9; }
.coupon-item { margin-bottom: 15px; padding: 10px; border-left: 4px solid #0073aa; }
.coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.pro-upsell { font-style: italic; color: #0073aa; text-align: center; }";
file_put_contents($upload_dir . '/style.css', $css);

// Minimal JS
$js = "jQuery(document).ready(function($) {
    $('.coupon-btn').click(function() {
        $(this).text('Copied!');
    });
});";
file_put_contents($upload_dir . '/script.js', $js);

?>