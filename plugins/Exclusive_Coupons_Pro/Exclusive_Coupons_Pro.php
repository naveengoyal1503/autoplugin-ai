/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Create, manage, and display exclusive affiliate coupons to boost conversions and affiliate earnings.
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
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-coupons-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('exclusive_coupons_options', 'exclusive_coupons_data');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('exclusive_coupons_data', sanitize_text_field($_POST['coupon_code']));
        }
        $data = get_option('exclusive_coupons_data', 'SAVE10');
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th>Default Coupon Code</th>
                        <td><input type="text" name="coupon_code" value="<?php echo esc_attr($data); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => get_option('exclusive_coupons_data', 'SAVE10'),
            'afflink' => '#',
            'title' => 'Exclusive Deal'
        ), $atts);

        ob_start();
        ?>
        <div class="exclusive-coupon-box">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <p>Use code: <strong><?php echo esc_html($atts['code']); ?></strong></p>
            <a href="<?php echo esc_url($atts['afflink']); ?}" class="coupon-btn" target="_blank">Get Deal Now</a>
            <small>Limited time offer!</small>
        </div>
        <style>
        .exclusive-coupon-box { background: #fff3cd; border: 2px dashed #ffc107; padding: 20px; border-radius: 10px; text-align: center; max-width: 300px; margin: 20px auto; }
        .coupon-btn { background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; }
        .coupon-btn:hover { background: #218838; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('exclusive_coupons_data', 'SAVE10');
    }
}

new ExclusiveCouponsPro();

// Premium teaser
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro Premium</strong> for unlimited coupons, analytics, and auto-expiry!</p></div>';
});