/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_coupons', array($this, 'save_coupons'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-admin', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        $coupons = get_option('acv_coupons', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <p>Manage your exclusive coupons. <strong>Pro: Unlimited coupons, analytics.</strong></p>
            <form id="acv-form">
                <table class="form-table">
                    <tr>
                        <th>Coupon Name</th>
                        <td><input type="text" name="coupon_name[]" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="affiliate_link[]" /></td>
                    </tr>
                    <tr>
                        <th>Discount Code</th>
                        <td><input type="text" name="discount_code[]" /></td>
                    </tr>
                    <tr>
                        <th>Expiry Date</th>
                        <td><input type="date" name="expiry_date[]" /></td>
                    </tr>
                </table>
                <p><button type="button" id="add-coupon">Add Coupon</button></p>
                <p><input type="submit" value="Save Coupons" class="button-primary" /></p>
            </form>
            <h3>Use shortcode: [affiliate_coupon id="1"]</h3>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let row = 1;
            $('#add-coupon').click(function() {
                $('#acv-form table').append(
                    '<tr><td><input type="text" name="coupon_name[]" /></td>' +
                    '<td><input type="url" name="affiliate_link[]" /></td>' +
                    '<td><input type="text" name="discount_code[]" /></td>' +
                    '<td><input type="date" name="expiry_date[]" /></td></tr>'
                );
                row++;
            });
            $('#acv-form').submit(function(e) {
                e.preventDefault();
                var data = $(this).serialize();
                $.post(acv_ajax.ajax_url, {action: 'save_coupons', data: data}, function(resp) {
                    alert('Saved!');
                });
            });
        });
        </script>
        <?php
    }

    public function save_coupons() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $coupons = array();
        parse_str($_POST['data'], $coupons);
        update_option('acv_coupons', $coupons);
        wp_send_json_success();
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('acv_coupons', array());
        $id = intval($atts['id']);
        if (isset($coupons['coupon_name'][$id])) {
            $name = sanitize_text_field($coupons['coupon_name'][$id]);
            $link = esc_url($coupons['affiliate_link'][$id]);
            $code = sanitize_text_field($coupons['discount_code'][$id]);
            $expiry = sanitize_text_field($coupons['expiry_date'][$id]);
            if ($expiry && strtotime($expiry) < time()) {
                return '<p><em>Coupon expired.</em></p>';
            }
            $personalized = $code . '-' . substr(md5(auth()->user_id ?? 'guest'), 0, 4);
            return '<div class="acv-coupon"><h3>' . $name . '</h3><p>Code: <strong>' . $personalized . '</strong></p><a href="' . $link . '" class="button" target="_blank">Get Deal</a></div>';
        }
        return '';
    }

    public function activate() {
        add_option('acv_coupons', array());
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons, analytics & more! <a href="https://example.com/pro" target="_blank">Get Pro ($49)</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');