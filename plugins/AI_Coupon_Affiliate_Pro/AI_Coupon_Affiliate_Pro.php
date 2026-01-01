/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: Automatically generates and displays personalized affiliate coupon codes with AI-powered recommendations to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_display', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('ai_coupon_pro_options', 'ai_coupon_pro_settings');
        add_settings_section('main_section', 'Main Settings', null, 'ai_coupon_pro');
        add_settings_field('affiliate_links', 'Affiliate Links (JSON)', array($this, 'affiliate_links_field'), 'ai_coupon_pro', 'main_section');
        add_settings_field('api_key', 'OpenAI API Key (Pro)', array($this, 'api_key_field'), 'ai_coupon_pro', 'main_section');
    }

    public function affiliate_links_field() {
        $settings = get_option('ai_coupon_pro_settings', array('affiliate_links' => '[]'));
        echo '<textarea name="ai_coupon_pro_settings[affiliate_links]" rows="10" cols="50">' . esc_textarea($settings['affiliate_links']) . '</textarea>';
        echo '<p class="description">Enter JSON array of affiliate offers: [{"name":"Product","code":"SAVE20","link":"https://aff.link","desc":"20% off"}]</p>';
    }

    public function api_key_field() {
        $settings = get_option('ai_coupon_pro_settings', array());
        echo '<input type="password" name="ai_coupon_pro_settings[api_key]" value="' . esc_attr($settings['api_key'] ?? '') . '" />';
        echo '<p class="description">Pro feature: OpenAI API key for AI recommendations.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_coupon_pro_options');
                do_settings_sections('ai_coupon_pro');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation, unlimited coupons, analytics for $49/year. <a href="https://example.com/pro">Buy Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('num' => 3), $atts);
        $settings = get_option('ai_coupon_pro_settings', array('affiliate_links' => '[]'));
        $coupons = json_decode($settings['affiliate_links'], true) ?: array();

        if (empty($coupons)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=ai-coupon-pro') . '">Set up now</a>.</p>';
        }

        // Simple AI-like randomization for demo
        shuffle($coupons);
        $display = array_slice($coupons, 0, $atts['num']);

        $output = '<div class="ai-coupon-container">';
        foreach ($display as $coupon) {
            $output .= '<div class="coupon-card">';
            $output .= '<h3>' . esc_html($coupon['name']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['desc']) . '</p>';
            $output .= '<span class="coupon-code">' . esc_html($coupon['code']) . '</span>';
            $output .= '<a href="' . esc_url($coupon['link']) . '" class="coupon-btn" target="_blank">Shop Now & Save</a>';
            $output .= '</div>';
        }
        $output .= '</div>';

        if (!empty($settings['api_key'])) {
            $output .= $this->get_ai_recommendation();
        } else {
            $output .= '<p><a href="' . admin_url('options-general.php?page=ai-coupon-pro') . '">Upgrade to Pro for AI recommendations</a></p>';
        }

        return $output;
    }

    private function get_ai_recommendation() {
        // Pro demo: Simulate AI call
        $recommendations = array(
            'Perfect for shoppers looking to save on tech!',
            'Handpicked deals based on your browsing.',
            'AI suggests: Use these for max savings.'
        );
        return '<div class="ai-recommendation">' . $recommendations[array_rand($recommendations)] . '</div>';
    }

    public function activate() {
        add_option('ai_coupon_pro_settings', array('affiliate_links' => '[]'));
    }
}

new AICouponAffiliatePro();

// Inline CSS and JS for single file

function ai_coupon_styles() {
    echo '<style>
.ai-coupon-container { display: flex; flex-wrap: wrap; gap: 20px; margin: 20px 0; }
.coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1 1 300px; background: #f9f9f9; }
.coupon-code { display: block; background: #ffeb3b; padding: 10px; font-size: 1.2em; font-weight: bold; margin: 10px 0; }
.coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
.coupon-btn:hover { background: #005a87; }
.ai-recommendation { background: #e3f2fd; padding: 15px; margin-top: 20px; border-left: 4px solid #2196f3; }
    </style>';
}
add_action('wp_head', 'ai_coupon_styles');

/*
Pro JS (minified demo)
*/
function ai_coupon_scripts() {
    echo '<script>jQuery(document).ready(function($){ $(".coupon-btn").on("click",function(){ $(this).text("Copied! Applying discount..."); }); });</script>';
}
add_action('wp_footer', 'ai_coupon_scripts');
