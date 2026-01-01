/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons with personalized discount codes to boost conversions and revenue.
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
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('exclusive_coupons_pro_options', 'exclusive_coupons_pro_settings');
        add_settings_section('coupons_section', 'Coupon Settings', null, 'exclusive-coupons-pro');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'exclusive-coupons-pro', 'coupons_section');
    }

    public function coupons_field() {
        $settings = get_option('exclusive_coupons_pro_settings', array());
        $coupons = isset($settings['coupons']) ? $settings['coupons'] : array(
            array('name' => 'Sample Coupon', 'code' => 'SAVE20', 'afflink' => 'https://example.com/aff', 'desc' => '20% off')
        );
        echo '<textarea name="exclusive_coupons_pro_settings[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>Enter JSON array of coupons: {"name":"Name","code":"CODE","afflink":"Affiliate Link","desc":"Description"}</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('exclusive_coupons_pro_options');
                do_settings_sections('exclusive-coupons-pro');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $settings = get_option('exclusive_coupons_pro_settings', array());
        $coupons = json_decode($settings['coupons'] ?? '[]', true);
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $unique_code = $coupon['code'] . '-' . uniqid();
        ob_start();
        ?>
        <div class="exclusive-coupon" data-afflink="<?php echo esc_attr($coupon['afflink']); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['desc']); ?></p>
            <div class="coupon-code"><?php echo esc_html($unique_code); ?></div>
            <button class="copy-code">Copy Code</button>
            <a href="#" class="reveal-deal">Reveal Deal</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('exclusive_coupons_pro_settings', array('coupons' => json_encode(array(
            array('name' => 'Sample Coupon', 'code' => 'SAVE20', 'afflink' => 'https://example.com/aff', 'desc' => '20% off')
        ))));
    }
}

new ExclusiveCouponsPro();

// Premium upsell notice
function exclusive_coupons_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock premium features like analytics and unlimited coupons: <a href="https://example.com/premium" target="_blank">Upgrade to Pro</a></p></div>';
}
add_action('admin_notices', 'exclusive_coupons_pro_notice');

// Create assets directories
$upload_dir = wp_upload_dir();
$css_dir = plugin_dir_path(__FILE__) . 'assets/css/';
$js_dir = plugin_dir_path(__FILE__) . 'assets/js/';
if (!file_exists($css_dir)) mkdir($css_dir, 0755, true);
if (!file_exists($js_dir)) mkdir($js_dir, 0755, true);

// Inline styles and scripts for single file
add_action('wp_head', function() {
    echo '<style>
.exclusive-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; border-radius: 10px; }
.coupon-code { font-size: 24px; font-weight: bold; color: #e74c3c; background: #fff; padding: 10px; margin: 10px 0; display: block; }
.copy-code, .reveal-deal { background: #007cba; color: white; border: none; padding: 10px 20px; margin: 5px; cursor: pointer; border-radius: 5px; }
.reveal-deal { background: #27ae60; }
.copied { background: #f39c12 !important; }
    </style>';
});

add_action('wp_footer', function() {
    echo '<script>jQuery(document).ready(function($) {
        $(".copy-code").click(function() {
            var code = $(this).siblings(".coupon-code").text();
            navigator.clipboard.writeText(code).then(function() {
                $(this).text("Copied!").addClass("copied");
                setTimeout(() => $(this).text("Copy Code").removeClass("copied"), 2000);
            }.bind(this));
        });
        $(".reveal-deal").click(function(e) {
            e.preventDefault();
            var link = $(this).siblings("[data-afflink]").data("afflink") + "?coupon=" + $(this).siblings(".coupon-code").text();
            window.open(link, "_blank");
        });
    });</script>';
});