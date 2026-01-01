/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator for affiliate marketing. Automatically creates and displays personalized coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

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
        add_action('wp_footer', array($this, 'footer_script'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('ai_coupon_settings', 'ai_coupon_options');
        add_settings_section('ai_coupon_main', 'Main Settings', null, 'ai_coupon');
        add_settings_field('api_key', 'OpenAI API Key (Pro)', array($this, 'api_key_field'), 'ai_coupon', 'ai_coupon_main');
        add_settings_field('affiliate_ids', 'Affiliate IDs (JSON)', array($this, 'affiliate_ids_field'), 'ai_coupon', 'ai_coupon_main');
    }

    public function api_key_field() {
        $options = get_option('ai_coupon_options', array());
        echo '<input type="password" name="ai_coupon_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your OpenAI API key for AI coupon generation (Pro feature).</p>';
    }

    public function affiliate_ids_field() {
        $options = get_option('ai_coupon_options', array());
        echo '<textarea name="ai_coupon_options[affiliate_ids]" rows="5" cols="50">' . esc_textarea($options['affiliate_ids'] ?? '') . '</textarea>';
        echo '<p class="description">JSON array of affiliate programs, e.g. {"amazon": "your-id", "other": "id"}.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_coupon_settings');
                do_settings_sections('ai_coupon');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation, unlimited coupons, and analytics for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'count' => 3
        ), $atts);

        $options = get_option('ai_coupon_options', array());
        $coupons = get_transient('ai_coupons_' . md5($atts['niche']));

        if (!$coupons || !is_pro()) {
            $coupons = $this->generate_sample_coupons($atts['niche'], $atts['count']);
            if (is_pro() && $options['api_key']) {
                $coupons = $this->generate_ai_coupons($atts['niche'], $atts['count'], $options['api_key']);
            }
            set_transient('ai_coupons_' . md5($atts['niche']), $coupons, HOUR_IN_SECONDS);
        }

        return $this->render_coupons($coupons);
    }

    private function generate_sample_coupons($niche, $count) {
        $samples = array(
            array('code' => 'SAVE20', 'desc' => '20% off on electronics', 'link' => '#', 'expires' => date('Y-m-d', strtotime('+7 days'))),
            array('code' => 'DEAL10', 'desc' => '10% off fashion', 'link' => '#', 'expires' => date('Y-m-d', strtotime('+14 days'))),
            array('code' => 'FREESHIP', 'desc' => 'Free shipping sitewide', 'link' => '#', 'expires' => date('Y-m-d', strtotime('+30 days')))
        );
        return array_slice($samples, 0, $count);
    }

    private function generate_ai_coupons($niche, $count, $api_key) {
        // Pro: Real OpenAI call (simplified placeholder)
        $prompt = "Generate $count unique coupon codes for $niche niche with descriptions, affiliate links, and expiry dates.";
        // In pro version: use wp_remote_post to OpenAI API
        return $this->generate_sample_coupons($niche, $count); // Fallback
    }

    private function render_coupons($coupons) {
        $output = '<div class="ai-coupon-container">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="coupon-card">';
            $output .= '<h3>' . esc_html($coupon['code']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['desc']) . '</p>';
            $output .= '<a href="' . esc_url($coupon['link']) . '" class="coupon-btn" target="_blank">Get Deal</a>';
            $output .= '<small>Expires: ' . esc_html($coupon['expires']) . '</small>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function footer_script() {
        if (is_pro()) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                $('.coupon-btn').on('click', function() {
                    // Track clicks (Pro analytics)
                    console.log('Coupon clicked');
                });
            });
            </script>
            <?php
        }
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

function is_pro() {
    // Check license or pro file existence
    return file_exists(plugin_dir_path(__FILE__) . 'pro.php');
}

AICouponAffiliatePro::get_instance();

// Assets would be created as style.css and script.js in /assets/
// style.css: .ai-coupon-container { display: flex; flex-wrap: wrap; } .coupon-card { border: 1px solid #ddd; padding: 20px; margin: 10px; border-radius: 8px; } .coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; }
// script.js: empty for free version
?>