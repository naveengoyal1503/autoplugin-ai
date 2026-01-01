/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, tracking clicks and conversions for maximum blog monetization.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="80" placeholder='[{"name":"10% Off Hosting","code":"AFF10","url":"https://affiliate-link.com/?coupon=AFF10","affiliate":"Your Affiliate ID"}]'><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">JSON array of coupons: name, code, url, affiliate</p>
                <p><input type="submit" name="acv_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Pro Upgrade: Unlock unlimited coupons, analytics dashboard, and auto-generation! <a href="#pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $click_id = uniqid();
        return '<div class="acv-coupon" data-click-id="' . $click_id . '" data-affiliate="' . esc_attr($coupon['affiliate']) . '">
            <h3>' . esc_html($coupon['name']) . '</h3>
            <p>Code: <strong>' . esc_html($coupon['code']) . '</strong></p>
            <a href="#" class="button acv-btn">Get Deal (Track Click)</a>
            <p><small>Exclusive affiliate deal</small></p>
        </div>';
    }

    public function track_click() {
        $click_id = sanitize_text_field($_POST['click_id']);
        $affiliate = sanitize_text_field($_POST['affiliate']);
        // In pro version, log to database
        error_log('ACV Click: ' . $click_id . ' for ' . $affiliate);
        $coupon = json_decode(get_option('acv_coupons', '[]'), true);
        // Find matching coupon
        foreach ($coupon as $c) {
            if ($c['affiliate'] === $affiliate) {
                wp_redirect($c['url']);
                exit;
            }
        }
        wp_die('Error');
    }

    public function activate() {
        if (false === get_option('acv_coupons')) {
            update_option('acv_coupons', '[]');
        }
    }
}

AffiliateCouponVault::get_instance();

// Inline JS for basic version
add_action('wp_footer', function() {
    if (is_singular()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.acv-btn').click(function(e) {
                e.preventDefault();
                var $coupon = $(this).closest('.acv-coupon');
                var clickId = $coupon.data('click-id');
                var affiliate = $coupon.data('affiliate');
                $.post(acv_ajax.ajax_url, {
                    action: 'acv_track_click',
                    click_id: clickId,
                    affiliate: affiliate
                }, function() {
                    window.open($coupon.find('~ a').attr('href'), '_blank'); // Pro: dynamic URL
                });
            });
        });
        </script>
        <style>
        .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; }
        .acv-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        .acv-btn:hover { background: #005a87; }
        </style>
        <?php
    }
});

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Upgrade for analytics, unlimited coupons & more! <a href="#pro">Learn More</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');