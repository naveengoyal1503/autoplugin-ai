<?php
/*
Plugin Name: WP Revenue Booster
Description: Automatically optimizes ad, affiliate, and coupon placements for maximum revenue on WordPress sites.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

if (!defined('ABSPATH')) exit;

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'inject_optimized_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function inject_optimized_content($content) {
        if (is_single()) {
            $settings = get_option('wp_revenue_booster_settings');
            $ad_code = isset($settings['ad_code']) ? $settings['ad_code'] : '';
            $affiliate_link = isset($settings['affiliate_link']) ? $settings['affiliate_link'] : '';
            $coupon_code = isset($settings['coupon_code']) ? $settings['coupon_code'] : '';

            $optimized = "<div class='wp-revenue-booster'>";
            if (!empty($ad_code)) {
                $optimized .= "<div class='ad'>$ad_code</div>";
            }
            if (!empty($affiliate_link)) {
                $optimized .= "<div class='affiliate'><a href='$affiliate_link' target='_blank'>Special Offer</a></div>";
            }
            if (!empty($coupon_code)) {
                $optimized .= "<div class='coupon'>Use code: $coupon_code</div>";
            }
            $optimized .= "</div>";

            $content .= $optimized;
        }
        return $content;
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('wpRevenueBooster', 'wp_revenue_booster_settings');

        add_settings_section(
            'wp_revenue_booster_section',
            'Revenue Booster Settings',
            null,
            'wpRevenueBooster'
        );

        add_settings_field(
            'ad_code',
            'Ad Code',
            array($this, 'ad_code_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'affiliate_link',
            'Affiliate Link',
            array($this, 'affiliate_link_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'coupon_code',
            'Coupon Code',
            array($this, 'coupon_code_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );
    }

    public function ad_code_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<textarea name="wp_revenue_booster_settings[ad_code]" rows="4" cols="50">' . (isset($settings['ad_code']) ? esc_attr($settings['ad_code']) : '') . '</textarea>';
    }

    public function affiliate_link_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<input type="text" name="wp_revenue_booster_settings[affiliate_link]" value="' . (isset($settings['affiliate_link']) ? esc_attr($settings['affiliate_link']) : '') . '" />';
    }

    public function coupon_code_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<input type="text" name="wp_revenue_booster_settings[coupon_code]" value="' . (isset($settings['coupon_code']) ? esc_attr($settings['coupon_code']) : '') . '" />';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wpRevenueBooster');
                do_settings_sections('wpRevenueBooster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new WP_Revenue_Booster;
?>