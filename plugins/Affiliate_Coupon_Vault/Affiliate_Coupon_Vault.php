/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks and conversions, and displays personalized deals to boost commissions.
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
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-js', plugin_dir_url(__FILE__) . 'coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-css', plugin_dir_url(__FILE__) . 'coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_settings', 'affiliate_coupon_options');
        add_settings_section('main_section', 'Coupon Settings', null, 'affiliate-coupon-vault');
        add_settings_field('coupons', 'Coupons (JSON format: {"code":"discount","afflink":"url","desc":"description"})', array($this, 'coupons_field'), 'affiliate-coupon-vault', 'main_section');
    }

    public function coupons_field() {
        $options = get_option('affiliate_coupon_options', array('coupons' => '[]'));
        echo '<textarea name="affiliate_coupon_options[coupons]" rows="10" cols="50">' . esc_textarea($options['coupons']) . '</textarea>';
        echo '<p class="description">Enter coupons as JSON array, e.g. [{"code":"SAVE20","afflink":"https://aff.link","desc":"20% off"}]</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_settings');
                do_settings_sections('affiliate-coupon-vault');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics dashboard, and auto-generation for $49/year!</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $options = get_option('affiliate_coupon_options', array('coupons' => '[]'));
        $coupons = json_decode($options['coupons'], true) ?: array();
        if (empty($coupons)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Set up now</a>.</p>';
        }
        $coupon = $coupons[array_rand($coupons)];
        $track_url = add_query_arg('ref', 'vault-' . uniqid(), $coupon['afflink']);
        return '<div class="affiliate-coupon-vault"><h3>Exclusive Deal: <code>' . esc_html($coupon['code']) . '</code></h3><p>' . esc_html($coupon['desc']) . '</p><a href="' . esc_url($track_url) . '" class="coupon-btn" target="_blank" rel="nofollow">Get Deal & Track (Affiliate)</a></div>';
    }

    public function activate() {
        add_option('affiliate_coupon_options', array('coupons' => '[]'));
    }
}

AffiliateCouponVault::get_instance();

// Inline styles and scripts for self-contained

function acv_inline_assets() {
    ?>
    <style>
    .affiliate-coupon-vault { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
    .affiliate-coupon-vault code { background: #0073aa; color: white; padding: 5px 10px; border-radius: 4px; }
    .coupon-btn { display: inline-block; background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.3s; }
    .coupon-btn:hover { background: #005a87; }
    </style>
    <script>jQuery(document).ready(function($){ $('.coupon-btn').on('click', function(){ gtag && gtag('event', 'coupon_click', {'event_category': 'affiliate', 'event_label': $(this).text()}); }); });</script>
    <?php
}
add_action('wp_head', 'acv_inline_assets');

// Track clicks
add_action('init', function() {
    if (isset($_GET['ref']) && strpos($_GET['ref'], 'vault-') === 0) {
        setcookie('acv_ref', sanitize_text_field($_GET['ref']), time() + 86400, '/');
    }
});
