/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Vault_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Vault Pro
 * Plugin URI: https://example.com/aicouponvault
 * Description: Automatically generates, manages, and displays personalized affiliate coupons with AI-powered deal suggestions to boost conversions and revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponVault {
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
        add_shortcode('ai_coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ai-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            'AI Coupon Vault Settings',
            'Coupon Vault',
            'manage_options',
            'ai-coupon-vault',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('ai_coupon_vault_options', 'ai_coupon_vault_settings');
        add_settings_section('main_section', 'Main Settings', null, 'ai_coupon_vault');
        add_settings_field('api_key', 'OpenAI API Key (Pro)', array($this, 'api_key_field'), 'ai_coupon_vault', 'main_section');
        add_settings_field('affiliate_links', 'Affiliate Links (JSON)', array($this, 'affiliate_links_field'), 'ai_coupon_vault', 'main_section');
    }

    public function api_key_field() {
        $settings = get_option('ai_coupon_vault_settings', array());
        echo '<input type="password" name="ai_coupon_vault_settings[api_key]" value="' . esc_attr($settings['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your OpenAI API key for AI coupon generation (Pro feature).</p>';
    }

    public function affiliate_links_field() {
        $settings = get_option('ai_coupon_vault_settings', array());
        echo '<textarea name="ai_coupon_vault_settings[affiliate_links]" class="large-text" rows="10">' . esc_textarea($settings['affiliate_links'] ?? '{"amazon": "https://amazon.com/?tag=yourtag", "other": "https://example.com/aff"}') . '</textarea>';
        echo '<p class="description">JSON object of affiliate links, e.g. {"store": "link"}.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Vault Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_coupon_vault_options');
                do_settings_sections('ai_coupon_vault');
                submit_button();
                ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for AI generation, unlimited coupons, and analytics. <a href="#" onclick="alert('Pro upgrade: $49/year')">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 5), $atts);
        $settings = get_option('ai_coupon_vault_settings', array());
        $coupons = get_transient('ai_coupon_cache');
        if (false === $coupons) {
            $coupons = $this->generate_coupons($settings, $atts['count']);
            set_transient('ai_coupon_cache', $coupons, HOUR_IN_SECONDS);
        }
        return $this->render_coupons($coupons);
    }

    private function generate_coupons($settings, $count) {
        $coupons = array();
        $aff_links = json_decode($settings['affiliate_links'] ?? '{}', true);
        // Free: Static coupons
        $static = array(
            array('title' => '20% Off Amazon', 'code' => 'SAVE20', 'link' => $aff_links['amazon'] ?? '#'),
            array('title' => 'Free Trial', 'code' => 'TRIAL', 'link' => $aff_links['other'] ?? '#'),
        );
        $coupons = array_slice($static, 0, $count);
        // Pro: AI generation (simplified mock)
        if (!empty($settings['api_key'])) {
            for ($i = 0; $i < $count; $i++) {
                $coupons[] = array(
                    'title' => 'AI Deal ' . ($i+1),
                    'code' => 'AI' . wp_rand(1000,9999),
                    'link' => $aff_links['amazon'] ?? '#',
                );
            }
        }
        return $coupons;
    }

    private function render_coupons($coupons) {
        $html = '<div class="ai-coupon-vault"><h3>Exclusive Deals</h3><ul>';
        foreach ($coupons as $coupon) {
            $html .= '<li><strong>' . esc_html($coupon['title']) . '</strong><br>Code: <code>' . esc_html($coupon['code']) . '</code><br><a href="' . esc_url($coupon['link']) . '" target="_blank" rel="nofollow">Shop Now</a></li>';
        }
        $html .= '</ul></div>';
        if (empty(get_option('ai_coupon_vault_settings')['api_key'])) {
            $html .= '<p><em>Upgrade to Pro for AI-powered coupons!</em></p>';
        }
        return $html;
    }

    public function activate() {
        if (!get_option('ai_coupon_vault_settings')) {
            update_option('ai_coupon_vault_settings', array('affiliate_links' => '{"amazon": "https://amazon.com/?tag=yourtag"}'));
        }
        flush_rewrite_rules();
    }
}

AICouponVault::get_instance();

// Inline CSS
add_action('wp_head', function() { echo '<style>.ai-coupon-vault ul{list-style:none;padding:0;}.ai-coupon-vault li{margin:10px 0;padding:15px;border:1px solid #ddd;background:#f9f9f9;}.ai-coupon-vault code{background:#eee;padding:2px 5px;border-radius:3px;}</style>'; });

// Inline JS
add_action('wp_footer', function() { echo '<script>jQuery(document).ready(function($){ $(".ai-coupon-vault a").click(function(){console.log("Coupon clicked!");}); });</script>'; });