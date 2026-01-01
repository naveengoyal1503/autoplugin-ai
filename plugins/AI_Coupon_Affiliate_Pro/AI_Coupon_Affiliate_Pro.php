/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate manager that generates personalized deals, tracks clicks, and boosts conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
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

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_deals', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro');
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
        add_settings_field('api_key', 'OpenAI API Key (Pro)', array($this, 'api_key_field'), 'ai-coupon-pro', 'main_section');
        add_settings_field('affiliate_links', 'Affiliate Links (JSON)', array($this, 'affiliate_links_field'), 'ai-coupon-pro', 'main_section');
    }

    public function api_key_field() {
        $settings = get_option('ai_coupon_pro_settings', array());
        echo '<input type="password" name="ai_coupon_pro_settings[api_key]" value="' . esc_attr($settings['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your OpenAI API key for AI coupon generation (Pro feature).</p>';
    }

    public function affiliate_links_field() {
        $settings = get_option('ai_coupon_pro_settings', array());
        echo '<textarea name="ai_coupon_pro_settings[affiliate_links]" class="large-text" rows="10">' . esc_textarea($settings['affiliate_links'] ?? '{"product1":"https://affiliate.link1","product2":"https://affiliate.link2"}') . '</textarea>';
        echo '<p class="description">JSON object of products and affiliate links. Example: {"product":"link"}</p>';
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
            <h2>Pro Upgrade</h2>
            <p>Unlock AI generation and unlimited coupons for $49/year. <a href="#" onclick="alert('Pro upgrade link')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 3), $atts);
        $settings = get_option('ai_coupon_pro_settings', array());
        $links = json_decode($settings['affiliate_links'] ?? '{}', true);
        $html = '<div class="ai-coupon-deals">';
        $products = array_slice(array_keys($links), 0, (int)$atts['count']);
        foreach ($products as $product) {
            $code = $this->generate_coupon_code($product);
            $html .= '<div class="coupon-item">';
            $html .= '<h4>' . esc_html($product) . '</h4>';
            $html .= '<p>Code: <strong>' . esc_html($code) . '</strong></p>';
            $html .= '<a href="' . esc_url($links[$product]) . '" class="coupon-link" data-product="' . esc_attr($product) . '" target="_blank">Get Deal (Track Affiliate)</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function generate_coupon_code($product) {
        // Free version: Simple static codes
        return 'SAVE' . rand(10, 50) . '%-' . substr(md5($product), 0, 6);
        // Pro AI version (disabled):
        // $settings = get_option('ai_coupon_pro_settings');
        // if (!empty($settings['api_key'])) {
        //     // OpenAI API call to generate unique code
        // }
    }

    public function activate() {
        if (!get_option('ai_coupon_pro_settings')) {
            update_option('ai_coupon_pro_settings', array());
        }
        // Create assets dir
        $upload_dir = plugin_dir_path(__FILE__) . 'assets/';
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }
        // Create placeholder JS
        file_put_contents($upload_dir . 'script.js', "jQuery(document).ready(function($){ $('.coupon-link').click(function(){ ga('send','event','Coupon','Click',$(this).data('product')); }); });");
        // Create placeholder CSS
        file_put_contents($upload_dir . 'style.css', ".ai-coupon-deals { display: flex; flex-wrap: wrap; gap: 20px; } .coupon-item { border: 1px solid #ddd; padding: 20px; border-radius: 8px; } .coupon-link { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }");
    }
}

AICouponAffiliatePro::get_instance();

// Track clicks
add_action('wp_ajax_track_coupon_click', 'track_coupon_click');
add_action('wp_ajax_nopriv_track_coupon_click', 'track_coupon_click');
function track_coupon_click() {
    if (isset($_POST['product'])) {
        // Log click for affiliate tracking (Pro: integrates with wecantrack-like)
        error_log('Coupon click: ' . sanitize_text_field($_POST['product']));
        wp_die('Tracked');
    }
}
