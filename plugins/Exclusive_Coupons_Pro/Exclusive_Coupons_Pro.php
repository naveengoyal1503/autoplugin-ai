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
        wp_register_style('ecp-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css', array(), '1.0.0');
        wp_register_script('ecp-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('ecp_coupons', "Code: SAVE20\nAffiliate Link: https://example.com/affiliate\nDescription: 20% off first purchase");
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="80" class="large-text"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Format each coupon: Code: CODE<br>Affiliate Link: URL<br>Description: Text<br>--- (separate coupons)</p>
                <p><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[exclusive_coupon id="1"]</code> or <code>[exclusive_coupon]</code> for random.</p>
            <p>Premium: Unlock unlimited coupons, click tracking, and analytics.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'random'), $atts);
        $coupons_text = get_option('ecp_coupons', '');
        if (empty($coupons_text)) return '<p>No coupons configured. Go to Settings > Coupons Pro.</p>';

        $coupons = explode('---', $coupons_text);
        $selected = array();
        if ($atts['id'] === 'random') {
            $selected_coupon = trim($coupons[array_rand($coupons)]);
        } else {
            $selected_coupon = trim($coupons[(int)$atts['id'] - 1] ?? $coupons);
        }
        parse_str(str_replace(array("Code: ", "Affiliate Link: ", "Description: "), array("code=", "link=", "desc="), $selected_coupon), $selected);

        if (empty($selected['link'])) return '';

        ob_start();
        ?>
        <div class="ecp-coupon" style="border: 2px dashed #0073aa; padding: 20px; background: #f9f9f9; border-radius: 5px;">
            <h3 style="color: #0073aa;">🎉 Exclusive Deal!</h3>
            <p><strong>Use Code: <span style="background: #0073aa; color: white; padding: 5px 10px; border-radius: 3px;"><?php echo esc_html($selected['code'] ?? 'SAVE20'); ?></span></strong></p>
            <p><?php echo esc_html($selected['desc'] ?? 'Special discount for our readers!'); ?></p>
            <a href="<?php echo esc_url($selected['link']); ?}" target="_blank" class="button" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;">Get Deal Now &rsaquo;</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', "Code: SAVE20\nAffiliate Link: https://example.com/affiliate\nDescription: 20% off first purchase\n---\nCode: WELCOME10\nAffiliate Link: https://example.com/affiliate2\nDescription: $10 off your order");
        }
    }
}

new ExclusiveCouponsPro();

// Premium upsell notice
function ecp_admin_notice() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="notice notice-info">
        <p><strong>Exclusive Coupons Pro:</strong> Upgrade to Pro for click tracking, unlimited coupons, and analytics! <a href="https://example.com/premium" target="_blank">Get Pro</a></p>
    </div>
    <?php
}
add_action('admin_notices', 'ecp_admin_notice');

// Enqueue styles for frontend
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_style('dashicons', '
        .ecp-coupon { max-width: 400px; margin: 20px 0; }
        .ecp-coupon .button:hover { background: #005a87; }
    ');
});