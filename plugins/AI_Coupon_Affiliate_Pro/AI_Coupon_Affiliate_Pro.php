/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered plugin that generates, tracks, and displays personalized affiliate coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_display', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-affiliate-pro');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
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

    public function admin_init() {
        register_setting('ai_coupon_settings', 'ai_coupon_options');
        add_settings_section('ai_coupon_main', 'Main Settings', null, 'ai_coupon');
        add_settings_field('api_key', 'OpenAI API Key (Pro)', array($this, 'api_key_field'), 'ai_coupon', 'ai_coupon_main');
        add_settings_field('affiliate_links', 'Affiliate Links (JSON)', array($this, 'affiliate_links_field'), 'ai_coupon', 'ai_coupon_main');
    }

    public function api_key_field() {
        $options = get_option('ai_coupon_options');
        echo '<input type="password" name="ai_coupon_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your OpenAI API key for AI coupon generation. <strong>Pro Feature</strong></p>';
    }

    public function affiliate_links_field() {
        $options = get_option('ai_coupon_options');
        echo '<textarea name="ai_coupon_options[affiliate_links]" class="large-text" rows="10">' . esc_textarea($options['affiliate_links'] ?? '{"amazon":"https://amazon.com/ref=yourid", "hosting":"https://hostinger.com/aff=yourid"}') . '</textarea>';
        echo '<p class="description">JSON object of affiliate links e.g. {"brand":"link"}</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_coupon_settings');
                do_settings_sections('ai_coupon');
                submit_button();
                ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for unlimited AI generations, analytics, and integrations. <a href="https://example.com/pro">Get Pro Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('niche' => 'general'), $atts);
        $coupon = $this->generate_ai_coupon($atts['niche']);
        if (!$coupon) {
            return '<p>Upgrade to Pro for AI coupons!</p>';
        }
        $options = get_option('ai_coupon_options');
        $link = $options['affiliate_links'] ? json_decode($options['affiliate_links'], true)[$coupon['brand']] ?? '#' : '#';
        ob_start();
        ?>
        <div class="ai-coupon-card">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="<?php echo esc_url($link); ?>" class="coupon-btn" target="_blank">Get Deal (Affiliate)</a>
            <small>Tracked clicks: <span class="coupon-clicks">0</span></small>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_ai_coupon($niche) {
        $options = get_option('ai_coupon_options');
        if (empty($options['api_key'])) {
            return false; // Pro feature
        }
        // Simulated AI generation (replace with real OpenAI API call in Pro)
        $prompts = array(
            'general' => array(
                array('title' => '50% OFF Hosting', 'description' => 'Get premium hosting at half price!', 'code' => 'HOST50', 'brand' => 'hosting'),
                array('title' => 'Amazon Prime 30 Days Free', 'description' => 'Start your free trial today.', 'code' => 'PRIME30', 'brand' => 'amazon')
            )
        );
        $coupons = $prompts[$niche] ?? $prompts['general'];
        return $coupons[array_rand($coupons)];
    }

    public function activate() {
        if (!get_option('ai_coupon_options')) {
            add_option('ai_coupon_options');
        }
    }
}

AICouponAffiliatePro::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.ai-coupon-card { border: 2px solid #007cba; padding: 20px; border-radius: 10px; background: #f9f9f9; max-width: 400px; margin: 20px 0; }
.coupon-code { background: #fff; padding: 10px; font-family: monospace; font-size: 24px; text-align: center; border: 1px dashed #007cba; margin: 10px 0; }
.coupon-btn { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
.coupon-btn:hover { background: #005a87; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.coupon-btn').click(function() {
        var clicks = parseInt($(this).closest('.ai-coupon-card').find('.coupon-clicks').text());
        $(this).closest('.ai-coupon-card').find('.coupon-clicks').text(clicks + 1);
        // Track click (Pro: send to analytics)
        console.log('Coupon clicked!');
    });
});
</script>
<?php });