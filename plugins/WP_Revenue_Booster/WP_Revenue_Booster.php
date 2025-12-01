/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Automatically optimizes ad placements, affiliate links, and upsell offers to maximize site revenue.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_head', array($this, 'add_tracking_code'));
        add_action('the_content', array($this, 'inject_optimized_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function add_tracking_code() {
        echo '<!-- WP Revenue Booster Tracking -->';
    }

    public function inject_optimized_content($content) {
        $settings = get_option('wp_revenue_booster_settings');
        $ads_enabled = isset($settings['enable_ads']) ? $settings['enable_ads'] : false;
        $affiliate_enabled = isset($settings['enable_affiliate']) ? $settings['enable_affiliate'] : false;
        $upsell_enabled = isset($settings['enable_upsell']) ? $settings['enable_upsell'] : false;

        if ($ads_enabled) {
            $ad_code = $settings['ad_code'];
            $content .= '<div class="wp-revenue-booster-ad">' . $ad_code . '</div>';
        }

        if ($affiliate_enabled) {
            $affiliate_link = $settings['affiliate_link'];
            $content .= '<div class="wp-revenue-booster-affiliate">Check out <a href="' . esc_url($affiliate_link) . '" target="_blank">this product</a>.</div>';
        }

        if ($upsell_enabled) {
            $upsell_message = $settings['upsell_message'];
            $content .= '<div class="wp-revenue-booster-upsell">' . $upsell_message . '</div>';
        }

        return $content;
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp_revenue_booster',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('wp_revenue_booster', 'wp_revenue_booster_settings');

        add_settings_section(
            'wp_revenue_booster_section',
            'Settings',
            null,
            'wp_revenue_booster'
        );

        add_settings_field(
            'enable_ads',
            'Enable Ad Optimization',
            array($this, 'enable_ads_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'ad_code',
            'Ad Code',
            array($this, 'ad_code_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'enable_affiliate',
            'Enable Affiliate Link Optimization',
            array($this, 'enable_affiliate_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'affiliate_link',
            'Affiliate Link',
            array($this, 'affiliate_link_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'enable_upsell',
            'Enable Upsell Offers',
            array($this, 'enable_upsell_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'upsell_message',
            'Upsell Message',
            array($this, 'upsell_message_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );
    }

    public function enable_ads_render() {
        $settings = get_option('wp_revenue_booster_settings');
        $checked = isset($settings['enable_ads']) ? $settings['enable_ads'] : false;
        echo '<input type="checkbox" name="wp_revenue_booster_settings[enable_ads]" value="1" ' . checked(1, $checked, false) . ' />';
    }

    public function ad_code_render() {
        $settings = get_option('wp_revenue_booster_settings');
        $value = isset($settings['ad_code']) ? $settings['ad_code'] : '';
        echo '<textarea name="wp_revenue_booster_settings[ad_code]" rows="4" cols="50">' . esc_textarea($value) . '</textarea>';
    }

    public function enable_affiliate_render() {
        $settings = get_option('wp_revenue_booster_settings');
        $checked = isset($settings['enable_affiliate']) ? $settings['enable_affiliate'] : false;
        echo '<input type="checkbox" name="wp_revenue_booster_settings[enable_affiliate]" value="1" ' . checked(1, $checked, false) . ' />';
    }

    public function affiliate_link_render() {
        $settings = get_option('wp_revenue_booster_settings');
        $value = isset($settings['affiliate_link']) ? $settings['affiliate_link'] : '';
        echo '<input type="text" name="wp_revenue_booster_settings[affiliate_link]" value="' . esc_url($value) . '" />';
    }

    public function enable_upsell_render() {
        $settings = get_option('wp_revenue_booster_settings');
        $checked = isset($settings['enable_upsell']) ? $settings['enable_upsell'] : false;
        echo '<input type="checkbox" name="wp_revenue_booster_settings[enable_upsell]" value="1" ' . checked(1, $checked, false) . ' />';
    }

    public function upsell_message_render() {
        $settings = get_option('wp_revenue_booster_settings');
        $value = isset($settings['upsell_message']) ? $settings['upsell_message'] : '';
        echo '<input type="text" name="wp_revenue_booster_settings[upsell_message]" value="' . esc_attr($value) . '" />';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_revenue_booster');
                do_settings_sections('wp_revenue_booster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new WP_Revenue_Booster();
