/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            wp_enqueue_script('jquery');
        }
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Coupon Code: SAVE10\nAffiliate Link: https://example.com/aff\nDescription: 10% off");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <p><label>Enter coupons (one per line: Code|Link|Description):</label></p>
                <textarea name="coupons" rows="10" cols="50" class="large-text"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Pro Upgrade: Unlimited coupons, auto-generation, analytics. <a href="#" style="color:#0073aa;">Upgrade Now</a></p>
            <p>Use shortcode: <code>[affiliate_coupon id="1"]</code></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons = explode('\n', get_option('acv_coupons', ''));
        $id = intval($atts['id'] ?? 0);
        if (isset($coupons[$id])) {
            $parts = explode('|', $coupons[$id]);
            if (count($parts) >= 3) {
                return '<div style="border:2px solid #0073aa;padding:20px;background:#f9f9f9;border-radius:5px;"><h3>' . esc_html($parts[2]) . '</h3><p><strong>Code:</strong> ' . esc_html($parts) . '</p><a href="' . esc_url($parts[1]) . '" class="button button-primary" target="_blank" rel="nofollow">Get Deal (Affiliate)</a></div>';
            }
        }
        return 'Coupon not found.';
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "SAVE20|https://example.com/aff1|20% Off First Purchase\nDEAL15|https://example.com/aff2|15% Sitewide Discount");
        }
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons & analytics! <a href="#" style="color:#0073aa;">Learn More</a></p></div>';
}
add_action('admin_notices', 'acv_admin_notice');