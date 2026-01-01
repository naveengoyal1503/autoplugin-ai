/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons for your WordPress site, boosting conversions with personalized discount codes from partner brands.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
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
        wp_register_style('acv-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_register_script('acv-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page'),
            'dashicons-cart',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['acv_save_coupon'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Brand1: SAVE10|Brand2: DISCOUNT20");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Coupons (Format: Brand:Code|Brand2:Code2)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons', 'primary', 'acv_save_coupon'); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon brand="Brand1" affiliate_url="https://your-affiliate-link.com"]</code></p>
            <p><strong>Premium:</strong> Unlock unlimited coupons, auto-generation, click tracking, and integrations. <a href="#" onclick="alert('Upgrade to Pro!')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => '',
            'affiliate_url' => '#',
        ), $atts);

        $coupons = get_option('acv_coupons', "");
        if (empty($coupons)) return '<p>No coupons configured.</p>';

        $coupon_list = explode('|', $coupons);
        foreach ($coupon_list as $coupon) {
            $parts = explode(':', $coupon);
            if (trim($parts) === $atts['brand']) {
                $code = trim($parts[1]);
                return '<div class="acv-coupon"><strong>Exclusive Deal:</strong> Use code <code>' . esc_html($code) . '</code> <a href="' . esc_url($atts['affiliate_url']) . '" target="_blank" rel="nofollow" class="button button-primary">Shop Now & Save!</a></div>';
            }
        }
        return '<p>Coupon not found for ' . esc_html($atts['brand']) . '.</p>';
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Amazon: AFF10|Shopify: WP20");
        }
    }
}

// Enqueue styles
add_action('admin_enqueue_scripts', function($hook) {
    if ('toplevel_page_affiliate-coupon-vault' !== $hook) return;
    wp_enqueue_style('acv-admin-style');
    wp_enqueue_script('acv-admin-script');
});

// Add frontend styles
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_style('dashicons', '
        .acv-coupon { background: #fff3cd; padding: 15px; border-left: 4px solid #ffeaa7; margin: 10px 0; }
        .acv-coupon code { background: #ffc107; padding: 2px 6px; border-radius: 3px; }
    ');
});

AffiliateCouponVault::get_instance();

// Premium teaser
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Upgrade to Pro for click tracking, unlimited coupons, and auto-API generation! <a href="#" onclick="alert(\'Pro features unlocked!\')">Learn More</a></p></div>';
});