/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate manager that generates dynamic coupon sections to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
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
        add_shortcode('ai_coupon_section', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_save_coupon', array($this, 'save_coupon_ajax'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'save_coupon_ajax'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(document).ready(function($) { $(".coupon-generate").click(function() { var affiliateUrl = $("input[name=\'affiliate-url-" + $(this).data("id") + "\']").val(); if(affiliateUrl) { $("input[name=\'affiliate-url-" + $(this).data("id") + "\']").val(affiliateUrl + "?ref=" + $("input[name=\'ref-id\']").val()); } }); });');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Premium)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[ai_coupon_section]</code></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Exclusive Coupons',
            'count' => 5
        ), $atts);

        $coupons = get_option('ai_coupons', array(
            array('code' => 'SAVE20', 'desc' => '20% off on all items', 'affiliate' => ''),
            array('code' => 'DEAL10', 'desc' => '10% off first purchase', 'affiliate' => ''),
            array('code' => 'FREESHIP', 'desc' => 'Free shipping today', 'affiliate' => '')
        ));

        ob_start();
        echo '<div class="ai-coupon-section">';
        echo '<h3>' . esc_html($atts['title']) . '</h3>';
        echo '<ul>';
        foreach (array_slice($coupons, 0, intval($atts['count'])) as $index => $coupon) {
            $unique_id = uniqid();
            echo '<li class="coupon-item">';
            echo '<strong>' . esc_html($coupon['code']) . '</strong><br>';
            echo esc_html($coupon['desc']) . '<br>';
            echo '<input type="text" name="affiliate-url-' . $unique_id . '" placeholder="Enter affiliate URL" style="width:200px;">';
            echo '<input type="hidden" name="ref-id" value="yourrefid">';
            echo '<button type="button" class="button coupon-generate" data-id="' . $unique_id . '">Track Click</button>';
            echo '<p><small>Copy and share the tracked link for commissions.</small></p>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<style>.ai-coupon-section ul {list-style:none;padding:0;}.ai-coupon-section li {border:1px solid #ddd;margin:10px 0;padding:15px;background:#f9f9f9;}</style>';
        echo '</div>';
        return ob_get_clean();
    }

    public function save_coupon_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_coupon_nonce')) {
            wp_die('Security check failed');
        }
        $coupons = get_option('ai_coupons', array());
        $coupons[] = array(
            'code' => sanitize_text_field($_POST['code']),
            'desc' => sanitize_text_field($_POST['desc']),
            'affiliate' => esc_url_raw($_POST['affiliate'])
        );
        update_option('ai_coupons', $coupons);
        wp_send_json_success('Coupon saved!');
    }

    public function activate() {
        if (!get_option('ai_coupons')) {
            update_option('ai_coupons', array());
        }
    }
}

AICouponAffiliatePro::get_instance();

// Premium upsell notice
function ai_coupon_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock AI-generated coupons and advanced analytics with <strong>AI Coupon Affiliate Pro Premium</strong> for $49/year! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'ai_coupon_admin_notice');
?>