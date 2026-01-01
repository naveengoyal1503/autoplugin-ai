/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: AI-powered coupon generator for affiliate marketing. Free version with pro upgrade.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponAffiliatePro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
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
        add_options_page(
            'AI Coupon Pro Settings',
            'AI Coupon Pro',
            'manage_options',
            'ai-coupon-pro',
            array($this, 'settings_page')
        );
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
                        <th>OpenAI API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and premium templates for $49/year. <a href="https://example.com/pro" target="_blank">Buy Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'general',
            'count' => 3
        ), $atts);

        $api_key = get_option('ai_coupon_api_key');
        $affiliate_id = get_option('ai_coupon_affiliate_id');

        if (empty($api_key) || empty($affiliate_id)) {
            return '<p><strong>Pro Feature:</strong> Enter API key and affiliate ID in settings for AI coupons.</p>';
        }

        // Simulate AI generation (replace with real OpenAI API call in Pro)
        $coupons = array(
            array('code' => 'SAVE20', 'desc' => '20% off on electronics', 'link' => "https://affiliate.com?coupon=SAVE20&ref={$affiliate_id}"),
            array('code' => 'DEAL50', 'desc' => '50% off clothing', 'link' => "https://affiliate.com?coupon=DEAL50&ref={$affiliate_id}"),
            array('code' => 'FREESHIP', 'desc' => 'Free shipping sitewide', 'link' => "https://affiliate.com?coupon=FREESHIP&ref={$affiliate_id}")
        );

        $output = '<div class="ai-coupon-container">';
        foreach (array_slice($coupons, 0, intval($atts['count'])) as $coupon) {
            $output .= '<div class="coupon-item">';
            $output .= '<h4>' . esc_html($coupon['code']) . '</h4>';
            $output .= '<p>' . esc_html($coupon['desc']) . '</p>';
            $output .= '<a href="' . esc_url($coupon['link']) . '" class="coupon-btn" target="_blank">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

AICouponAffiliatePro::get_instance();

// Pro upsell notice
function ai_coupon_pro_notice() {
    if (!get_option('ai_coupon_api_key')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Coupon Affiliate Pro</strong> for AI-generated coupons and analytics! <a href="' . admin_url('options-general.php?page=ai-coupon-pro') . '">Settings</a> | <a href="https://example.com/pro" target="_blank">Buy Pro</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_pro_notice');

// Minimal CSS
/* Add to assets/style.css */
/* .ai-coupon-container { display: flex; flex-wrap: wrap; gap: 1rem; } .coupon-item { border: 1px solid #ddd; padding: 1rem; border-radius: 5px; flex: 1 1 300px; } .coupon-btn { background: #0073aa; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 3px; } */

// Minimal JS
/* Add to assets/script.js */
/* jQuery(document).ready(function($) { $('.coupon-btn').on('click', function() { $(this).text('Copied!'); }); }); */