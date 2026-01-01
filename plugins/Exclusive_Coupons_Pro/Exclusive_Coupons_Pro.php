/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and manages exclusive affiliate coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
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
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('ecp_settings', 'ecp_options');
        add_settings_section('ecp_main_section', 'Coupon Settings', null, 'exclusive-coupons-pro');
        add_settings_field('ecp_coupons', 'Coupons List (JSON)', array($this, 'coupons_field'), 'exclusive-coupons-pro', 'ecp_main_section');
    }

    public function coupons_field() {
        $options = get_option('ecp_options', array('coupons' => array()));
        $coupons_json = json_encode($options['coupons'], JSON_PRETTY_PRINT);
        echo '<textarea name="ecp_options[coupons]" rows="10" cols="80">' . esc_textarea($coupons_json) . '</textarea>';
        echo '<p class="description">Enter coupons as JSON array: [{"brand":"Brand","code":"SAVE20","afflink":"https://aff.link","desc":"20% off"}]</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ecp_settings');
                do_settings_sections('exclusive-coupons-pro');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $options = get_option('ecp_options', array('coupons' => array()));
        $coupons = $options['coupons'];
        shuffle($coupons);
        $coupons = array_slice($coupons, 0, intval($atts['limit']));
        $output = '<div class="ecp-coupons">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="ecp-coupon">';
            $output .= '<h4>' . esc_html($coupon['brand']) . '</h4>';
            $output .= '<p>' . esc_html($coupon['desc']) . '</p>';
            $output .= '<div class="ecp-code">Code: <strong>' . esc_html($coupon['code']) . '</strong></div>';
            $output .= '<a href="' . esc_url($coupon['afflink']) . '" class="ecp-button" target="_blank">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        $default = array('coupons' => array(
            array('brand' => 'Demo Brand', 'code' => 'DEMO20', 'afflink' => '#', 'desc' => '20% off demo'),
        ));
        update_option('ecp_options', $default);
    }
}

new ExclusiveCouponsPro();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.ecp-coupons { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
.ecp-coupon { background: #f9f9f9; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.ecp-code { background: #fff; padding: 10px; border: 1px dashed #ccc; margin: 10px 0; }
.ecp-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
.ecp-button:hover { background: #005a87; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.ecp-button').on('click', function() {
        $(this).text('Copied! Shop now');
    });
});
</script>
<?php });