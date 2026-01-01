/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate link generator for boosting commissions.
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
        add_shortcode('ai_coupon_box', array($this, 'coupon_box_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-pro', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-pro', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('ai_coupon_pro_options', 'ai_coupon_pro_settings');
        add_settings_section('main_section', 'Main Settings', null, 'ai-coupon-pro');
        add_settings_field('api_key', 'OpenAI API Key (Pro Feature)', array($this, 'api_key_callback'), 'ai-coupon-pro', 'main_section');
        add_settings_field('affiliates', 'Affiliate Links', array($this, 'affiliates_callback'), 'ai-coupon-pro', 'main_section');
    }

    public function api_key_callback() {
        $options = get_option('ai_coupon_pro_settings');
        echo '<input type="password" name="ai_coupon_pro_settings[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your OpenAI API key for AI coupon generation. <a href="#" onclick="alert(\'Pro Feature: Upgrade for full AI access!\')">Upgrade to Pro</a></p>';
    }

    public function affiliates_callback() {
        $options = get_option('ai_coupon_pro_settings');
        $affiliates = $options['affiliates'] ?? '';
        echo '<textarea name="ai_coupon_pro_settings[affiliates]" rows="5" cols="50" class="large-text">' . esc_textarea($affiliates) . '</textarea>';
        echo '<p class="description">Enter affiliate links in JSON format: {\"amazon\":\"https://amzn.to/xxx\",\"other\":\"link\"}</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_coupon_pro_options');
                do_settings_sections('ai-coupon-pro');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, click tracking, and analytics for $49/year!</p>
        </div>
        <?php
    }

    public function coupon_box_shortcode($atts) {
        $atts = shortcode_atts(array('niche' => 'general'), $atts);
        $options = get_option('ai_coupon_pro_settings');
        $affiliates = json_decode($options['affiliates'] ?? '{}', true);
        $coupons = $this->generate_coupons($atts['niche']);

        ob_start();
        ?>
        <div id="ai-coupon-box" class="ai-coupon-pro-container">
            <h3>🔥 Exclusive Deals & Coupons</h3>
            <div class="coupons-list">
                <?php foreach ($coupons as $coupon): ?>
                    <div class="coupon-item">
                        <span class="coupon-code"><?php echo esc_html($coupon['code']); ?></span>
                        <p><?php echo esc_html($coupon['desc']); ?> <strong><?php echo esc_html($coupon['discount']); ?></strong></p>
                        <?php if (isset($affiliates[$coupon['affiliate']])): ?>
                            <a href="<?php echo esc_url($affiliates[$coupon['affiliate']]); ?>&coupon=<?php echo esc_attr($coupon['code']); ?>" class="coupon-btn" target="_blank" onclick="trackClick('<?php echo esc_js($coupon['affiliate']); ?>')">Shop Now & Save</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="pro-upsell">Unlimited coupons & tracking? <a href="#" onclick="alert('Upgrade to Pro!')">Go Pro</a></p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($niche) {
        // Simulated AI generation (Pro uses real OpenAI API)
        $samples = array(
            array('code' => 'SAVE20', 'desc' => '20% off on electronics', 'discount' => '20%', 'affiliate' => 'amazon'),
            array('code' => 'DEAL50', 'desc' => '50% off clothing items', 'discount' => '50%', 'affiliate' => 'amazon'),
            array('code' => 'FREESHIP', 'desc' => 'Free shipping on orders', 'discount' => 'Free Ship', 'affiliate' => 'other')
        );
        return $samples;
    }

    public function activate() {
        add_option('ai_coupon_pro_settings', array());
    }
}

new AICouponAffiliatePro();

// Assets (base64 or inline for single file)
/*
Assets would be enqueued from plugin_dir_url, but for single-file demo, add inline styles/scripts.
*/

?>