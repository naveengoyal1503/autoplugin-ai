/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Booster
 * Description: Display dynamic affiliate coupons with tracking to boost conversions.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponBooster {
    public function __construct() {
        add_shortcode('affiliate_coupons', array($this, 'render_coupons'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acb-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function add_admin_menu() {
        add_options_page('Affiliate Coupon Booster', 'Affiliate Coupon Booster', 'manage_options', 'affiliate_coupon_booster', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('acb_settings', 'acb_coupons');

        add_settings_section('acb_section', __('Coupons Settings', 'acb'), null, 'acb_settings');

        add_settings_field(
            'acb_coupons',
            __('Enter coupons as JSON array', 'acb'),
            array($this, 'coupons_field_render'),
            'acb_settings',
            'acb_section'
        );
    }

    public function coupons_field_render() {
        $options = get_option('acb_coupons', '[]');
        echo '<textarea cols="50" rows="10" name="acb_coupons">' . esc_textarea($options) . '</textarea>';
        echo '<p class="description">Format: [{"code":"SAVE20","affiliate_url":"https://affiliatesite.com/?ref=123","desc":"Save 20% on all items"}, ...]</p>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Coupon Booster</h2>
            <?php
            settings_fields('acb_settings');
            do_settings_sections('acb_settings');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function render_coupons() {
        $coupons_json = get_option('acb_coupons', '[]');
        $coupons = json_decode($coupons_json, true);
        if (empty($coupons) || !is_array($coupons)) return '<p>No coupons available.</p>';

        $html = '<div class="acb-coupons">';
        foreach ($coupons as $coupon) {
            $code = esc_html($coupon['code'] ?? '');
            $url = esc_url($coupon['affiliate_url'] ?? '#');
            $desc = esc_html($coupon['desc'] ?? '');
            $html .= "<div class='acb-coupon'><div class='acb-desc'>{$desc}</div><div class='acb-code'>Code: <strong>{$code}</strong></div><a href='{$url}' class='acb-apply' target='_blank' rel='nofollow noopener'>Use Coupon</a></div>";
        }
        $html .= '</div>';
        return $html;
    }
}

new AffiliateCouponBooster();
