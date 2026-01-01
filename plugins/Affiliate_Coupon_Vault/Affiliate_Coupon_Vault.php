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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'code' => 'SAVE10',
            'discount' => '10%',
            'link' => '#',
            'expires' => '',
        ), $atts);

        $expires = !empty($atts['expires']) ? date('M d, Y', strtotime($atts['expires'])) : 'No expiration';

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>">
            <div class="coupon-header">
                <span class="coupon-code"><?php echo esc_html($atts['code']); ?></span>
                <span class="coupon-discount"><?php echo esc_html($atts['discount']); ?> OFF</span>
            </div>
            <div class="coupon-details">
                <p>Exclusive deal for our readers! Click to copy code and save.</p>
                <a href="<?php echo esc_url($atts['link']); ?}" target="_blank" class="coupon-button" rel="nofollow">Get Deal & Copy Code</a>
                <small>Expires: <?php echo esc_html($expires); ?></small>
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
            update_option('affiliate_coupon_vault_settings', $_POST['affiliate_coupon_vault_settings']);
        }
        $settings = get_option('affiliate_coupon_vault_settings', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th>Enable Pro Features</th>
                        <td><input type="checkbox" name="affiliate_coupon_vault_settings[pro]" <?php checked(isset($settings['pro'])); ?> disabled> <em>Upgrade to Pro for unlimited coupons</em></td>
                    </tr>
                    <tr>
                        <th>Max Free Coupons</th>
                        <td>5 (Pro: Unlimited)</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon affiliate="amazon" code="SAVE20" discount="20%" link="https://amazon.com/deal" expires="+30 days"]</code></p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

AffiliateCouponVault::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.affiliate-coupon-vault { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; max-width: 400px; }
.coupon-header { margin-bottom: 15px; }
.coupon-code { background: #ff4444; color: white; padding: 10px 20px; font-size: 24px; font-weight: bold; border-radius: 5px; }
.coupon-discount { background: #44ff44; color: #000; padding: 5px 15px; font-size: 18px; font-weight: bold; border-radius: 5px; margin-left: 10px; }
.coupon-button { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; }
.coupon-button:hover { background: #005a87; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.coupon-button').on('click', function(e) {
        e.preventDefault();
        var $coupon = $(this).closest('.affiliate-coupon-vault');
        var code = $coupon.find('.coupon-code').text();
        navigator.clipboard.writeText(code).then(function() {
            $(this).text('Code Copied! Redirecting...');
            setTimeout(() => { window.open($coupon.data('affiliate'), '_blank'); }, 1000);
        }.bind(this));
    });
});
</script>
<?php });