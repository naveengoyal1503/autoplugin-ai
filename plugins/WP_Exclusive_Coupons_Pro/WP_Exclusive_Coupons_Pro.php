/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Generate, manage, and track exclusive affiliate coupons to boost conversions on your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WP_Exclusive_Coupons {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('wpec-admin-css', plugin_dir_url(__FILE__) . 'admin.css');
        wp_enqueue_style('wpec-admin-css');
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons',
            'manage_options',
            'wp-exclusive-coupons',
            array($this, 'admin_page'),
            'dashicons-cart',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            update_option('wpec_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        $coupons = get_option('wpec_coupons', array());
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="coupons[code]" value="<?php echo esc_attr($coupons['code'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Discount</th>
                        <td><input type="text" name="coupons[discount]" value="<?php echo esc_attr($coupons['discount'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="coupons[link]" value="<?php echo esc_attr($coupons['link'] ?? ''); ?>" style="width: 100%;" /></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><textarea name="coupons[desc]" rows="4" style="width: 100%;"><?php echo esc_textarea($coupons['desc'] ?? ''); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupon'); ?>
            </form>
            <h2>Use Shortcode</h2>
            <p>[exclusive_coupon]</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons = get_option('wpec_coupons', array());
        if (empty($coupons['code'])) {
            return '<p>No coupon configured.</p>';
        }
        $tracking_id = uniqid('wpec_');
        $link = add_query_arg('ref', $tracking_id, $coupons['link']);
        ob_start();
        ?>
        <div class="wpec-coupon-box">
            <h3>Exclusive Deal: <strong><?php echo esc_html($coupons['code']); ?></strong></h3>
            <p><?php echo esc_html($coupons['desc']); ?></p>
            <p><strong>Save <?php echo esc_html($coupons['discount']); ?>!</strong></p>
            <a href="<?php echo esc_url($link); ?>" class="button button-large button-primary" target="_blank">Get Deal Now</a>
        </div>
        <style>
        .wpec-coupon-box { border: 2px dashed #0073aa; padding: 20px; background: #f9f9f9; text-align: center; max-width: 400px; }
        .wpec-coupon-box h3 { color: #0073aa; margin-top: 0; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('wpec_coupons')) {
            update_option('wpec_coupons', array());
        }
    }
}

new WP_Exclusive_Coupons();

// Premium upsell notice
function wpec_premium_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>WP Exclusive Coupons Pro</strong> for unlimited coupons, click tracking, and integrations! <a href="https://example.com/premium" target="_blank">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'wpec_premium_notice');