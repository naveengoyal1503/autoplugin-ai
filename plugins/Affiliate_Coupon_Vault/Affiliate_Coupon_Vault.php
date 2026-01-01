/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons to boost conversions and affiliate earnings.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugins_url('style.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugins_url('script.js', __FILE__), array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'discount' => '10%',
            'code' => 'SAVE10',
            'link' => '#',
            'expires' => '',
        ), $atts);

        $expires = !empty($atts['expires']) ? date('M j, Y', strtotime($atts['expires'])) : 'Never';

        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <div class="coupon-header">
                <h3><?php echo esc_html($atts['affiliate']); ?> Exclusive Deal</h3>
            </div>
            <div class="coupon-body">
                <span class="discount"><?php echo esc_html($atts['discount']); ?> OFF</span>
                <span class="code"><?php echo esc_html($atts['code']); ?></span>
                <a href="<?php echo esc_url($atts['link']); ?}" class="coupon-link" target="_blank">Get Deal</a>
                <small class="expires">Expires: <?php echo esc_html($expires); ?></small>
            </div>
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
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_settings');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('affiliate_coupon_vault_settings', sanitize_text_field($_POST['api_key']));
        }
        $settings = get_option('affiliate_coupon_vault_settings', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Pro API Key (for upgrades)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($settings); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Upgrade to Pro for unlimited coupons and analytics: <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function activate() {
        // Create default options
        add_option('affiliate_coupon_vault_settings', '');
    }
}

// Initialize
AffiliateCouponVault::get_instance();

// Pro Upsell Notice
function affiliate_coupon_vault_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock unlimited coupons with <strong>Affiliate Coupon Vault Pro</strong>! <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'affiliate_coupon_vault_pro_notice');