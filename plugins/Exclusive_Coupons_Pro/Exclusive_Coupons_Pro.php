/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons with custom promo codes, tracking, and automated affiliate link integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('ecp-admin-css', plugin_dir_url(__FILE__) . 'admin.css');
        wp_enqueue_style('ecp-admin-css');
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            update_option('ecp_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', array());
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Brand</th>
                        <th>Coupon Code</th>
                        <th>Affiliate Link</th>
                        <th>Description</th>
                    </tr>
                    <?php foreach ($coupons as $index => $coupon): ?>
                    <tr>
                        <td><input type="text" name="coupons[<?php echo $index; ?>][brand]" value="<?php echo esc_attr($coupon['brand']); ?>" /></td>
                        <td><input type="text" name="coupons[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" /></td>
                        <td><input type="url" name="coupons[<?php echo $index; ?>][link]" value="<?php echo esc_attr($coupon['link']); ?>" /></td>
                        <td><textarea name="coupons[<?php echo $index; ?>][desc]"><?php echo esc_textarea($coupon['desc']); ?></textarea></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><input type="text" name="coupons[new][brand]" placeholder="New Brand" /></td>
                        <td><input type="text" name="coupons[new][code]" placeholder="COUPON123" /></td>
                        <td><input type="url" name="coupons[new][link]" placeholder="https://affiliate-link.com" /></td>
                        <td><textarea name="coupons[new][desc]" placeholder="Exclusive 20% off!"></textarea></td>
                    </tr>
                </table>
                <p><input type="submit" name="save_coupon" class="button-primary" value="Save Coupons" /></p>
            </form>
            <p>Use shortcode: <code>[exclusive_coupon id="0"]</code> (replace 0 with coupon index)</p>
            <p><em>Pro: Unlimited coupons, click tracking, analytics. Upgrade at example.com</em></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('ecp_coupons', array());
        $id = intval($atts['id']);
        if (isset($coupons[$id])) {
            $coupon = $coupons[$id];
            $link = add_query_arg('ecp', $id, $coupon['link']);
            return '<div class="exclusive-coupon"><h3>' . esc_html($coupon['brand']) . '</h3><p>' . esc_html($coupon['desc']) . '</p><p><strong>Code: ' . esc_html($coupon['code']) . '</strong></p><a href="' . esc_url($link) . '" class="button" target="_blank">Get Deal</a></div>';
        }
        return '';
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', array(array('brand' => 'Example Brand', 'code' => 'WELCOME20', 'link' => '', 'desc' => '20% off first purchase')));
        }
    }
}

new ExclusiveCouponsPro();

// Basic CSS
add_action('wp_head', function() { ?>
<style>
.exclusive-coupon { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 5px; }
.exclusive-coupon .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }
.exclusive-coupon .button:hover { background: #005a87; }
</style>
<?php });

// Track clicks
add_action('init', function() {
    if (isset($_GET['ecp'])) {
        update_option('ecp_clicks_' . intval($_GET['ecp']), (get_option('ecp_clicks_' . intval($_GET['ecp']), 0) + 1));
    }
});