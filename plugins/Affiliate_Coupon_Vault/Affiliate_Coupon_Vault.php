/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generate and manage exclusive affiliate coupons to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('acv-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_style('acv-admin-style');
    }

    public function admin_menu() {
        add_menu_page('Affiliate Coupons', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Coupon Code: SAVE20\nAffiliate Link: https://affiliate-link.com\nDescription: 20% off first purchase");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="80" placeholder="Enter coupons one per line: Code|Affiliate Link|Description|Uses Left"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Format: Code|Affiliate Link|Description|Uses Left (e.g., SAVE20|https://example.com|20% off|Unlimited)</p>
                <p><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Shortcode Usage</h2>
            <p>Use <code>[affiliate_coupon id="1"]</code> to display coupons. Premium: Auto-rotation and tracking.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons = explode("\n", get_option('acv_coupons', ''));
        if (empty($coupons)) return '';

        $lines = array_filter(array_map('trim', $coupons));
        $coupon_data = array_map(function($line) {
            $parts = explode('|', $line, 4);
            return array(
                'code' => isset($parts) ? trim($parts) : '',
                'link' => isset($parts[1]) ? esc_url(trim($parts[1])) : '#',
                'desc' => isset($parts[2]) ? trim($parts[2]) : '',
                'uses' => isset($parts[3]) ? trim($parts[3]) : 'Unlimited'
            );
        }, $lines);

        if (empty($coupon_data)) return '';

        // Pick first for demo (premium: random/rotate)
        $coupon = $coupon_data;

        ob_start();
        ?>
        <div style="border: 2px dashed #0073aa; padding: 20px; background: #f9f9f9; text-align: center; max-width: 400px;">
            <h3 style="color: #0073aa;">🎉 Exclusive Deal!</h3>
            <p><strong><?php echo esc_html($coupon['desc']); ?></strong></p>
            <p><strong>Code: <span style="background: #0073aa; color: white; padding: 5px 10px; font-size: 1.2em; border-radius: 5px;"><?php echo esc_html($coupon['code']); ?></span></strong></p>
            <p>Uses left: <?php echo esc_html($coupon['uses']); ?></p>
            <a href="<?php echo $coupon['link']; ?>" target="_blank" style="background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Get Deal Now & Save!</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "WELCOME10|https://your-affiliate-link.com|10% off first order|50");
        }
    }
}

new AffiliateCouponVault();

// Premium upsell notice
function acv_admin_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, click tracking, auto-rotation, and analytics for $49/year. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');

// Prevent direct access
if (!defined('ABSPATH')) exit;