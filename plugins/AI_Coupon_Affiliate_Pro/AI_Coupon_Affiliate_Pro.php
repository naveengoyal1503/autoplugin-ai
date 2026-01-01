/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate link generator for WordPress blogs. Earn commissions automatically.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) exit;

class AICouponAffiliatePro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
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
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-..."></td>
                    </tr>
                    <tr>
                        <th>Affiliate Program IDs (e.g., Amazon, etc.)</th>
                        <td><textarea name="affiliate_ids" class="large-text"><?php echo esc_textarea($affiliate_ids); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation, unlimited coupons, analytics. <a href="https://example.com/pro">Buy Now ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'count' => 3
        ), $atts);

        $coupons = $this->generate_coupons($atts['niche'], $atts['count']);
        ob_start();
        ?>
        <div class="ai-coupon-container">
            <h3>Exclusive Deals for You</h3>
            <?php foreach ($coupons as $coupon): ?>
                <div class="coupon-card">
                    <h4><?php echo esc_html($coupon['title']); ?></h4>
                    <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                    <p>Save: <?php echo esc_html($coupon['discount']); ?></p>
                    <a href="<?php echo esc_url($coupon['affiliate_link']); ?}" class="coupon-btn" target="_blank" rel="nofollow">Shop Now & Save</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($niche, $count) {
        // Freemium: Basic static coupons (Pro: AI-generated)
        $base_coupons = array(
            array('title' => '10% Off Electronics', 'code' => 'SAVE10', 'discount' => '10%', 'affiliate_link' => '#'),
            array('title' => 'Free Shipping', 'code' => 'FREESHIP', 'discount' => 'Free Shipping', 'affiliate_link' => '#'),
            array('title' => '20% Off Fashion', 'code' => 'FASHION20', 'discount' => '20%', 'affiliate_link' => '#')
        );

        // Simulate AI (Pro would call OpenAI API)
        $api_key = get_option('ai_coupon_api_key');
        if ($api_key && function_exists('curl_init')) {
            // Pro AI call placeholder
            $prompt = "Generate $count unique coupon codes for $niche niche with affiliate-friendly descriptions.";
            // Actual OpenAI call would go here
        }

        return array_slice($base_coupons, 0, $count);
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function admin_scripts($hook) {
        if ($hook !== 'settings_page_ai-coupon-pro') return;
        wp_enqueue_script('ai-coupon-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
    }
}

AICouponAffiliatePro::get_instance();

// Pro upsell notice
function ai_coupon_pro_notice() {
    if (!get_option('ai_coupon_api_key')) {
        echo '<div class="notice notice-info"><p>Unlock AI-powered coupons with <a href="' . admin_url('options-general.php?page=ai-coupon-pro') . '">AI Coupon Pro</a> ($49/year)!</p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_pro_notice');

// Assets placeholder - create these files
// assets/style.css, assets/script.js, assets/admin.js
?>