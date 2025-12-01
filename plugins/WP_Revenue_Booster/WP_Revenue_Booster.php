<?php
/*
Plugin Name: WP Revenue Booster
Description: Automatically suggests and inserts high-converting affiliate links, coupons, and sponsored content into your posts.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'inject_monetization_elements'));
    }

    public function add_admin_menu() {
        add_options_page('WP Revenue Booster', 'Revenue Booster', 'manage_options', 'wp_revenue_booster', array($this, 'options_page'));
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
            'affiliate_links',
            'Affiliate Links (JSON)',
            array($this, 'affiliate_links_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'coupons',
            'Coupons (JSON)',
            array($this, 'coupons_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'sponsored_content',
            'Sponsored Content (JSON)',
            array($this, 'sponsored_content_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea rows="5" cols="50" name="wp_revenue_booster_settings[affiliate_links]">' . (isset($options['affiliate_links']) ? esc_attr($options['affiliate_links']) : '') . '</textarea>';
    }

    public function coupons_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea rows="5" cols="50" name="wp_revenue_booster_settings[coupons]">' . (isset($options['coupons']) ? esc_attr($options['coupons']) : '') . '</textarea>';
    }

    public function sponsored_content_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea rows="5" cols="50" name="wp_revenue_booster_settings[sponsored_content]">' . (isset($options['sponsored_content']) ? esc_attr($options['sponsored_content']) : '') . '</textarea>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>WP Revenue Booster</h2>
            <?php
            settings_fields('wpRevenueBooster');
            do_settings_sections('wpRevenueBooster');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function inject_monetization_elements($content) {
        $options = get_option('wp_revenue_booster_settings');
        $affiliate_links = isset($options['affiliate_links']) ? json_decode($options['affiliate_links'], true) : array();
        $coupons = isset($options['coupons']) ? json_decode($options['coupons'], true) : array();
        $sponsored_content = isset($options['sponsored_content']) ? json_decode($options['sponsored_content'], true) : array();

        // Simple logic: inject first affiliate link, coupon, and sponsored content
        if (!empty($affiliate_links)) {
            $link = $affiliate_links;
            $content .= '<p><a href="' . esc_url($link['url']) . '" target="_blank">' . esc_html($link['text']) . '</a></p>';
        }
        if (!empty($coupons)) {
            $coupon = $coupons;
            $content .= '<p>Use coupon <strong>' . esc_html($coupon['code']) . '</strong> for ' . esc_html($coupon['discount']) . ' off!</p>';
        }
        if (!empty($sponsored_content)) {
            $sp = $sponsored_content;
            $content .= '<div class="sponsored-content">' . wp_kses_post($sp['html']) . '</div>';
        }

        return $content;
    }
}

new WP_Revenue_Booster();
?>