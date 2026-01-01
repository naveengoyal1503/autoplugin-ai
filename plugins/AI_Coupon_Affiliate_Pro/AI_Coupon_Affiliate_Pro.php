/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate deal generator with tracking.
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
        add_shortcode('ai_coupon_deals', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
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
            <h1>AI Coupon Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>AI API Key (Pro Feature)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Network ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation, unlimited deals, and analytics for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'limit' => 5
        ), $atts);

        $coupons = $this->generate_coupons($atts['category'], $atts['limit']);
        ob_start();
        ?>
        <div class="ai-coupon-container">
            <?php foreach ($coupons as $coupon): ?>
                <div class="coupon-deal">
                    <h3><?php echo esc_html($coupon['title']); ?></h3>
                    <p><?php echo esc_html($coupon['description']); ?></p>
                    <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
                    <a href="<?php echo esc_url($coupon['link']); ?}" class="affiliate-link" target="_blank" rel="nofollow">Get Deal <span class="affiliate-track"><?php echo get_option('ai_coupon_affiliate_id', ''); ?></span></a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($category, $limit) {
        // Simulate AI generation (Pro: integrate OpenAI); demo data
        $demo_coupons = array(
            array('title' => '50% Off Hosting', 'description' => 'Exclusive deal on premium hosting.', 'code' => 'HOST50', 'link' => 'https://example.com/hosting?aff=' . get_option('ai_coupon_affiliate_id', '')),
            array('title' => 'Free Theme Trial', 'description' => 'Try premium WordPress themes free.', 'code' => 'THEMEFREE', 'link' => 'https://example.com/themes?aff=' . get_option('ai_coupon_affiliate_id', '')),
            array('title' => '20% Off Plugins', 'description' => 'Discount on essential WP plugins.', 'code' => 'PLUGIN20', 'link' => 'https://example.com/plugins?aff=' . get_option('ai_coupon_affiliate_id', ''))
        );
        return array_slice($demo_coupons, 0, $limit);
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Pro upsell notice
function ai_coupon_pro_notice() {
    if (!get_option('ai_coupon_pro_dismissed')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Coupon Pro</strong> features: Real AI generation, unlimited coupons, analytics. <a href="https://example.com/pro" target="_blank">Upgrade now ($49/year)</a> | <a href="?dismiss=pro">Dismiss</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_pro_notice');

if (isset($_GET['dismiss']) && $_GET['dismiss'] === 'pro') {
    update_option('ai_coupon_pro_dismissed', true);
    wp_redirect(admin_url());
    exit;
}

// Minimal CSS/JS placeholders (create assets folder with empty files)
/*
Create /assets/style.css:
.ai-coupon-container { max-width: 600px; }
.coupon-deal { border: 1px solid #ddd; padding: 20px; margin: 10px 0; }
.coupon-code { background: #f0f0f0; padding: 10px; font-family: monospace; }
.affiliate-link { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; }

Create /assets/script.js:
jQuery(document).ready(function($) {
    $('.affiliate-link').on('click', function() {
        // Track clicks
        console.log('Affiliate click tracked');
    });
});
*/