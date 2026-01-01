/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons, track usage, and boost conversions on your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
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
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_menu() {
        add_options_page(
            'Exclusive Coupons Pro',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', "Code1|Brand1|20% off|https://affiliate.link1\nCode2|Brand2|$10 off|https://affiliate.link2");
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post">
                <p><label>Enter coupons (format: Code|Brand|Discount|Affiliate Link), one per line:</label></p>
                <textarea name="coupons" rows="10" cols="80" class="large-text"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[exclusive_coupon id="1"]</code> (ID starts from 1)</p>
            <p><strong>Pro Features:</strong> Usage tracking, analytics, unlimited coupons. <a href="https://example.com/upgrade">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 1), $atts);
        $id = intval($atts['id']);
        $coupons = $this->get_coupons();
        if (!isset($coupons[$id - 1])) {
            return 'Coupon not found.';
        }
        list($code, $brand, $discount, $link) = explode('|', $coupons[$id - 1], 4);
        $clicks = get_option("ecp_clicks_$id", 0);
        return '<div class="exclusive-coupon" style="border:2px solid #0073aa;padding:20px;background:#f9f9f9;border-radius:5px;"><h3>Exclusive Deal: ' . esc_html($brand) . '</h3><p><strong>' . esc_html($code) . '</strong> - ' . esc_html($discount) . '</p><p><a href="' . esc_url($link) . '" target="_blank" class="button" style="background:#0073aa;color:white;padding:10px 20px;text-decoration:none;border-radius:3px;">Get Deal (Used ' . $clicks . ' times)</a></p></div>';
    }

    private function get_coupons() {
        $data = get_option('ecp_coupons', '');
        return array_filter(array_map('trim', explode("\n", $data)));
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', "WELCOME20|ExampleBrand|20% OFF|https://your-affiliate-link.com\nSAVE10|ShopNow|Save $10|https://your-affiliate-link2.com");
        }
    }
}

new ExclusiveCouponsPro();

// Enqueue styles if needed
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_style('dashicons', '.exclusive-coupon { max-width: 300px; margin: 20px 0; }');
});

// Track clicks (free version limit: 100 total)
add_action('wp_loaded', function() {
    if (isset($_GET['ecp_click'])) {
        $id = intval($_GET['ecp_click']);
        $clicks = get_option("ecp_clicks_$id", 0) + 1;
        update_option("ecp_clicks_$id", $clicks);
        $total_clicks = get_option('ecp_total_clicks', 0) + 1;
        if ($total_clicks <= 100) {
            update_option('ecp_total_clicks', $total_clicks);
        }
        $coupons = (new ExclusiveCouponsPro())->get_coupons();
        if (isset($coupons[$id - 1])) {
            list($code, $brand, $discount, $link) = explode('|', $coupons[$id - 1], 4);
            wp_redirect(esc_url_raw($link));
            exit;
        }
    }
});
