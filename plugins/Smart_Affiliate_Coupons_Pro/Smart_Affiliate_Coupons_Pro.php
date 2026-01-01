/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates, tracks, and displays personalized affiliate coupons with conversion-optimized popups and shortcodes.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCoupons {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sac-style', plugin_dir_url(__FILE__) . 'sac-style.css', array(), '1.0.0');
        wp_localize_script('sac-script', 'sac_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupons', 'Aff Coupons', 'manage_options', 'smart-affiliate-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('sac_coupons', array());
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON: code,afflink,discount,expiry)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea(json_encode($coupons)); ?></textarea></td>
                    </tr>
                </table>
                <p><input type="submit" name="sac_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Upgrade to Pro for unlimited coupons & analytics! <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function ajax_save_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'sac_nonce')) {
            wp_die('Security check failed');
        }
        $clicks = get_option('sac_clicks', array());
        $clicks[$_POST['code']] = isset($clicks[$_POST['code']]) ? $clicks[$_POST['code']] + 1 : 1;
        update_option('sac_clicks', $clicks);
        wp_redirect($_POST['afflink']);
        exit;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('sac_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return '';
        }
        $coupon = $coupons[$atts['id']];
        $clicks = get_option('sac_clicks', array())[$coupon['code']] ?? 0;
        return '<div class="sac-coupon" data-code="' . esc_attr($coupon['code']) . '" data-afflink="' . esc_url($coupon['afflink']) . '">' .
                '<h3>' . esc_html($coupon['code']) . ' - ' . esc_html($coupon['discount']) . '% OFF</h3>' .
                '<p>Clicked: ' . $clicks . ' times</p>' .
                '<button class="sac-btn">Redeem Now</button></div>';
    }

    public function activate() {
        add_option('sac_coupons', array(
            array('SAVE10', 'https://example.com/aff1', '10%', '2026-12-31'),
            array('DEAL20', 'https://example.com/aff2', '20%', '2026-06-30')
        ));
    }

    public function admin_scripts($hook) {
        if ($hook != 'settings_page_smart-affiliate-coupons') return;
        wp_enqueue_script('jquery');
    }
}

new SmartAffiliateCoupons();

// Inline JS and CSS for self-contained
add_action('wp_head', function() {
    echo '<script> jQuery(document).ready(function($) { $(".sac-btn").click(function() { var code = $(this).closest(".sac-coupon").data("code"); var afflink = $(this).closest(".sac-coupon").data("afflink"); $.post(sac_ajax.ajaxurl, {action: "save_coupon", code: code, afflink: afflink, nonce: "' . wp_create_nonce('sac_nonce') . '"}, function() { window.location = afflink; }); }); }); </script>';
    echo '<style>.sac-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 10px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }.sac-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }.sac-btn:hover { background: #005a87; }</style>';
});