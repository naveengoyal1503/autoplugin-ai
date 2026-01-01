/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator with affiliate tracking for WordPress monetization.
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
        add_shortcode('ai_coupon_deals', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_generate_coupons', array($this, 'ajax_generate_coupons'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('ai-coupon-js', 'aicoupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'category' => 'all'
        ), $atts);

        $coupons = get_transient('ai_coupons_' . md5(serialize($atts)));
        if (false === $coupons) {
            $coupons = $this->generate_sample_coupons($atts['count'], $atts['category']);
            set_transient('ai_coupons_' . md5(serialize($atts)), $coupons, HOUR_IN_SECONDS);
        }

        ob_start();
        echo '<div class="ai-coupon-container">';
        foreach ($coupons as $coupon) {
            echo '<div class="coupon-card">';
            echo '<h3>' . esc_html($coupon['title']) . '</h3>';
            echo '<p><strong>Code:</strong> ' . esc_html($coupon['code']) . '</p>';
            echo '<p><strong>Discount:</strong> ' . esc_html($coupon['discount']) . '</p>';
            echo '<a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank" class="coupon-btn" rel="nofollow">Shop Now & Save</a>';
            echo '<small>Expires: ' . esc_html($coupon['expires']) . '</small>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    private function generate_sample_coupons($count, $category) {
        $samples = array(
            array('title' => '50% Off Hosting', 'code' => 'HOST50', 'discount' => '50%', 'affiliate_link' => 'https://example.com/hosting?ref=yourid', 'expires' => '2026-03-01'),
            array('title' => 'Free Domain', 'code' => 'DOMAINFREE', 'discount' => 'Free Year', 'affiliate_link' => 'https://example.com/domain?ref=yourid', 'expires' => '2026-02-15'),
            array('title' => '20% Off Themes', 'code' => 'THEME20', 'discount' => '20%', 'affiliate_link' => 'https://example.com/themes?ref=yourid', 'expires' => '2026-01-31'),
            array('title' => 'Buy One Get One VPN', 'code' => 'VPNBOGO', 'discount' => 'BOGO', 'affiliate_link' => 'https://example.com/vpn?ref=yourid', 'expires' => '2026-02-28'),
            array('title' => '30% Off Email Marketing', 'code' => 'EMAIL30', 'discount' => '30%', 'affiliate_link' => 'https://example.com/email?ref=yourid', 'expires' => '2026-03-15'),
            array('title' => '15% Off Plugins', 'code' => 'PLUGIN15', 'discount' => '15%', 'affiliate_link' => 'https://example.com/plugins?ref=yourid', 'expires' => '2026-02-10')
        );
        shuffle($samples);
        return array_slice($samples, 0, $count);
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
                        <th>Your Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Usage:</strong> Use shortcode <code>[ai_coupon_deals count="5" category="hosting"]</code></p>
            <p><em>Upgrade to Pro for real AI generation and unlimited coupons!</em></p>
        </div>
        <?php
    }

    public function ajax_generate_coupons() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $count = intval($_POST['count']);
        $coupons = $this->generate_sample_coupons($count, 'all');
        wp_send_json_success($coupons);
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Pro upsell notice
function ai_coupon_pro_notice() {
    if (!get_option('ai_coupon_pro_dismissed')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Coupon Affiliate Pro</strong>: Real AI coupon generation, analytics & more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a> | <a href="?ai_coupon_dismiss=1">Dismiss</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_pro_notice');

if (isset($_GET['ai_coupon_dismiss'])) {
    update_option('ai_coupon_pro_dismissed', 1);
}