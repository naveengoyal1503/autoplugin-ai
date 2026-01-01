/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Easily create, manage, and display exclusive affiliate coupons with auto-expiring promo codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('acv-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_style('acv-admin-style');
    }

    public function admin_menu() {
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Coupons (JSON)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea><br>
                        Format: [{ "title": "10% Off Sitewide", "code": "AFF10", "affiliate_link": "https://example.com", "expiry": "2026-12-31", "description": "Exclusive deal" }]</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[acv_coupon id="1"]</code> or <code>[acv_coupon]</code> for random.</p>
            <p><strong>Pro Features:</strong> Unlimited coupons, analytics, auto-expiry checks, custom designs. <a href="https://example.com/pro">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons_json = get_option('acv_coupons', '');
        if (empty($coupons_json)) return '<p>No coupons configured. <a href="/wp-admin/admin.php?page=affiliate-coupon-vault">Set up now</a>.</p>';
        $coupons = json_decode($coupons_json, true);
        if (empty($coupons)) return '<p>Invalid coupon format.</p>';

        if ($atts['id']) {
            $coupon = $coupons[$atts['id']] ?? $coupons;
        } else {
            $coupon = $coupons[array_rand($coupons)];
        }

        $today = date('Y-m-d');
        if ($coupon['expiry'] && $today > $coupon['expiry']) {
            return '<div class="acv-expired">Coupon expired!</div>';
        }

        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; margin: 20px 0;">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($coupon['code']); ?></code></p>
            <?php if ($coupon['description']) : ?>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <?php endif; ?>
            <a href="<?php echo esc_url($coupon['affiliate_link']); ?}" target="_blank" class="button button-primary" style="margin-top: 10px;">Grab Deal &nbsp;→</a>
            <?php if ($coupon['expiry']) : ?>
            <p style="font-size: 12px; color: #666;">Expires: <?php echo esc_html($coupon['expiry']); ?></p>
            <?php endif; ?>
        </div>
        <style>
        .acv-coupon { max-width: 400px; }
        .acv-expired { color: #d63638; padding: 20px; background: #ffebee; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', json_encode(array(
                array(
                    'title' => 'Sample 20% Off',
                    'code' => 'WP20',
                    'affiliate_link' => 'https://example.com',
                    'expiry' => '2026-12-31',
                    'description' => 'Test coupon for demo.'
                )
            )));
        }
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="notice notice-info">
        <p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, click tracking, and more! <a href="https://example.com/pro">Get Pro</a></p>
    </div>
    <?php
}
add_action('admin_notices', 'acv_admin_notice');

?>