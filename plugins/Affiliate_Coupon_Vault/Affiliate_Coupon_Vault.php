/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupon codes and deals to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
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
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            update_option('acv_pro', isset($_POST['pro_version']) ? 'yes' : 'no');
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Coupon1|Discount10|https://affiliate.link1.com
Coupon2|Save20|https://affiliate.link2.com");
        $pro = get_option('acv_pro', 'no');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (Code|Description|Affiliate Link, one per line)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                    <?php if ($pro !== 'yes') { ?>
                    <tr>
                        <th>Pro Version</th>
                        <td><label><input type="checkbox" name="pro_version" value="1"> Unlock unlimited coupons & analytics (Upgrade required)</label></td>
                    </tr>
                    <?php } ?>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Get unlimited coupons, analytics, custom branding for $49/year. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons_str = get_option('acv_coupons', '');
        $coupons = explode("\n", trim($coupons_str));
        $coupons = array_slice(array_filter(array_map('trim', $coupons)), 0, $atts['limit']);
        if (empty($coupons)) return '<p>No coupons available.</p>';

        $output = '<div class="acv-vault">';
        foreach ($coupons as $coupon) {
            $parts = explode('|', $coupon, 3);
            if (count($parts) === 3) {
                $code = esc_html($parts);
                $desc = esc_html($parts[1]);
                $link = esc_url($parts[2]);
                $output .= '<div class="acv-coupon"><strong>' . $code . '</strong>: ' . $desc . ' <a href="' . $link . '" target="_blank" rel="nofollow">Shop Now</a></div>';
            }
        }
        $output .= '</div>';
        if (get_option('acv_pro') !== 'yes') {
            $output .= '<p><a href="https://example.com/pro" target="_blank">Upgrade to Pro for more features</a></p>';
        }
        return $output;
    }

    public function activate() {
        add_option('acv_coupons', "WELCOME10|10% Off First Purchase|https://youraffiliatelink.com/?ref=wpuser");
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_admin_notice() {
    if (get_option('acv_pro') !== 'yes') {
        echo '<div class="notice notice-info"><p>Unlock <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons and analytics! <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');

// Prevent direct access to style.css and script.js - in real plugin, include them
// For single-file, inline or assume they exist. Here, minimal CSS/JS simulation via wp_add_inline_style/script
add_action('wp_head', function() {
    if (is_admin()) return;
    echo '<style>.acv-vault {border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9;}.acv-coupon {margin-bottom: 10px; font-size: 16px;}</style>';
    echo '<script>jQuery(document).ready(function($) { $(".acv-coupon a").on("click", function() { console.log("Coupon clicked!"); }); });</script>';
});