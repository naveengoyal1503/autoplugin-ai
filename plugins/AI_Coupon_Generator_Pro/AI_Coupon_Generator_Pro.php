/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGeneratorPro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro');
        if (is_admin()) {
            return;
        }
        // Simulate AI coupon generation (in pro: integrate OpenAI API)
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'amazon',
            'category' => 'electronics',
            'limit' => 5
        ), $atts);

        $is_pro = $this->is_pro();
        $coupons = $this->generate_coupons($atts['affiliate'], $atts['category'], $atts['limit'], $is_pro);

        ob_start();
        ?>
        <div id="ai-coupon-container" class="ai-coupon-pro" data-pro="<?php echo $is_pro ? '1' : '0'; ?>">
            <h3>🔥 Exclusive Coupons for You!</h3>
            <?php if (!$is_pro): ?>
                <div class="pro-upgrade-notice">Upgrade to Pro for unlimited AI-generated coupons & analytics!</div>
            <?php endif; ?>
            <div class="coupons-list">
                <?php foreach ($coupons as $coupon): ?>
                    <div class="coupon-item">
                        <h4><?php echo esc_html($coupon['title']); ?></h4>
                        <p><?php echo esc_html($coupon['description']); ?></p>
                        <div class="coupon-code">Code: <strong><?php echo esc_html($coupon['code']); ?></strong></div>
                        <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank" rel="nofollow">Shop Now & Save <?php echo esc_html($coupon['discount']); ?>%</a>
                        <?php if ($is_pro): ?>
                            <span class="track-click" data-id="<?php echo esc_attr($coupon['id']); ?>"></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$is_pro): ?>
                <div class="pro-cta"><a href="https://example.com/pro" target="_blank">Get Pro Version - Unlock AI Power!</a></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($affiliate, $category, $limit, $pro) {
        // Demo coupons (Pro: Real AI generation via OpenAI)
        $demo_coupons = array(
            array('id' => 1, 'title' => 'Smartphone Deal', 'description' => 'Latest model with massive discount.', 'code' => 'SAVE20', 'link' => '#', 'discount' => '20'),
            array('id' => 2, 'title' => 'Laptop Flash Sale', 'description' => 'High-performance laptop on offer.', 'code' => 'LAPTOP15', 'link' => '#', 'discount' => '15'),
            array('id' => 3, 'title' => 'Headphones 30% Off', 'description' => 'Noise-cancelling pro headphones.', 'code' => 'HEAD30', 'link' => '#', 'discount' => '30'),
        );
        return array_slice($demo_coupons, 0, $pro ? $limit : min(3, $limit));
    }

    private function is_pro() {
        // Check license key or pro file
        return get_option('ai_coupon_pro_license') !== false;
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_coupon_pro_license'])) {
            update_option('ai_coupon_pro_license', sanitize_text_field($_POST['ai_coupon_pro_license']));
            echo '<div class="notice notice-success"><p>Pro activated! (Demo)</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Pro Settings</h1>
            <form method="post">
                <p>Pro License Key: <input type="text" name="ai_coupon_pro_license" value="<?php echo esc_attr(get_option('ai_coupon_pro_license', '')); ?>" /></p>
                <p class="description">Enter Pro key or <a href="https://example.com/pro" target="_blank">buy here</a> ($49/year).</p>
                <?php submit_button(); ?>
            </form>
            <p><strong>Usage:</strong> Use shortcode [ai_coupon_generator affiliate="amazon" category="electronics" limit="5"]</p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

AICouponGeneratorPro::get_instance();

// Pro upsell notice
function ai_coupon_pro_admin_notice() {
    if (!AICouponGeneratorPro::get_instance()->is_pro()) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Coupon Generator Pro</strong> for unlimited coupons & analytics! <a href="options-general.php?page=ai-coupon-pro">Upgrade now</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_pro_admin_notice');

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    // Note: In real plugin, include actual JS/CSS files
});
?>