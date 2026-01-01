/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes and deals to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_shortcode('acv_coupons', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        add_settings_section('acv_main', 'Coupon Settings', null, 'acv');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'acv', 'acv_main');
    }

    public function coupons_field() {
        $coupons = get_option('acv_coupons', array());
        echo '<textarea name="acv_coupons" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"name":"Coupon Name","code":"SAVE10","affiliate_link":"https://affiliate.link","description":"10% off"}</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, custom branding for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = get_option('acv_coupons', array());
        if (empty($coupons)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Configure now</a>.</p>';
        }
        shuffle($coupons);
        $coupons = array_slice($coupons, 0, intval($atts['limit']));
        $output = '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="acv-coupon">';
            $output .= '<h4>' . esc_html($coupon['name']) . '</h4>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            $output .= '<code>' . esc_html($coupon['code']) . '</code>';
            $output .= '<a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank" class="button">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        $output .= '<button id="acv-generate" class="button">Generate New Coupon</button>';
        return $output;
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $default = array(
            'name' => 'Special Deal',
            'code' => 'AFF' . wp_generate_uuid4(),
            'affiliate_link' => 'https://your-affiliate-link.com',
            'description' => 'Exclusive discount for readers'
        );
        wp_send_json_success($default);
    }
}

AffiliateCouponVault::get_instance();

// CSS
add_action('wp_head', function() { ?>
<style>
.acv-coupons { display: grid; gap: 1rem; margin: 1rem 0; }
.acv-coupon { border: 1px solid #ddd; padding: 1rem; border-radius: 5px; }
.acv-coupon code { background: #f1f1f1; padding: 0.2rem 0.5rem; }
#pro-upsell { background: #fff3cd; padding: 1rem; border-left: 4px solid #ffc107; margin: 1rem 0; }
</style>
<?php });

// JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('#acv-generate').click(function() {
        $.post(acv_ajax.ajax_url, {
            action: 'acv_generate_coupon',
            nonce: acv_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert('New coupon generated: ' + response.data.code);
            }
        });
    });
});
</script>
<?php });