/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Manager
 * Plugin URI: https://example.com/smart-affiliate-coupon-manager
 * Description: Automatically generates and manages personalized affiliate coupons with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupon-manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCouponManager {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('sacm_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sacm-frontend', plugin_dir_url(__FILE__) . 'sacm.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sacm-frontend', plugin_dir_url(__FILE__) . 'sacm.css', array(), '1.0.0');
    }

    public function admin_enqueue($hook) {
        if ($hook === 'toplevel_page_sacm-settings') {
            wp_enqueue_script('sacm-admin', plugin_dir_url(__FILE__) . 'sacm-admin.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_menu() {
        add_menu_page('Smart Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'sacm-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sacm_save'])) {
            update_option('sacm_coupons', sanitize_textarea_field($_POST['sacm_coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('sacm_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON)</th>
                        <td><textarea name="sacm_coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="sacm_save" class="button-primary" value="Save Changes"></p>
            </form>
            <p>Use JSON format: <code>[{"name":"10% Off","code":"SAVE10","afflink":"https://aff.link","desc":"Affiliate discount"}]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-expiry. <a href="#pro">Get Pro ($49/yr)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('sacm_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) return 'Coupon not found.';
        $coupon = $coupons[$atts['id']];
        $click_id = uniqid();
        return '<div class="sacm-coupon"><h3>' . esc_html($coupon['name']) . '</h3><p>' . esc_html($coupon['desc']) . '</p><input type="text" readonly value="' . esc_attr($coupon['code']) . '" onclick="this.select()"><a href="' . esc_url($coupon['afflink']) . '?cid=' . $click_id . '" class="button sacm-btn" target="_blank">Get Deal (Affiliate)</a><small>Tracked click ID: ' . $click_id . '</small></div>';
    }

    public function activate() {
        if (!get_option('sacm_coupons')) {
            update_option('sacm_coupons', json_encode(array(
                array('name' => 'Free Trial', 'code' => 'TRIAL2026', 'afflink' => 'https://example-aff.com/trial?ref=wp', 'desc' => 'Start your free trial via affiliate link')
            )));
        }
    }
}

new SmartAffiliateCouponManager();

// Frontend CSS
add_action('wp_head', function() { ?><style>.sacm-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }.sacm-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }</style><?php });

// JS
add_action('wp_footer', function() { ?><script>jQuery(document).ready(function($) { $('.sacm-btn').click(function() { gtag('event', 'coupon_click', {'coupon': $(this).data('coupon')}); }); });</script><?php });

// Pro upsell notice (free limit: 3 coupons)
add_action('admin_notices', function() { if (is_admin() && current_user_can('manage_options') && count(json_decode(get_option('sacm_coupons', '[]'), true)) > 3) { echo '<div class="notice notice-warning"><p>Upgrade to <strong>Pro</strong> for unlimited coupons! <a href="#pro">Learn more</a></p></div>'; } });
