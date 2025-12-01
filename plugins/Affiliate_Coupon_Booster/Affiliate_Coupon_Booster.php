/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Booster
 * Description: Automatically aggregates affiliate coupons and deals, displays them with affiliate links to boost conversions.
 * Version: 1.0
 * Author: Generated
 * License: GPL2
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Affiliate_Coupon_Booster {
    private $option_name = 'affiliate_coupon_booster_options';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_coupons', array($this, 'render_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function add_admin_menu() {
        add_options_page('Affiliate Coupon Booster', 'Affiliate Coupon Booster', 'manage_options', 'affiliate_coupon_booster', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('affiliateCouponBooster', $this->option_name);

        add_settings_section('affiliateCouponBooster_section', __('Settings', 'affiliate-coupon-booster'), null, 'affiliateCouponBooster');

        add_settings_field(
            'acco_coupons',
            __('Coupons Data (JSON)', 'affiliate-coupon-booster'),
            array($this, 'coupons_field_render'),
            'affiliateCouponBooster',
            'affiliateCouponBooster_section'
        );
    }

    public function coupons_field_render() {
        $options = get_option($this->option_name);
        $data = isset($options['coupons']) ? $options['coupons'] : '';
        echo '<textarea cols="60" rows="10" name="'.esc_attr($this->option_name).'[coupons]" placeholder="[{\"title\":\"...\", \"url\":\"...\", \"code\":\"...\", \"expiry\":\"YYYY-MM-DD\"}]">'.esc_textarea($data).'</textarea>';
        echo '<p class="description">' . __('Enter a JSON array of coupons with fields: title, url (affiliate link), code, expiry (optional, YYYY-MM-DD).', 'affiliate-coupon-booster') . '</p>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Coupon Booster</h2>
            <?php
            settings_fields('affiliateCouponBooster');
            do_settings_sections('affiliateCouponBooster');
            submit_button();
            ?>
        </form>
        <?php
    }

    // Enqueue minimal CSS
    public function enqueue_assets() {
        wp_enqueue_style('affiliate-coupon-booster-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function render_coupons_shortcode() {
        $options = get_option($this->option_name);
        $coupons_data = isset($options['coupons']) ? $options['coupons'] : '';
        if (!$coupons_data) return '<p>No coupons available.</p>';

        $coupons = json_decode($coupons_data, true);
        if (!is_array($coupons)) return '<p>Invalid coupons data format.</p>';

        $today = new DateTime();
        $output = '<div class="affiliate-coupon-list">';
        foreach ($coupons as $coupon) {
            if (!isset($coupon['title']) || !isset($coupon['url']) || !isset($coupon['code'])) continue;

            // Check expiry if set
            if (isset($coupon['expiry']) && $coupon['expiry']) {
                $expiry_date = DateTime::createFromFormat('Y-m-d', $coupon['expiry']);
                if ($expiry_date && $expiry_date < $today) continue;
            }

            $title = esc_html($coupon['title']);
            $url = esc_url($coupon['url']);
            $code = esc_html($coupon['code']);

            $output .= '<div class="affiliate-coupon-item">';
            $output .= '<h3><a href="'.$url.'" target="_blank" rel="nofollow noopener">'.$title.'</a></h3>';
            $output .= '<p><strong>Coupon Code: </strong><code>'.$code.'</code></p>';
            $output .= '<p><a href="'.$url.'" class="coupon-btn" target="_blank" rel="nofollow noopener">Use Coupon</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }
}

new Affiliate_Coupon_Booster();