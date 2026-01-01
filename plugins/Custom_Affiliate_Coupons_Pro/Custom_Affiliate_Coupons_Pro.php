/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Affiliate Coupons Pro
 * Plugin URI: https://example.com/custom-affiliate-coupons-pro
 * Description: Generate personalized affiliate coupons, track clicks, and boost conversions on your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class CustomAffiliateCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('cacp-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_style('cacp-admin-style');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'cacp-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('cacp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('cacp_coupons', '');
        ?>
        <div class="wrap">
            <h1>Custom Affiliate Coupons Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON format: {"code":"CODE","afflink":"URL","desc":"Description"})</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon id="1"]</code> (Premium: click tracking).</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons = json_decode(get_option('cacp_coupons', '[]'), true);
        if (empty($coupons)) return 'No coupons configured.';

        $atts = shortcode_atts(array('id' => 0), $atts);
        $id = intval($atts['id']) - 1;

        if (!isset($coupons[$id])) return 'Invalid coupon ID.';

        $coupon = $coupons[$id];
        $link = $coupon['afflink'];

        // Premium: Track clicks (requires pro key)
        $pro_key = get_option('cacp_pro_key', '');
        if ($pro_key) {
            $link .= (strpos($link, '?') ? '&' : '?') . 'ref=cacp';
        }

        return '<div class="cacp-coupon"><h3>Exclusive Deal: <strong>' . esc_html($coupon['code']) . '</strong></h3><p>' . esc_html($coupon['desc']) . '</p><a href="' . esc_url($link) . '" class="button button-large cacp-btn" target="_blank">Get Deal Now</a></div>';
    }

    public function activate() {
        if (!get_option('cacp_coupons')) {
            update_option('cacp_coupons', json_encode(array(
                array('code' => 'SAVE20', 'afflink' => 'https://example.com/aff?ref=blog', 'desc' => '20% off on premium tools')
            )));
        }
    }
}

new CustomAffiliateCouponsPro();

// Inline CSS for coupon styling
function cacp_styles() {
    echo '<style>.cacp-coupon {border: 2px solid #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center;}.cacp-btn {background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 18px;}</style>';
}
add_action('wp_head', 'cacp_styles');
add_action('wp_footer', 'cacp_styles');

// Freemium upsell notice
function cacp_upsell_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Custom Affiliate Coupons Pro:</strong> Unlock unlimited coupons, click tracking & analytics with premium upgrade! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'cacp_upsell_notice');
