/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons for your WordPress site, boosting conversions with personalized discount codes and tracking.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ecp_coupons', array());
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', array());
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <?php $i = 0; foreach ($coupons as $coupon): ?>
                <div style="border:1px solid #ccc; margin:10px; padding:10px;">
                    <h3>Coupon <?php echo $i+1; ?></h3>
                    <label>Title: <input type="text" name="coupons[<?php echo $i; ?>][title]" value="<?php echo esc_attr($coupon['title']); ?>" /></label><br>
                    <label>Code: <input type="text" name="coupons[<?php echo $i; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" /></label><br>
                    <label>Affiliate Link: <input type="url" name="coupons[<?php echo $i; ?>][link]" value="<?php echo esc_attr($coupon['link']); ?>" style="width:300px;" /></label><br>
                    <label>Discount: <input type="text" name="coupons[<?php echo $i; ?>][discount]" value="<?php echo esc_attr($coupon['discount']); ?>" /></label><br>
                    <label>Image URL: <input type="url" name="coupons[<?php echo $i; ?>][image]" value="<?php echo esc_attr($coupon['image']); ?>" style="width:300px;" /></label>
                </div>
                <?php $i++; endforeach; ?>
                <p><input type="submit" name="ecp_save" value="Save Coupons" class="button-primary" /></p>
                <p><a href="#" onclick="addCoupon(); return false;">Add New Coupon</a></p>
            </form>
        </div>
        <script>
        function addCoupon() {
            var i = document.querySelectorAll('.wrap > div').length - 1;
            var html = '<div style="border:1px solid #ccc; margin:10px; padding:10px;">' +
                '<h3>Coupon ' + (i+1) + '</h3>' +
                '<label>Title: <input type="text" name="coupons[' + i + '][title]" /></label><br>' +
                '<label>Code: <input type="text" name="coupons[' + i + '][code]" /></label><br>' +
                '<label>Affiliate Link: <input type="url" name="coupons[' + i + '][link]" style="width:300px;" /></label><br>' +
                '<label>Discount: <input type="text" name="coupons[' + i + '][discount]" /></label><br>' +
                '<label>Image URL: <input type="url" name="coupons[' + i + '][image]" style="width:300px;" /></label>' +
                '</div>';
            document.querySelector('.wrap form').insertAdjacentHTML('beforeend', html);
        }
        </script>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('ecp_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return '';
        }
        $coupon = $coupons[$atts['id']];
        $output = '<div class="ecp-coupon" style="border:2px dashed #007cba; padding:20px; text-align:center; max-width:400px; margin:20px auto; background:#f9f9f9;">
            ' . ($coupon['image'] ? '<img src="' . esc_url($coupon['image']) . '" style="max-width:100%; height:auto;" alt="' . esc_attr($coupon['title']) . '" />' : '') . '
            <h3>' . esc_html($coupon['title']) . '</h3>
            <p><strong>Code:</strong> <span style="background:#fff; padding:5px 10px; font-size:1.2em; font-weight:bold; color:#007cba;">' . esc_html($coupon['code']) . '</span></p>
            <p><strong>Save:</strong> ' . esc_html($coupon['discount']) . '% OFF</p>
            <a href="' . esc_url($coupon['link']) . '" target="_blank" class="button" style="display:inline-block; background:#007cba; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;">Get Deal Now</a>
        </div>';
        return $output;
    }
}

new ExclusiveCouponsPro();

// Pro teaser
function ecp_pro_teaser() {
    if (!get_option('ecp_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Upgrade to Pro for unlimited coupons, click tracking, and analytics! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'ecp_pro_teaser');

// Prevent direct access to style.css if not exists
if (!file_exists(plugin_dir_path(__FILE__) . 'style.css')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'style.css', '.ecp-coupon { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }');
}
