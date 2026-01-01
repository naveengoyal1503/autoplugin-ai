/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons to boost conversions and monetize your WordPress site.
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
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
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

    public function admin_scripts($hook) {
        if ($hook !== 'toplevel_page_exclusive-coupons') {
            return;
        }
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(document).ready(function($) { $("#add-coupon").click(function() { $("#coupons-list").append("<div class=\"form-group\"><input type=\"text\" name=\"coupon[\" + Date.now() + \"]\" placeholder=\"Coupon Code\"><input type=\"text\" name=\"afflink[\" + Date.now() + \"]\" placeholder=\"Affiliate Link\"><input type=\"date\" name=\"expiry[\" + Date.now() + \"]\" placeholder=\"Expiry\"><button type=\"button\" class=\"remove\">Remove</button></div>"); }); $(".remove").live("click", function() { $(this).parent().remove(); }); });');
        wp_enqueue_style('exclusive-coupons-admin', plugin_dir_url(__FILE__) . 'admin.css');
    }

    public function admin_page() {
        if (isset($_POST['save_coupons'])) {
            update_option('exclusive_coupons', $_POST['coupon']);
            update_option('exclusive_afflinks', $_POST['afflink']);
            update_option('exclusive_expiries', $_POST['expiry']);
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('exclusive_coupons', array());
        $afflinks = get_option('exclusive_afflinks', array());
        $expiries = get_option('exclusive_expiries', array());
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons</th>
                        <td>
                            <div id="coupons-list">
                                <?php for ($i = 0; $i < count($coupons); $i++): ?>
                                    <div class="form-group">
                                        <input type="text" name="coupon[<?php echo $i; ?>" value="<?php echo esc_attr($coupons[$i]); ?>" placeholder="Coupon Code" />
                                        <input type="text" name="afflink[<?php echo $i; ?>" value="<?php echo esc_attr($afflinks[$i]); ?>" placeholder="Affiliate Link" />
                                        <input type="date" name="expiry[<?php echo $i; ?>" value="<?php echo esc_attr($expiries[$i]); ?>" />
                                        <button type="button" class="remove">Remove</button>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <button type="button" id="add-coupon">Add Coupon</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons', 'primary', 'save_coupons'); ?>
            </form>
            <h2>Shortcode Usage</h2>
            <p>Use <code>[exclusive_coupon id="0"]</code> to display coupon (replace 0 with coupon index).</p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-expiry checks, WooCommerce integration. <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <style>
        .form-group { margin-bottom: 10px; }
        .form-group input { margin-right: 10px; width: 200px; }
        </style>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $id = intval($atts['id']);
        $coupons = get_option('exclusive_coupons', array());
        $afflinks = get_option('exclusive_afflinks', array());
        $expiries = get_option('exclusive_expiries', array());

        if (!isset($coupons[$id])) {
            return 'Coupon not found.';
        }

        $expiry = isset($expiries[$id]) ? $expiries[$id] : '';
        $expired = $expiry && strtotime($expiry) < current_time('timestamp');

        if ($expired) {
            return '<div class="exclusive-coupon expired">Coupon EXPIRED: ' . esc_html($coupons[$id]) . '</div>';
        }

        return '<div class="exclusive-coupon"><strong>Exclusive Deal:</strong> Use code <code>' . esc_html($coupons[$id]) . '</code> <a href="' . esc_url($afflinks[$id]) . '" target="_blank" rel="nofollow">Shop Now & Save!</a></div>';
    }

    public function activate() {
        if (!get_option('exclusive_coupons')) {
            update_option('exclusive_coupons', array());
        }
    }
}

new ExclusiveCouponsPro();

// Enqueue frontend styles
function exclusive_coupons_styles() {
    wp_enqueue_style('exclusive-coupons', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'exclusive_coupons_styles');

/* Pro Teaser */
function exclusive_coupons_pro_teaser() {
    if (!function_exists('is_pro_version')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro</strong> for unlimited coupons, click tracking, and more! <a href="https://example.com/pro">Learn More</a></p></div>';
    }
}
add_action('admin_notices', 'exclusive_coupons_pro_teaser');