/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons to boost your commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
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
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function activate() {
        add_option('acv_coupons', array());
        add_option('acv_pro_version', false);
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        register_setting('acv_settings', 'acv_pro_version');
        if (isset($_POST['acv_submit'])) {
            update_option('acv_coupons', sanitize_text_field($_POST['acv_coupons']));
        }
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Affiliate Coupon Vault', 'affiliate-coupon-vault'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                $coupons = get_option('acv_coupons', array());
                ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Coupons (JSON format: {"code":"discount","link":"url","expires":"YYYY-MM-DD"})', 'affiliate-coupon-vault'); ?></th>
                        <td>
                            <textarea name="acv_coupons" rows="10" cols="50"><?php echo esc_textarea(json_encode($coupons)); ?></textarea>
                            <p class="description"><?php _e('Enter coupons as JSON array. Pro version supports unlimited and auto-generation.', 'affiliate-coupon-vault'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-expiration & WooCommerce integration for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('acv_coupons', array());
        if (empty($coupons)) return '<p>No coupons available.</p>';

        $coupon = $coupons[array_rand($coupons)];
        $today = date('Y-m-d');
        if (isset($coupon['expires']) && $coupon['expires'] < $today) {
            return '<p>This coupon has expired.</p>';
        }

        $output = '<div class="acv-coupon" style="border: 2px dashed #007cba; padding: 20px; margin: 10px 0; background: #f9f9f9;">
            <h3>Exclusive Deal! Use Code: <strong>' . esc_html($coupon['code']) . '</strong></h3>
            <p>Save ' . esc_html($coupon['discount']) . '!</p>
            <a href="' . esc_url($coupon['link']) . '" target="_blank" class="button button-primary" style="padding: 10px 20px;">Grab Deal Now</a>
        </div>';
        return $output;
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }
}

AffiliateCouponVault::get_instance();

// Pro teaser
add_action('admin_notices', function() {
    if (!get_option('acv_pro_version')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Upgrade for advanced features! <a href="https://example.com/pro" target="_blank">Learn More</a></p></div>';
    }
});

// Mini CSS
add_action('wp_head', function() {
    echo '<style>.acv-coupon {max-width: 400px; font-family: Arial, sans-serif;} .acv-coupon h3 {margin-top: 0; color: #007cba;}</style>';
});