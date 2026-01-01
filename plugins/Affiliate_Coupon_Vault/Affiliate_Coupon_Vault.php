/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-styles', plugins_url('style.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('acv-script', plugins_url('script.js', __FILE__), array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_link' => '',
            'discount' => '10%',
            'code' => '',
            'expires' => '+30 days',
            'pro' => 'no'
        ), $atts);

        if ('yes' === $atts['pro'] && !get_option('acv_pro_active')) {
            return '<p>Upgrade to Pro for advanced coupons!</p>';
        }

        $code = !empty($atts['code']) ? $atts['code'] : 'ACV' . wp_generate_uuid4() . substr(md5(time()), 0, 5);
        $expires = strtotime($atts['expires']);
        $formatted_expires = date('M j, Y', $expires);

        ob_start();
        ?>
        <div class="acv-coupon" data-clipboard-text="<?php echo esc_attr($code); ?>">
            <h3>Exclusive Deal: <strong><?php echo esc_html($atts['discount']); ?> OFF</strong></h3>
            <p>Use code: <span class="acv-code"><?php echo esc_html($code); ?></span></p>
            <p>Expires: <?php echo esc_html($formatted_expires); ?></p>
            <a href="<?php echo esc_url($atts['affiliate_link']); ?}" target="_blank" class="acv-button">Shop Now & Save</a>
            <button class="acv-copy">Copy Code</button>
            <?php if ('no' === $atts['pro']) : ?>
            <p><small>Pro: Track clicks & analytics</small></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
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
        register_setting('acv_settings', 'acv_pro_active');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Pro Version Active</th>
                        <td><input type="checkbox" name="acv_pro_active" value="1" <?php checked(get_option('acv_pro_active')); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Usage: <code>[acv_coupon affiliate_link="https://example.com" discount="20%"]</code></p>
        </div>
        <?php
    }

    public function activate() {
        update_option('acv_pro_active', 0);
    }
}

// Initialize
AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_pro_notice() {
    if (!get_option('acv_pro_active') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons, analytics & more! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_pro_notice');

// Prevent direct access to files
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    wp_die('Access denied.');
}
?>