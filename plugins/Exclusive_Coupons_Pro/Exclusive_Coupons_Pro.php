/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, personalized coupon codes for your WordPress site visitors, boosting affiliate conversions and reader loyalty.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'ecp-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('ecp_settings', 'ecp_options');
        add_settings_section('ecp_main_section', 'Coupon Settings', null, 'exclusive-coupons-pro');
        add_settings_field('ecp_coupons', 'Coupons', array($this, 'coupons_field'), 'exclusive-coupons-pro', 'ecp_main_section');
        add_settings_field('ecp_pro', 'Pro Features', array($this, 'pro_field'), 'exclusive-coupons-pro', 'ecp_main_section');
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
            <p><strong>Upgrade to Pro for unlimited coupons, analytics, and custom branding!</strong></p>
        </div>
        <?php
    }

    public function coupons_field() {
        $options = get_option('ecp_options', array('coupons' => array(
            array('code' => 'SAVE10', 'desc' => '10% off at Example Store', 'afflink' => 'https://example.com/ref')
        )));
        $coupons = $options['coupons'];
        echo '<textarea name="ecp_options[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>Enter JSON array of coupons: [{&quot;code&quot;: &quot;SAVE10&quot;, &quot;desc&quot;: &quot;Description&quot;, &quot;afflink&quot;: &quot;Affiliate Link&quot;}]</p>';
    }

    public function pro_field() {
        echo '<p><a href="https://example.com/pro" target="_blank">Unlock Pro: Unlimited coupons, usage tracking, custom designs ($49/year)</a></p>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $options = get_option('ecp_options', array('coupons' => array()));
        $coupons = $options['coupons'];
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $unique_code = $coupon['code'] . '-' . uniqid();
        ob_start();
        ?>
        <div id="ecp-coupon-<?php echo esc_attr($atts['id']); ?>" class="ecp-coupon">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <p>Your exclusive code: <strong><?php echo esc_html($unique_code); ?></strong></p>
            <a href="<?php echo esc_url($coupon['afflink']); ?>&coupon=<?php echo urlencode($unique_code); ?>" class="ecp-button" target="_blank">Redeem Now</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('ecp_options', array('coupons' => array(
            array('code' => 'SAVE10', 'desc' => '10% off at Example Store', 'afflink' => 'https://example.com/ref')
        )));
    }
}

new ExclusiveCouponsPro();

// Inline styles and scripts for self-contained plugin
add_action('wp_head', function() {
    echo '<style>
    .ecp-coupon { background: #f8f9fa; padding: 20px; border: 2px dashed #0073aa; border-radius: 8px; text-align: center; max-width: 400px; margin: 20px auto; }
    .ecp-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .ecp-button:hover { background: #005a87; }
    </style>';
});

add_action('wp_footer', function() {
    echo '<script>jQuery(document).ready(function($) { $(".ecp-coupon").on("click", ".ecp-button", function() { $(this).text("Copied! Redeem now"); }); });</script>';
});