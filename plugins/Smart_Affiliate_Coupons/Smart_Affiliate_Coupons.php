<?php
/*
Plugin Name: Smart Affiliate Coupons
Description: Manage dynamic coupon codes and affiliate deals with enhanced features for WooCommerce and affiliate marketers.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons.php
License: GPLv2 or later
*/
if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCoupons {
    private $options_option_name = 'sac_options';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('sac_coupons', array($this, 'render_coupon_list'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_styles'));
    }

    public function add_admin_menu() {
        add_menu_page('Smart Affiliate Coupons', 'Smart Coupons', 'manage_options', 'smart-affiliate-coupons', array($this, 'options_page'), 'dashicons-tickets', 60);
    }

    public function settings_init() {
        register_setting('sac_settings', $this->options_option_name);

        add_settings_section(
            'sac_section_main',
            __('Manage Coupons & Affiliate Deals', 'sac'),
            null,
            'sac_settings'
        );

        add_settings_field(
            'sac_coupons_field',
            __('Coupons & Deals JSON Data', 'sac'),
            array($this, 'coupons_field_render'),
            'sac_settings',
            'sac_section_main'
        );
    }

    public function coupons_field_render() {
        $options = get_option($this->options_option_name);
        $json_data = isset($options['coupons_json']) ? esc_textarea($options['coupons_json']) : '';
        echo '<textarea cols="60" rows="15" name="' . $this->options_option_name . '[coupons_json]" placeholder="[{\"code\":\"SAVE10\",\"desc\":\"10% off all products\",\"url\":\"https://example.com/product?ref=affiliate\"}]">' . $json_data . '</textarea>';
        echo '<p class="description">Enter coupons and affiliate offers as JSON array of objects with code, desc, url fields.</p>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupons</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('sac_settings');
                do_settings_sections('sac_settings');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Place the shortcode <code>[sac_coupons]</code> on any post or page to display the active coupons and affiliate deals.</p>
            <p>Example JSON input:</p>
            <pre>[
  {"code": "SAVE10", "desc": "10% off on selected items", "url": "https://example.com/shop?ref=affiliate"},
  {"code": "FREESHIP", "desc": "Free Shipping on orders over $50", "url": "https://example.com/cart?ref=affiliate"}
]</pre>
        </div>
        <?php
    }

    public function render_coupon_list() {
        $options = get_option($this->options_option_name);
        $coupons = array();
        if (!empty($options['coupons_json'])) {
            $coupons = json_decode($options['coupons_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return '<p>Invalid coupon data. Please check plugin settings.</p>';
            }
        }
        if (!$coupons) {
            return '<p>No coupons available at the moment. Please check back later.</p>';
        }

        $output = '<div class="sac-coupons-list" style="margin: 20px 0;padding: 10px;border: 1px solid #ddd;background: #f9f9f9;">';
        foreach ($coupons as $coupon) {
            $code = esc_html($coupon['code'] ?? '');
            $desc = esc_html($coupon['desc'] ?? '');
            $url = esc_url($coupon['url'] ?? '#');
            $output .= "<div class='sac-coupon' style='margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 10px;'>";
            $output .= "<strong style='font-size:1.2em;color:#2a7ae2;'>Code: $code</strong><br>";
            $output .= "<span style='display:block;margin:5px 0;'>$desc</span>";
            $output .= "<a href='$url' target='_blank' rel='nofollow noopener' style='background:#2a7ae2;color:#fff;padding:8px 12px;text-decoration:none;border-radius:4px;'>Use Coupon</a>";
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function enqueue_scripts_styles() {
        if (!is_admin()) {
            wp_enqueue_style('sac-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
        }
    }
}
new SmartAffiliateCoupons();
