/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Automatically generates, manages, and displays exclusive discount coupons to boost affiliate conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WPExclusiveCouponsPro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wpecp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('wpecp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'wpecp', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('wpecp_options', 'wpecp_settings');
        add_settings_section('wpecp_main', 'Coupon Settings', null, 'wpecp');
        add_settings_field('coupons', 'Coupons (JSON format: {"code":"discount","afflink":"url"})', array($this, 'coupons_field'), 'wpecp', 'wpecp_main');
        add_settings_field('pro_notice', 'Go Pro', array($this, 'pro_notice'), 'wpecp', 'wpecp_main');
    }

    public function coupons_field() {
        $settings = get_option('wpecp_settings', array('coupons' => '[]'));
        echo '<textarea name="wpecp_settings[coupons]" rows="10" cols="50">' . esc_textarea($settings['coupons']) . '</textarea>';
        echo '<p>Enter JSON array of coupons: [{"code":"SAVE20","discount":"20% off","afflink":"https://aff.link"}]</p>';
    }

    public function pro_notice() {
        echo '<p><strong>Pro Version:</strong> Unlimited coupons, analytics, auto-generation, custom designs. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wpecp_options');
                do_settings_sections('wpecp');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('num' => 3), $atts);
        $settings = get_option('wpecp_settings', array('coupons' => '[]'));
        $coupons = json_decode($settings['coupons'], true) ?: array();
        shuffle($coupons);
        $coupons = array_slice($coupons, 0, intval($atts['num']));
        $output = '<div class="wpecp-coupons">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="wpecp-coupon">';
            $output .= '<h3>Exclusive: <code>' . esc_html($coupon['code']) . '</code> - ' . esc_html($coupon['discount']) . '</h3>';
            $output .= '<a href="' . esc_url($coupon['afflink']) . '" target="_blank" class="wpecp-btn">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        add_option('wpecp_settings', array('coupons' => '[]'));
    }
}

new WPExclusiveCouponsPro();

// Inline styles and scripts for self-contained plugin

function wpecp_inline_styles() {
    echo '<style>
    .wpecp-coupons { display: flex; flex-wrap: wrap; gap: 20px; }
    .wpecp-coupon { background: #fff; border: 2px dashed #0073aa; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .wpecp-coupon h3 { margin: 0 0 15px; color: #0073aa; }
    .wpecp-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .wpecp-btn:hover { background: #005a87; }
    @media (max-width: 768px) { .wpecp-coupons { flex-direction: column; } }
    </style>';
}
add_action('wp_head', 'wpecp_inline_styles');

function wpecp_inline_scripts() {
    echo '<script>
    jQuery(document).ready(function($) {
        $(".wpecp-btn").on("click", function() {
            gtag("event", "coupon_click", {"coupon": $(this).prev("h3").text() });
        });
    });
    </script>';
}
add_action('wp_footer', 'wpecp_inline_scripts');