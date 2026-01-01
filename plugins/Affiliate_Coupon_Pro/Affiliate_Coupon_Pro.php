/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Pro
 * Plugin URI: https://example.com/affiliate-coupon-pro
 * Description: Automatically generates and displays personalized affiliate coupons, promo codes, and discount deals to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acp_save_coupon', array($this, 'save_coupon'));
        add_action('wp_ajax_nopriv_acp_save_coupon', array($this, 'save_coupon'));
    }

    public function init() {
        if (get_option('acp_pro_version')) {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acp-frontend', plugin_dir_url(__FILE__) . 'acp-frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acp-frontend', plugin_dir_url(__FILE__) . 'acp-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Pro', 'Coupon Pro', 'manage_options', 'affiliate-coupon-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acp_coupon_data'])) {
            update_option('acp_coupons', sanitize_text_field(wp_unslash($_POST['acp_coupon_data'])));
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        $coupons = get_option('acp_coupons', '{"coupons":[] }');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Pro Settings</h1>
            <form method="post">
                <textarea name="acp_coupon_data" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">JSON format: {"coupons":[{"code":"SAVE20","afflink":"https://aff.link","desc":"20% off"}]}</p>
                <p><input type="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: [affiliate_coupon id="1"]</p>
            <?php if (!get_option('acp_pro_version')) { ?>
            <p><strong>Upgrade to Pro</strong> for unlimited coupons and analytics! <a href="#">Buy Now ($49)</a></p>
            <?php } ?>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('acp_coupons', '{"coupons":[] }'), true);
        if (!isset($coupons['coupons'][$atts['id']])) {
            return '';
        }
        $coupon = $coupons['coupons'][$atts['id']];
        ob_start();
        ?>
        <div class="acp-coupon-box">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <p><strong>Code: <?php echo esc_html($coupon['code']); ?></strong></p>
            <a href="<?php echo esc_url($coupon['afflink']); ?>" target="_blank" class="acp-button">Get Deal &nbsp; →</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function save_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'acp_nonce')) {
            wp_die('Security check failed');
        }
        update_option('acp_coupons', sanitize_text_field($_POST['data']));
        wp_send_json_success('Saved');
    }

    public function pro_notice() {
        echo '<div class="notice notice-success"><p>Affiliate Coupon Pro is activated! Enjoy premium features.</p></div>';
    }
}

new AffiliateCouponPro();

// Pro activation hook
register_activation_hook(__FILE__, function() {
    if (isset($_POST['pro_license']) && $_POST['pro_license'] === 'prokey') {
        update_option('acp_pro_version', '1.0');
    }
});

// Frontend JS (embedded)
function acp_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acp-coupon-box .acp-button').on('click', function() {
            $(this).text('Copied! Check your email for exclusive deals');
        });
    });
    </script>
    <style>
    .acp-coupon-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 20px 0; }
    .acp-coupon-box h3 { color: #856404; margin: 0 0 10px; }
    .acp-button { background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    .acp-button:hover { background: #218838; }
    </style>
    <?php
}
add_action('wp_footer', 'acp_inline_js');