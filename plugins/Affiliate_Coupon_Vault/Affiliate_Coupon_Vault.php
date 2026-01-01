/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons for bloggers, boosting conversions with personalized discount codes and tracking.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
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
            update_option('acv_affiliates', sanitize_text_field($_POST['affiliates']));
            update_option('acv_coupon_code', sanitize_text_field($_POST['coupon_code']));
            echo '<div class="notice notice-success"><p>Coupon settings saved!</p></div>';
        }
        $affiliates = get_option('acv_affiliates', '');
        $coupon_code = get_option('acv_coupon_code', 'SAVE10');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links (JSON: {"brand":"url"})</th>
                        <td><textarea name="affiliates" rows="5" cols="50"><?php echo esc_textarea($affiliates); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Default Coupon Code</th>
                        <td><input type="text" name="coupon_code" value="<?php echo esc_attr($coupon_code); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => '',
        ), $atts);

        $affiliates = json_decode(get_option('acv_affiliates', '{}'), true);
        $coupon_code = get_option('acv_coupon_code', 'SAVE10');
        $brand = $atts['brand'];

        if (!isset($affiliates[$brand])) {
            return '<p>No coupon available for ' . esc_html($brand) . '</p>';
        }

        $link = $affiliates[$brand];
        $tracking_link = add_query_arg('coupon', $coupon_code, $link);

        ob_start();
        ?>
        <div class="acv-coupon">
            <h3>Exclusive Coupon: <strong><?php echo esc_html($coupon_code); ?></strong></h3>
            <p>Save with our special deal! <a href="<?php echo esc_url($tracking_link); ?}" target="_blank" class="acv-button">Shop Now & Save</a></p>
            <small>Tracked via Affiliate Coupon Vault</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('acv_affiliates')) {
            update_option('acv_affiliates', json_encode(array(
                'Amazon' => 'https://amazon.com/?tag=youraffiliateid',
                'Shopify' => 'https://shopify.com/?ref=yourref'
            )));
        }
    }
}

new AffiliateCouponVault();

// Create assets directories if missing
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets/';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}

// Sample style.css content
$css = ".acv-coupon { border: 2px dashed #0073aa; padding: 20px; background: #f9f9f9; text-align: center; margin: 20px 0; }
.acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }";
file_put_contents($assets_dir . 'style.css', $css);

// Sample script.js content
$js = "jQuery(document).ready(function($) {
    $('.acv-coupon a').click(function() {
        gtag('event', 'coupon_click', {'brand': $(this).data('brand')});
    });
});";
file_put_contents($assets_dir . 'script.js', $js);

?>