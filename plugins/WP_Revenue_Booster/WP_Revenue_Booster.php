/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Automatically optimizes ad placement, affiliate links, and upsell offers to maximize site revenue.
 * Version: 1.0
 * Author: Revenue Labs
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
        echo '<script>console.log("WP Revenue Booster: Tracking active");</script>';
    }

    public function inject_optimized_content($content) {
        $settings = get_option('wp_revenue_booster_settings');
        $ad_code = isset($settings['ad_code']) ? $settings['ad_code'] : '';
        $affiliate_link = isset($settings['affiliate_link']) ? $settings['affiliate_link'] : '';

        if (is_single() && !empty($ad_code)) {
            $content = str_replace('</p>', '</p>' . $ad_code, $content, 1);
        }

        if (is_single() && !empty($affiliate_link)) {
            $upsell = '<p><a href="' . esc_url($affiliate_link) . '" target="_blank">Check out this recommended product!</a></p>';
            $content .= $upsell;
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
    }

    public function ad_code_render() {
        $settings = get_option('wp_revenue_booster_settings');
        $value = isset($settings['ad_code']) ? $settings['ad_code'] : '';
        echo '<textarea name="wp_revenue_booster_settings[ad_code]" rows="5" cols="50">' . esc_textarea($value) . '</textarea>';
    }

    public function affiliate_link_render() {
        $settings = get_option('wp_revenue_booster_settings');
        $value = isset($settings['affiliate_link']) ? $settings['affiliate_link'] : '';
        echo '<input type="text" name="wp_revenue_booster_settings[affiliate_link]" value="' . esc_attr($value) . '" size="50" />';
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

new WP_Revenue_Booster();
?>