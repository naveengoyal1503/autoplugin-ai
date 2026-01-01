/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons to boost conversions and monetize your site.
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
        wp_register_script('ecp-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupons'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons_data']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', '');
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <textarea name="coupons_data" rows="20" cols="80" placeholder="Format: Code|Affiliate Link|Description|Discount %&#10;SAVE10|https://affiliate.link|10% off first purchase|10"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">One coupon per line: Code|Affiliate Link|Description|Discount %</p>
                <p><input type="submit" name="save_coupons" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[exclusive_coupon code="SAVE10"]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlock click tracking, unlimited coupons, and analytics for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        $coupons = explode("\n", get_option('ecp_coupons', ''));
        foreach ($coupons as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) >= 4 && $parts === $atts['code']) {
                $link = esc_url($parts[1]);
                $desc = esc_html($parts[2]);
                $discount = esc_html($parts[3]);
                return '<div class="ecp-coupon"><a href="' . $link . '" target="_blank" class="ecp-button">Use ' . $atts['code'] . ' - ' . $discount . ' OFF</a><p>' . $desc . '</p></div>';
            }
        }
        return '<p>Coupon not found.</p>';
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', "");
        }
    }
}

new ExclusiveCouponsPro();

/* Pro Upsell Notice */
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && !defined('ECP_PRO')) {
        echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Upgrade to Pro for advanced features like click tracking and unlimited coupons! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
    }
});

/* Basic CSS */
add_action('wp_head', function() {
    echo '<style>.ecp-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }.ecp-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block; }</style>';
});

/* Freemium Check - Define ECP_PRO in pro version */
if (defined('ECP_PRO')) {
    // Pro features here
}