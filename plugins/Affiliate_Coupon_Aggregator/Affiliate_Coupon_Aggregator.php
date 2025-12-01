/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Aggregator.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Aggregator
 * Description: Display curated affiliate coupons and deals dynamically to increase conversions.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponAggregator {
    private $coupons_option = 'aca_coupons';
    private $affiliate_id_option = 'aca_affiliate_id';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_coupons', array($this, 'display_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_options_page('Affiliate Coupon Aggregator', 'Affiliate Coupons', 'manage_options', 'affiliate_coupon_aggregator', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('acaSettings', $this->coupons_option);
        register_setting('acaSettings', $this->affiliate_id_option);

        add_settings_section('aca_settings_section', 'Settings', null, 'affiliate_coupon_aggregator');

        add_settings_field(
            'aca_coupons',
            'Coupons JSON',
            array($this, 'coupons_render'),
            'affiliate_coupon_aggregator',
            'aca_settings_section'
        );

        add_settings_field(
            'aca_affiliate_id',
            'Affiliate ID',
            array($this, 'affiliate_id_render'),
            'affiliate_coupon_aggregator',
            'aca_settings_section'
        );
    }

    public function coupons_render() {
        $value = get_option($this->coupons_option, '[]');
        echo '<textarea cols="50" rows="10" name="' . esc_attr($this->coupons_option) . '">' . esc_textarea($value) . '</textarea><p>Enter coupons as JSON array. Example: [{"title":"10% Off Sitewide","code":"SAVE10","url":"https://example.com?ref=affiliate"}]</p>';
    }

    public function affiliate_id_render() {
        $value = get_option($this->affiliate_id_option, '');
        echo '<input type="text" name="' . esc_attr($this->affiliate_id_option) . '" value="' . esc_attr($value) . '" placeholder="your-affiliate-id" />';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Coupon Aggregator Settings</h2>
            <?php
            settings_fields('acaSettings');
            do_settings_sections('affiliate_coupon_aggregator');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('aca-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function display_coupons_shortcode() {
        $coupons_json = get_option($this->coupons_option, '[]');
        $affiliate_id = trim(get_option($this->affiliate_id_option, ''));

        $coupons = json_decode($coupons_json, true);
        if (!$coupons || !is_array($coupons)) return '<p>No valid coupons found.</p>';

        $output = '<div class="aca-coupon-list">';

        foreach ($coupons as $coupon) {
            $title = isset($coupon['title']) ? esc_html($coupon['title']) : 'Coupon';
            $code = isset($coupon['code']) ? esc_html($coupon['code']) : '';
            $url = isset($coupon['url']) ? esc_url($coupon['url']) : '#';

            // Append affiliate ID to URL if set
            if ($affiliate_id) {
                $url = add_query_arg('aff_id', $affiliate_id, $url);
            }

            $output .= '<div class="aca-coupon-item">';
            $output .= '<a href="' . $url . '" target="_blank" rel="nofollow noopener noreferrer">';
            $output .= '<h4 class="aca-coupon-title">' . $title . '</h4>';
            $output .= '</a>';
            if ($code) {
                $output .= '<p class="aca-coupon-code">Code: <strong>' . $code . '</strong> <button class="aca-copy-btn" data-code="' . $code . '">Copy</button></p>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';

        // Add copy to clipboard script
        $output .= '<script>document.addEventListener("DOMContentLoaded", function(){var buttons=document.querySelectorAll(".aca-copy-btn");buttons.forEach(function(btn){btn.addEventListener("click",function(){navigator.clipboard.writeText(this.getAttribute("data-code")).then(()=>{alert("Coupon code copied!");});});});});</script>';

        return $output;
    }
}

new AffiliateCouponAggregator();
