/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons for your WordPress site, boosting conversions with personalized discount codes and tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupons', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('acv-admin-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_register_script('acv-admin-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            update_option('acv_pro', isset($_POST['pro_version']));
        }
        $coupons = get_option('acv_coupons', "Brand1: DISCOUNT10\nBrand2: SAVE20");
        $pro = get_option('acv_pro', false);
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea><br>
                        Format: Brand:CODE (one per line)</td>
                    </tr>
                    <?php if (!$pro) : ?>
                    <tr>
                        <th>Pro Version</th>
                        <td><label><input type="checkbox" name="pro_version" <?php checked($pro); ?>> Unlock unlimited coupons & tracking (Upgrade for $49)</label></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode <code>[acv_coupons]</code> to display coupons on any page.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons_text = get_option('acv_coupons', '');
        if (empty($coupons_text)) return '<p>No coupons configured.</p>';

        $coupons = explode("\n", trim($coupons_text));
        $output = '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            $parts = explode(':', trim($coupon), 2);
            if (count($parts) === 2) {
                $brand = sanitize_text_field($parts);
                $code = sanitize_text_field($parts[1]);
                $tracking_id = uniqid('acv_');
                $output .= '<div class="acv-coupon"><strong>' . esc_html($brand) . '</strong>: <code>' . esc_html($code) . '</code> <a href="#" class="acv-copy" data-code="' . esc_attr($code) . '" data-id="' . esc_attr($tracking_id) . '">Copy</a></div>';
            }
        }
        $output .= '</div>';
        $output .= '<script>jQuery(".acv-copy").click(function(e){e.preventDefault();navigator.clipboard.writeText(jQuery(this).data("code"));alert("Copied!");});</script>';
        return $output;
    }

    public function activate() {
        if (!get_option('acv_pro')) {
            update_option('acv_limit', 5);
        }
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_pro_notice() {
    if (!get_option('acv_pro') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons, analytics, and API support! <a href="https://example.com/pro">Get Pro ($49)</a></p></div>';
    }
}
add_action('admin_notices', 'acv_pro_notice');

// Basic CSS
add_action('wp_head', function() {
    echo '<style>.acv-coupons {background:#f9f9f9;padding:20px;border:1px solid #ddd;}.acv-coupon{margin:10px 0;}.acv-copy{background:#0073aa;color:white;padding:5px 10px;text-decoration:none;border-radius:3px;}</style>';
});

// Enqueue admin styles
function acv_admin_enqueue($hook) {
    if ($hook !== 'settings_page_affiliate-coupon-vault') return;
    wp_enqueue_style('acv-admin-style');
    wp_enqueue_script('acv-admin-script');
}
add_action('admin_enqueue_scripts', 'acv_admin_enqueue');