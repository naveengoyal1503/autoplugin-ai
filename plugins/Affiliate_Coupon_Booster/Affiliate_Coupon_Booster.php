/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Booster
 * Description: Automatically generates and displays dynamic affiliate coupon codes and deals to boost conversions on your WordPress site.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponBooster {
    private $option_name = 'acb_coupons';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_coupon_boost', array($this, 'render_coupon'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function admin_menu() {
        add_menu_page('Affiliate Coupon Booster', 'Affiliate Coupons', 'manage_options', 'acb_settings', array($this, 'settings_page'), 'dashicons-tickets', 80);
    }

    public function settings_init() {
        register_setting('acb_settings_group', $this->option_name);

        add_settings_section('acb_section', 'Manage Coupons', null, 'acb_settings');

        add_settings_field(
            'acb_coupons_field',
            'Coupons JSON',
            array($this, 'coupons_field_render'),
            'acb_settings',
            'acb_section'
        );
    }

    public function coupons_field_render() {
        $options = get_option($this->option_name, '[]');
        echo '<textarea cols="80" rows="10" name="' . esc_attr($this->option_name) . '">' . esc_textarea($options) . '</textarea>';
        echo '<p class="description">Enter coupon data as JSON array. Example:<br>[{"code":"SAVE10","description":"Save 10% at Store","affiliate_url":"https://affiliatelink.com/product?affid=123"}]</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acb_settings_group');
                do_settings_sections('acb_settings');
                submit_button();
                ?>
            </form>
            <h2>How to Use</h2>
            <p>Add your coupons in JSON format above. Then insert the shortcode <code>[affiliate_coupon_boost]</code> where you want to display the coupon offer.</p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acb-style', plugin_dir_url(__FILE__) . 'acb-style.css');
    }

    public function render_coupon($atts) {
        $coupons_json = get_option($this->option_name, '[]');
        $coupons = json_decode($coupons_json, true);
        if (!$coupons || !is_array($coupons) || count($coupons) === 0) {
            return '<p>No coupons available.</p>';
        }

        $coupon = $coupons[array_rand($coupons)];
        $code = esc_html($coupon['code']);
        $desc = esc_html($coupon['description']);
        $url = esc_url($coupon['affiliate_url']);

        $output = '<div class="acb-coupon">';
        $output .= '<p class="acb-desc">' . $desc . '</p>';
        $output .= '<a class="acb-button" href="' . $url . '" target="_blank" rel="nofollow noopener">Use Coupon: <strong>' . $code . '</strong></a>';
        $output .= '</div>';

        return $output;
    }
}

new AffiliateCouponBooster();

// Minimal CSS embedded for self-containment, added inline for plugin simplicity
add_action('wp_head', function() {
    echo '<style>.acb-coupon{border:1px solid #ddd;padding:15px;border-radius:6px;background:#f9f9f9;max-width:320px;margin:10px auto;text-align:center;}.acb-desc{font-size:1.1em;margin-bottom:10px;color:#333;}.acb-button{text-decoration:none;background:#28a745;color:#fff;padding:10px 20px;border-radius:4px;display:inline-block;font-weight:bold;transition:background 0.3s ease;}.acb-button:hover{background:#218838;}</style>';
});