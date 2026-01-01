/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost affiliate commissions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-coupon-generator
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-generator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'amazon',
            'category' => 'electronics',
            'amount' => '10',
        ), $atts);

        $coupon_code = $this->generate_coupon_code($atts['category']);
        $discount = $atts['amount'] . '% OFF';
        $link = $this->get_affiliate_link($atts['affiliate'], $coupon_code);

        ob_start();
        ?>
        <div class="ai-coupon-box">
            <h3>Exclusive Deal: <?php echo esc_html($discount); ?> on <?php echo esc_html($atts['category']); ?></h3>
            <p>Use code: <strong><?php echo esc_html($coupon_code); ?></strong></p>
            <a href="<?php echo esc_url($link); ?}" class="ai-coupon-btn" target="_blank">Shop Now & Save</a>
            <p class="ai-coupon-expire">Limited time offer!</p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($category) {
        $prefixes = array('SAVE', 'DEAL', 'COUPON');
        $suffixes = array('NOW', '2026', 'PRO');
        return strtoupper($prefixes[array_rand($prefixes)] . rand(10,99) . $suffixes[array_rand($suffixes)]);
    }

    private function get_affiliate_link($network, $code) {
        $links = array(
            'amazon' => 'https://amazon.com/?tag=youraffiliateid&coupon=' . $code,
            'generic' => 'https://example.com/deal?code=' . $code,
        );
        return isset($links[$network]) ? $links[$network] : $links['generic'];
    }

    public function admin_menu() {
        add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $aff_id = get_option('ai_coupon_affiliate_id', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Your Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($aff_id); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI-powered dynamic coupons, unlimited generations, and premium integrations for $49/year.</p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponGenerator();

// Pro upsell notice
function ai_coupon_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Coupon Generator Pro</strong> for advanced AI features and unlimited coupons! <a href="https://example.com/pro" target="_blank">Get Pro Now</a></p></div>';
}
add_action('admin_notices', 'ai_coupon_admin_notice');

// Assets would be created separately: script.js with click tracking, style.css for styling
?>