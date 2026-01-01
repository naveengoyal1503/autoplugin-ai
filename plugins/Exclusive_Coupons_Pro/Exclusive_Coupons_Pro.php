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
        wp_register_style('ecp-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_style('ecp-admin-style');
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('ecp_coupons', "Code: SAVE20\nLink: https://example.com/affiliate-link\nDescription: 20% off first purchase");
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="50" style="width:100%;"><?php echo esc_textarea($coupons); ?></textarea>
                <p>Format: Code: CODE<br>Link: URL<br>Description: Text<br><br>(One coupon per block)</p>
                <p><?php submit_button(); ?></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[exclusive_coupon id="1"]</code> or <code>[exclusive_coupon]</code> for random.</p>
            <p>Upgrade to Pro for unlimited coupons, analytics, and auto-expiration!</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons_text = get_option('ecp_coupons', '');
        if (empty($coupons_text)) return 'No coupons configured. <a href="' . admin_url('admin.php?page=exclusive-coupons') . '">Set up now</a>.';

        $coupons = explode('\n\n', $coupons_text);
        $id = isset($atts['id']) ? intval($atts['id']) - 1 : rand(0, count($coupons) - 1);
        if (!isset($coupons[$id])) return 'Coupon not found.';

        $lines = explode('\n', trim($coupons[$id]));
        $coupon = array();
        foreach ($lines as $line) {
            if (strpos($line, 'Code:') === 0) $coupon['code'] = substr($line, 5);
            elseif (strpos($line, 'Link:') === 0) $coupon['link'] = substr($line, 5);
            elseif (strpos($line, 'Description:') === 0) $coupon['desc'] = substr($line, 11);
        }

        if (empty($coupon['link'])) return 'Invalid coupon.';

        return '<div class="exclusive-coupon" style="border:2px solid #0073aa;padding:20px;background:#f9f9f9;border-radius:5px;">
            <h3>' . esc_html($coupon['desc']) . '</h3>
            <p><strong>Exclusive Code:</strong> <code>' . esc_html($coupon['code']) . '</code></p>
            <a href="' . esc_url($coupon['link']) . '" target="_blank" class="button button-primary" style="padding:10px 20px;font-size:16px;">Grab Deal Now</a>
        </div>';
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', "Code: SAVE20\nLink: https://example.com/affiliate-link\nDescription: 20% off first purchase\n\nCode: WELCOME10\nLink: https://example.com/aff-link2\nDescription: 10% off sitewide");
        }
    }
}

new ExclusiveCouponsPro();

// Pro upsell notice
function ecp_upsell_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Unlock unlimited coupons, click tracking, and premium templates! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'ecp_upsell_notice');