/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: AI-powered coupon code generator with affiliate link integration for WordPress monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
    private $api_key = 'your-openai-api-key-here'; // Replace with actual OpenAI key or settings
    private $is_pro = false; // Set to true for pro features

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('aicg_pro') === 'yes') {
            $this->is_pro = true;
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aicg-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aicg-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon-generator', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('aicg_api_key', sanitize_text_field($_POST['api_key']));
            update_option('aicg_pro', sanitize_text_field($_POST['is_pro']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('aicg_api_key', '');
        $is_pro = get_option('aicg_pro', 'no');
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" size="50" /></td>
                    </tr>
                    <tr>
                        <th>Pro Version</th>
                        <td><input type="checkbox" name="is_pro" value="yes" <?php checked($is_pro, 'yes'); ?> /> Enable Pro Features</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlimited coupons, analytics, custom templates. <a href="#" onclick="alert('Pro upgrade link here')">Buy Now ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => 'Generic',
            'discount' => '20',
            'affiliate' => '',
        ), $atts);

        if (!$this->is_pro && wp_count_posts('ai_coupon')->publish >= 5) {
            return '<p>Upgrade to Pro for unlimited coupons!</p>';
        }

        $coupon = $this->generate_coupon($atts['brand'], $atts['discount']);
        $link = $atts['affiliate'] ? $atts['affiliate'] : '#';

        ob_start();
        ?>
        <div class="ai-coupon-box pro-upgrade-<?php echo $this->is_pro ? 'active' : 'teaser'; ?>">
            <h3>Exclusive <?php echo esc_html($atts['brand']); ?> Deal!</h3>
            <p>Use code: <strong><?php echo esc_html($coupon); ?></strong> for <strong><?php echo esc_html($atts['discount']); ?>% OFF</strong></p>
            <a href="<?php echo esc_url($link); ?}" class="coupon-btn" target="_blank">Shop Now & Save</a>
            <?php if (!$this->is_pro): ?>
                <p><small><a href="<?php echo admin_url('options-general.php?page=ai-coupon-generator'); ?>">Upgrade to Pro</a> for more!</small></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon($brand, $discount) {
        // Simulate AI generation (replace with real OpenAI API call)
        $codes = array('SAVE20', 'DEAL25', 'COUPON30', 'FLASH40', 'PRO50');
        return strtoupper(substr(md5($brand . $discount . time()), 0, 8));
    }

    public function activate() {
        if (!wp_next_scheduled('aicg_daily_reset')) {
            wp_schedule_event(time(), 'daily', 'aicg_daily_reset');
        }
        add_option('aicg_usage_count', 0);
    }
}

new AICouponGenerator();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
    // Minimal CSS
    file_put_contents($upload_dir . '/style.css', ".ai-coupon-box { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; } .coupon-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; } .pro-upgrade-teaser { opacity: 0.7; }");
    // Minimal JS
    file_put_contents($upload_dir . '/script.js', "jQuery(document).ready(function($) { $('.coupon-btn').on('click', function() { $(this).text('Copied!'); }); });");
});

// Cleanup
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('aicg_daily_reset');
});