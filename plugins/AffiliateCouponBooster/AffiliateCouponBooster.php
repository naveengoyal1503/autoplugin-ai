<?php
/*
Plugin Name: AffiliateCouponBooster
Plugin URI: https://example.com/affiliatecouponbooster
Description: Aggregate coupons and manage affiliate links dynamically to boost conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateCouponBooster.php
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponBooster {
    public function __construct() {
        add_shortcode('acb_coupons', array($this, 'render_coupon_list'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function enqueue_assets() {
        wp_enqueue_style('acb-style', plugin_dir_url(__FILE__) . 'acb-style.css');
        wp_enqueue_script('acb-script', plugin_dir_url(__FILE__) . 'acb-script.js', array('jquery'), false, true);
    }

    public function add_admin_menu() {
        add_menu_page('AffiliateCouponBooster Settings', 'AffiliateCouponBooster', 'manage_options', 'acb-settings', array($this, 'settings_page'), 'dashicons-tickets', 81);
    }

    public function register_settings() {
        register_setting('acb_options_group', 'acb_coupons_data', array($this, 'sanitize_coupons'));
        add_settings_section('acb_main_section', 'Coupon Data Management', null, 'acb-settings');
        add_settings_field('acb_coupons_field', 'Coupons JSON Data', array($this, 'coupons_field_html'), 'acb-settings', 'acb_main_section');
    }

    public function sanitize_coupons($input) {
        $decoded = json_decode($input, true);
        if ($decoded === null || !is_array($decoded)) {
            add_settings_error('acb_coupons_data', 'acb_error', 'Invalid JSON format for coupons data');
            return get_option('acb_coupons_data');
        }
        return $input;
    }

    public function coupons_field_html() {
        $coupons = esc_textarea(get_option('acb_coupons_data', '[]'));
        echo '<textarea name="acb_coupons_data" rows="10" cols="50" style="width:100%;font-family: monospace;">' . $coupons . '</textarea>';
        echo '<p class="description">Enter your coupons and affiliate links as JSON array with keys: title, description, code, affiliate_url.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AffiliateCouponBooster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acb_options_group');
                do_settings_sections('acb-settings');
                submit_button();
                ?>
            </form>
            <p>Example JSON format:</p>
            <pre>[
  {"title": "10% off Widget A", "description": "Save 10% on Widget A", "code": "WIDGET10", "affiliate_url": "https://affiliate.example.com/product/widget-a"},
  {"title": "Free Shipping Offer", "description": "Get free shipping on orders over $50", "code": "FREESHIP", "affiliate_url": "https://affiliate.example.com/"}
]</pre>
        </div>
        <?php
    }

    public function render_coupon_list() {
        $coupons_json = get_option('acb_coupons_data', '[]');
        $coupons = json_decode($coupons_json, true);
        if (!$coupons || !is_array($coupons)) {
            return '<p>No coupons available.</p>';
        }

        ob_start();
        echo '<div class="acb-coupon-list">';
        foreach ($coupons as $coupon) {
            $title = esc_html($coupon['title'] ?? '');
            $desc = esc_html($coupon['description'] ?? '');
            $code = esc_html($coupon['code'] ?? '');
            $url = esc_url($coupon['affiliate_url'] ?? '');

            echo '<div class="acb-coupon-item">';
            echo '<h3 class="acb-coupon-title">' . $title . '</h3>';
            if ($desc) {
                echo '<p class="acb-coupon-desc">' . $desc . '</p>';
            }
            if ($code) {
                echo '<p class="acb-coupon-code">Coupon Code: <strong>' . $code . '</strong></p>';
            }
            if ($url) {
                echo '<p><a href="' . $url . '" target="_blank" rel="nofollow noopener" class="acb-coupon-link">Get Deal</a></p>';
            }
            echo '</div>';
        }
        echo '</div>';

        return ob_get_clean();
    }
}

new AffiliateCouponBooster();

// Inline CSS for basic styling
add_action('wp_head', function() {
    echo '<style>
    .acb-coupon-list { display: flex; flex-wrap: wrap; gap: 20px; margin: 20px 0; }
    .acb-coupon-item { border: 1px solid #ddd; padding: 15px; width: 100%; max-width: 300px; background: #f9f9f9; border-radius: 5px; }
    .acb-coupon-title { margin: 0 0 10px; font-size: 1.2em; color: #0073aa; }
    .acb-coupon-desc { margin: 0 0 10px; color: #333; }
    .acb-coupon-code { background: #222; color: #fff; padding: 5px 10px; display: inline-block; border-radius: 3px; letter-spacing: 1px; margin-bottom: 10px; }
    .acb-coupon-link { background: #0073aa; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; }
    .acb-coupon-link:hover { background: #005177; }
    </style>';
});
