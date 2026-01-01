/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks, and displays personalized deals to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea><br>
                <p class="description">JSON format: [{ "code": "SAVE10", "afflink": "https://affiliate.com/?ref=123", "desc": "10% off at Store" }]</p>
                <p><input type="submit" name="acv_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-generation. <a href="#pro">Get Pro ($49)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) return '';
        $coupon = $coupons[$atts['id']];
        ob_start();
        ?>
        <div class="acv-coupon" data-coupon-id="<?php echo $atts['id']; ?>">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <p><strong>Code:</strong> <span class="acv-code"><?php echo esc_html($coupon['code']); ?></span></p>
            <a href="#" class="acv-button button">Get Deal & Track</a>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }
        .acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; }
        .acv-button:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $coupon_id = intval($_POST['coupon_id']);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (isset($coupons[$coupon_id])) {
            update_option('acv_clicks_' . $coupon_id, (get_option('acv_clicks_' . $coupon_id, 0) + 1));
            wp_redirect($coupons[$coupon_id]['afflink']);
            exit;
        }
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', json_encode(array(
                array('code' => 'WELCOME10', 'afflink' => 'https://example.com/aff', 'desc' => '10% Off First Purchase')
            )));
        }
    }
}

AffiliateCouponVault::get_instance();

// Frontend JS (embedded)
function acv_add_inline_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-button').click(function(e) {
            e.preventDefault();
            var couponId = $(this).closest('.acv-coupon').data('coupon-id');
            $.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                nonce: acv_ajax.nonce,
                coupon_id: couponId
            }, function() {
                window.location = $(this).closest('.acv-coupon').find('a').attr('href');
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'acv_add_inline_script');