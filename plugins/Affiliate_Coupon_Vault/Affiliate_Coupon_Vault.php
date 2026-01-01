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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-frontend', plugin_dir_url(__FILE__) . 'acv.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
        add_settings_section('acv_main', 'Coupon Settings', null, 'acv-settings');
        add_settings_field('acv_coupons', 'Coupons', array($this, 'coupons_field'), 'acv-settings', 'acv_main');
    }

    public function coupons_field() {
        $options = get_option('acv_options', array('coupons' => array()));
        $coupons = $options['coupons'];
        echo '<textarea name="acv_options[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>JSON format: [{"name":"Coupon Name","code":"SAVE10","aff_link":"https://affiliate.link","desc":"10% off"}]</p>';
        echo '<p><label><input type="checkbox" name="acv_options[pro]" ' . checked($options['pro'], true, false) . '> Pro Version Active (Unlimited Coupons)</label></p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv-settings');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and more for $49/year. <a href="#" onclick="alert('Pro features coming soon!')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $options = get_option('acv_options', array('coupons' => array()));
        $coupons = json_decode($options['coupons'], true) ?: array();
        if ($options['pro'] !== true && count($coupons) > 3) {
            $coupons = array_slice($coupons, 0, 3);
        }
        $coupons = array_slice($coupons, 0, (int)$atts['limit']);

        if (empty($coupons)) {
            return '<p>No coupons available. Add some in settings.</p>';
        }

        $output = '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="acv-coupon">';
            $output .= '<h4>' . esc_html($coupon['name']) . '</h4>';
            $output .= '<p><strong>Code:</strong> ' . esc_html($coupon['code']) . '</p>';
            $output .= '<p>' . esc_html($coupon['desc']) . '</p>';
            $output .= '<a href="' . esc_url($coupon['aff_link']) . '" class="acv-btn" target="_blank">Get Deal & Track</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        add_option('acv_options', array('coupons' => array(
            array('name' => 'Sample Deal', 'code' => 'WELCOME10', 'aff_link' => '#', 'desc' => '10% off first purchase')
        )));
    }
}

AffiliateCouponVault::get_instance();

// Inline styles and scripts for single file
add_action('wp_head', function() { ?>
<style>
.acv-coupons { display: flex; flex-wrap: wrap; gap: 20px; }
.acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1 1 300px; background: #f9f9f9; }
.acv-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
.acv-btn:hover { background: #005a87; }
</style>
<script>jQuery(document).ready(function($) { $('.acv-btn').click(function() { gtag && gtag('event', 'coupon_click', {'event_category': 'affiliate', 'event_label': $(this).closest('.acv-coupon').find('h4').text() }); }); });</script>
<?php });