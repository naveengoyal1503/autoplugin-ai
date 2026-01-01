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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Amazon|10% off|AMZ10OFF\nShopify|Free trial|SHOPFREE");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (Format: Network|Discount|Code)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-rotation, and premium integrations for $49/year!</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = explode('\n', get_option('acv_coupons', ''));
        if (empty($coupons)) return '';

        $coupon = $coupons[array_rand($coupons)];
        list($network, $discount, $code) = explode('|', $coupon);

        $user_id = get_current_user_id();
        $personal_code = $code . ($user_id ? '-' . substr(md5($user_id), 0, 4) : '');

        ob_start();
        ?>
        <div class="acv-coupon" data-network="<?php echo esc_attr($network); ?>">
            <h3>Exclusive Deal: <?php echo esc_html($discount); ?> from <?php echo esc_html($network); ?>!</h3>
            <p>Use code: <strong><?php echo esc_html($personal_code); ?></strong></p>
            <a href="#" class="acv-copy-btn">Copy Code</a>
            <a href="https://<?php echo strtolower($network); ?>.com" class="acv-aff-link" target="_blank" rel="nofollow">Shop Now & Save</a>
            <small>Trackable affiliate link - Limited time!</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Amazon|10% off|AMZ10OFF\nShopify|Free trial|SHOPFREE");
        }
    }
}

AffiliateCouponVault::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
.acv-copy-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px; }
.acv-aff-link { background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
.acv-copy-btn:hover, .acv-aff-link:hover { opacity: 0.8; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.acv-copy-btn').click(function(e) {
        e.preventDefault();
        var code = $(this).siblings('p strong').text();
        navigator.clipboard.writeText(code).then(function() {
            $(this).text('Copied!');
        }.bind(this));
    });
});
</script>
<?php });