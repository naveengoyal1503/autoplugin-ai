/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicoupon-generator
 * Description: AI-powered plugin that generates unique, personalized coupon codes to boost affiliate conversions and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Simple AI simulation using random generation
        if (!get_option('ai_coupon_api_key')) {
            add_option('ai_coupon_api_key', 'free-mode');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save'])) {
            update_option('ai_coupon_settings', $_POST['settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $settings = get_option('ai_coupon_settings', array('discount' => 10, 'aff_link' => ''));
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Default Discount %</th>
                        <td><input type="number" name="settings[discount}" value="<?php echo esc_attr($settings['discount']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="settings[aff_link]" value="<?php echo esc_attr($settings['aff_link']); ?>" style="width: 300px;" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="save" class="button-primary" value="Save Settings" /></p>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, real AI integration, analytics & more for $49/year!</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => 'Example Brand',
            'product' => 'Product',
            'expiry' => date('Y-m-d', strtotime('+30 days'))
        ), $atts);

        $settings = get_option('ai_coupon_settings', array('discount' => 10, 'aff_link' => ''));
        $code = $this->generate_coupon_code($atts['brand'], $atts['product']);

        ob_start();
        ?>
        <div class="ai-coupon-container">
            <h3>Exclusive Deal: <?php echo esc_html($atts['brand']); ?> - <?php echo esc_html($atts['product']); ?></h3>
            <div class="coupon-code">CODE: <strong><?php echo esc_html($code); ?></strong></div>
            <p>Save <strong><?php echo esc_html($settings['discount']); ?>%</strong> | Expires: <?php echo esc_html($atts['expiry']); ?></p>
            <?php if ($settings['aff_link']) : ?>
            <a href="<?php echo esc_url($settings['aff_link']); ?>" class="coupon-btn" target="_blank">Shop Now & Save</a>
            <?php endif; ?>
            <button class="copy-btn" onclick="copyCoupon('<?php echo esc_js($code); ?>')">Copy Code</button>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($brand, $product) {
        // Simulated AI generation: brand + product + random
        $random = wp_rand(1000, 9999);
        return strtoupper(substr($brand, 0, 3) . substr($product, 0, 3) . $random);
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponGenerator();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.ai-coupon-container { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
.coupon-code { font-size: 24px; color: #d63638; margin: 10px 0; }
.coupon-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px; }
.copy-btn { background: #46b450; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
</style>
<script>
function copyCoupon(code) {
    navigator.clipboard.writeText(code).then(() => alert('Coupon copied!'));
}
</script>
<?php });

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('ai_coupon_pro')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Coupon Generator Pro</strong> for advanced AI, analytics & unlimited coupons! <a href="https://example.com/pro">Get Pro Now</a></p></div>';
    }
});