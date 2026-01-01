/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Manager
 * Plugin URI: https://example.com/smart-affiliate-coupon-manager
 * Description: Generate trackable affiliate coupon codes, display dynamic coupon sections, and boost affiliate conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCouponManager {
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
        add_shortcode('sacm_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('sacm-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('sacm-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'sacm-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('sacm_settings', 'sacm_coupons');
        add_settings_section('sacm_main', 'Coupon Settings', null, 'sacm');
        add_settings_field('coupons_list', 'Coupons', array($this, 'coupons_field'), 'sacm', 'sacm_main');
    }

    public function coupons_field() {
        $coupons = get_option('sacm_coupons', array());
        echo '<textarea name="sacm_coupons" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"name":"Discount 20%","code":"SAVE20","affiliate_link":"https://affiliate-link.com/?coupon=SAVE20","expires":"2026-12-31"}</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Manager</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sacm_settings');
                do_settings_sections('sacm');
                submit_button();
                ?>
            </form>
            <p>Pro upgrade available for advanced features like analytics and auto-generation.</p>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = get_option('sacm_coupons', array());
        $output = '<div class="sacm-coupons">';
        $count = 0;
        foreach ($coupons as $coupon) {
            if ($count >= $atts['limit']) break;
            $output .= '<div class="sacm-coupon">';
            $output .= '<h3>' . esc_html($coupon['name']) . '</h3>';
            $output .= '<p>Code: <strong>' . esc_html($coupon['code']) . '</strong></p>';
            $output .= '<a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank" class="sacm-btn">Shop Now & Save</a>';
            if (isset($coupon['expires']) && $coupon['expires'] < date('Y-m-d')) {
                $output .= '<span class="expired">Expired</span>';
            }
            $output .= '</div>';
            $count++;
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        if (!get_option('sacm_coupons')) {
            update_option('sacm_coupons', array(
                array(
                    'name' => 'Sample 20% Off',
                    'code' => 'WELCOME20',
                    'affiliate_link' => 'https://example-affiliate.com/?ref=yourid&coupon=WELCOME20',
                    'expires' => '2026-12-31'
                )
            ));
        }
    }
}

SmartAffiliateCouponManager::get_instance();

// Inline styles and scripts for self-contained plugin

function sacm_inline_styles() {
    echo '<style>
.sacm-coupons { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
.sacm-coupon { background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.sacm-btn { display: inline-block; background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.sacm-btn:hover { background: #005a87; }
.expired { color: red; font-weight: bold; }
@media (max-width: 768px) { .sacm-coupons { grid-template-columns: 1fr; } }
    </style>';
}
add_action('wp_head', 'sacm_inline_styles');

function sacm_inline_scripts() {
    echo '<script>jQuery(document).ready(function($) { $(".sacm-btn").on("click", function() { gtag("event", "coupon_click", {"coupon": $(this).prev().text()}); }); });</script>';
}
add_action('wp_footer', 'sacm_inline_scripts');

// Pro upsell notice
function sacm_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>Smart Affiliate Coupon Manager Pro</strong>: Unlimited coupons, analytics, auto-expiration & integrations. <a href="https://example.com/pro" target="_blank">Upgrade now for $49/year</a></p></div>';
}
add_action('admin_notices', 'sacm_admin_notice');