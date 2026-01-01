/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Affiliate Coupons Pro
 * Plugin URI: https://example.com/affiliate-coupons-pro
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-affiliate-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveAffiliateCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('eac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('eac_enable') !== 'yes') return;
        wp_register_style('eac-styles', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function enqueue_scripts() {
        if (get_option('eac_enable') === 'yes') {
            wp_enqueue_style('eac-styles');
        }
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'eac-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('eac_enable', sanitize_text_field($_POST['eac_enable']));
            update_option('eac_api_key', sanitize_text_field($_POST['eac_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Coupons Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Enable Plugin</th>
                        <td><input type="checkbox" name="eac_enable" value="yes" <?php checked(get_option('eac_enable'), 'yes'); ?> /></td>
                    </tr>
                    <tr>
                        <th>Affiliate API Key (Premium)</th>
                        <td><input type="text" name="eac_api_key" value="<?php echo esc_attr(get_option('eac_api_key')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use <code>[eac_coupon id="1"]</code> shortcode to display coupons. Premium unlocks unlimited coupons and tracking.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 1), $atts);
        $coupons = get_option('eac_coupons', array());
        $coupon = isset($coupons[$atts['id']]) ? $coupons[$atts['id']] : array(
            'title' => 'Exclusive Deal',
            'code' => 'SAVE' . wp_generate_password(4, false),
            'discount' => '20% OFF',
            'affiliate_link' => 'https://example.com/affiliate',
            'expires' => date('Y-m-d', strtotime('+30 days'))
        );
        ob_start();
        ?>
        <div class="eac-coupon" style="border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; text-align: center;">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p><strong>Code: <?php echo esc_html($coupon['code']); ?></strong></p>
            <p><?php echo esc_html($coupon['discount']); ?> - Expires: <?php echo esc_html($coupon['expires']); ?></p>
            <a href="<?php echo esc_url($coupon['affiliate_link']); ?>" target="_blank" class="button button-primary" style="padding: 10px 20px;">Grab Deal & Track Commission</a>
        </div>
        <style>
        .eac-coupon { max-width: 400px; margin: 20px auto; border-radius: 10px; }
        .eac-coupon .button { font-size: 18px; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('eac_enable', 'yes');
        add_option('eac_coupons', array());
    }
}

new ExclusiveAffiliateCouponsPro();

// Premium teaser
function eac_premium_teaser() {
    if (!get_option('eac_api_key')) {
        echo '<div class="notice notice-info"><p>Upgrade to Pro for unlimited coupons, analytics, and auto-generation! <a href="https://example.com/premium">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'eac_premium_teaser');