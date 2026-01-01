/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons with personalized promo codes to boost conversions and revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_promo', array($this, 'ajax_generate_promo'));
        add_action('wp_ajax_nopriv_generate_promo', array($this, 'ajax_generate_promo'));
    }

    public function init() {
        if (get_option('ecp_license') !== 'activated') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ecp-script', 'ecp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_coupons', sanitize_text_field($_POST['coupons']));
            update_option('ecp_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', 'SAVE20|20% off first purchase|YourAffiliateLink');
        $affiliate_id = get_option('ecp_affiliate_id', '');
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (format: CODE|Description|Affiliate URL):</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Your Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" /></td>
                    </tr>
                </table>
                <p><input type="submit" name="ecp_save" class="button-primary" value="Save Settings" /></p>
                <?php if (get_option('ecp_license') !== 'activated') { ?>
                <p><strong>Upgrade to Pro for unlimited coupons and analytics!</strong> <a href="https://example.com/pro">Get Pro</a></p>
                <?php } ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => '1'), $atts);
        $coupons = explode('\n', get_option('ecp_coupons', 'SAVE20|20% off first purchase|https://example.com/ref'));
        if (!isset($coupons[$atts['id']-1])) return 'Coupon not found.';
        list($code, $desc, $url) = explode('|', $coupons[$atts['id']-1]);
        $affiliate_id = get_option('ecp_affiliate_id', '');
        $unique_code = $code . '-' . $affiliate_id . '-' . uniqid();
        ob_start();
        ?>
        <div class="ecp-coupon" style="border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9;">
            <h3>Exclusive Deal! <strong><?php echo esc_html($desc); ?></strong></h3>
            <p><strong>Your Code: <span id="ecp-code"><?php echo esc_html($unique_code); ?></span></strong></p>
            <button id="copy-code" class="button">Copy Code</button>
            <a href="<?php echo esc_url($url); ?>" target="_blank" class="button button-primary" style="margin-left: 10px;">Shop Now & Save</a>
        </div>
        <script>
        jQuery('#copy-code').click(function() {
            navigator.clipboard.writeText(jQuery('#ecp-code').text());
            jQuery(this).text('Copied!');
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_promo() {
        $code = wp_generate_password(8, false);
        wp_send_json_success(array('code' => $code));
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Unlock <strong>Exclusive Coupons Pro</strong> features: Unlimited coupons, analytics, auto-expiration. <a href="https://example.com/pro">Upgrade now</a> for $49/year!</p></div>';
    }
}

new ExclusiveCouponsPro();

// Pro teaser: Limit to 3 coupons in free version
function ecp_limit_coupons($coupons) {
    if (get_option('ecp_license') !== 'activated') {
        $lines = explode('\n', $coupons);
        return implode('\n', array_slice($lines, 0, 3));
    }
    return $coupons;
}
add_filter('pre_update_option_ecp_coupons', 'ecp_limit_coupons');

// JS file content (inline for single file)
function ecp_inline_js() {
    if (!wp_script_is('ecp-script', 'enqueued')) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#generate-promo').click(function() {
            $.post(ecp_ajax.ajaxurl, {action: 'generate_promo'}, function(res) {
                if (res.success) $('#promo-code').val(res.data.code);
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'ecp_inline_js');