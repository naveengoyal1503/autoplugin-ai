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
        wp_register_script('ecp-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'), 'dashicons-tickets-alt');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', '');
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Coupons (JSON format)</th>
                        <td><textarea name="coupons" rows="10" cols="50" class="large-text"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Shortcode Usage</h2>
            <p>Use <code>[exclusive_coupon id="1"]</code> to display a coupon. Pro version unlocks click tracking.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => '1'), $atts);
        $coupons = json_decode(get_option('ecp_coupons', '[]'), true);
        if (isset($coupons[$atts['id'] - 1])) {
            $coupon = $coupons[$atts['id'] - 1];
            $clicks = get_option('ecp_clicks_' . $atts['id'], 0);
            $track_url = $coupon['url'] . '?ref=ecp-' . $atts['id'] . '&clicks=' . $clicks;
            return '<div class="ecp-coupon" style="border:2px solid #007cba;padding:20px;background:#f9f9f9;border-radius:5px;"><h3>' . esc_html($coupon['title']) . '</h3><p>Code: <strong>' . esc_html($coupon['code']) . '</strong></p><p><a href="' . esc_url($track_url) . '" target="_blank" class="button button-primary">Get Deal (Tracked)</a></p><p style="font-size:12px;color:#666;">Used by ' . intval($clicks) . ' users</p></div>';
        }
        return 'Coupon not found.';
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', json_encode(array(
                array('title' => 'Sample 50% Off', 'code' => 'SAMPLE50', 'url' => 'https://example.com'),
                array('title' => 'Free Trial', 'code' => 'FREETRIAL', 'url' => 'https://example.com/trial')
            )));
        }
    }
}

new ExclusiveCouponsPro();

// Track clicks
add_action('init', function() {
    if (isset($_GET['ref']) && preg_match('/^ecp-(\d+)/', $_GET['ref'], $matches)) {
        $id = intval($matches[1]);
        $clicks = get_option('ecp_clicks_' . $id, 0) + 1;
        update_option('ecp_clicks_' . $id, $clicks);
        wp_redirect(remove_query_arg(array('ref', 'clicks')));
        exit;
    }
});

// Enqueue styles
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'toplevel_page_exclusive-coupons') {
        wp_enqueue_style('ecp-admin-style');
        wp_enqueue_script('ecp-admin-script');
    }
});

// Pro upsell notice
add_action('admin_notices', function() {
    if (get_current_screen()->id === 'toplevel_page_exclusive-coupons') {
        echo '<div class="notice notice-info"><p><strong>Pro Version:</strong> Unlimited coupons, analytics dashboard, email capture, and integrations. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p></div>';
    }
});