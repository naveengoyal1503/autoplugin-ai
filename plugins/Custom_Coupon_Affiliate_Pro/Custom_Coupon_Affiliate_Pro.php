/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Affiliate Pro
 * Plugin URI: https://example.com/coupon-pro
 * Description: Generate custom affiliate coupons with tracking and expiration for higher conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class CustomCouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('coupon_pro', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            wp_register_style('coupon-pro-admin', plugin_dir_url(__FILE__) . 'admin.css');
            wp_enqueue_style('coupon-pro-admin');
        }
    }

    public function admin_menu() {
        add_menu_page('Coupon Pro', 'Coupon Pro', 'manage_options', 'coupon-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            update_option('coupon_pro_data', $_POST['coupon_data']);
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        $coupons = get_option('coupon_pro_data', array());
        ?>
        <div class="wrap">
            <h1>Custom Coupon Affiliate Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="coupon_data[code]" value="<?php echo esc_attr($coupons['code'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="coupon_data[link]" style="width:100%;" value="<?php echo esc_attr($coupons['link'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Discount %</th>
                        <td><input type="number" name="coupon_data[discount]" value="<?php echo esc_attr($coupons['discount'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Expires (days)</th>
                        <td><input type="number" name="coupon_data[expires]" value="<?php echo esc_attr($coupons['expires'] ?? '30'); ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="save_coupon" class="button-primary" value="Save Coupon" /></p>
            </form>
            <h2>Shortcode</h2>
            <p>Use: <code>[coupon_pro]</code></p>
            <?php
            if (isset($coupons['code'])) {
                echo '<h3>Preview:</h3>';
                echo $this->coupon_shortcode(array('id' => 'preview'));
            }
            ?>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons = get_option('coupon_pro_data', array());
        if (empty($coupons['code'])) return 'No coupon configured.';

        $unique_id = uniqid();
        $tracking_link = add_query_arg(array(
            'coupon' => $coupons['code'],
            'ref' => 'wp',
            'tid' => $unique_id
        ), $coupons['link']);

        $expires = isset($coupons['expires']) ? (int)$coupons['expires'] * 86400 : 2592000;
        $expiry_time = time() + $expires;

        if ($expiry_time < time()) {
            return '<div class="coupon-expired">Coupon expired.</div>';
        }

        ob_start();
        ?>
        <div class="coupon-pro-card" style="border: 2px dashed #0073aa; padding: 20px; border-radius: 10px; background: #f9f9f9; text-align: center; max-width: 400px;">
            <h3 style="color: #0073aa;">Exclusive Deal!</h3>
            <div style="font-size: 48px; font-weight: bold; color: #28a745; margin: 10px 0;"><?php echo esc_html($coupons['discount'] ?? '20'); ?>% OFF</div>
            <p><strong>Code: <?php echo esc_html($coupons['code']); ?></strong></p>
            <p>Expires in <?php echo floor(($expiry_time - time()) / 86400); ?> days</p>
            <a href="<?php echo esc_url($tracking_link); ?>" target="_blank" class="button" style="background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">Grab Deal Now</a>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.coupon-pro-card a').click(function() {
                gtag('event', 'coupon_click', {'coupon': '<?php echo esc_js($coupons['code']); ?>'});
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new CustomCouponAffiliatePro();

// Pro notice
function coupon_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Custom Coupon Affiliate Pro:</strong> Upgrade to Pro for unlimited coupons, analytics, and more! <a href="#" onclick="alert(\'Pro features coming soon!\')">Learn More</a></p></div>';
}
add_action('admin_notices', 'coupon_pro_notice');

// Enqueue basic styles
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_style('dashicons', '.coupon-pro-card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }');
});