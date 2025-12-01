/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Boost your WordPress site revenue with smart affiliate, coupon, and sponsored content placement.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'inject_monetization_content'));
        add_action('admin_init', array($this, 'settings_init'));
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
            'Monetization Settings',
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
        echo '<textarea cols="60" rows="5" name="wp_revenue_booster_settings[affiliate_links]">' . (isset($options['affiliate_links']) ? esc_attr($options['affiliate_links']) : '') . '</textarea>';
    }

    public function coupons_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea cols="60" rows="5" name="wp_revenue_booster_settings[coupons]">' . (isset($options['coupons']) ? esc_attr($options['coupons']) : '') . '</textarea>';
    }

    public function sponsored_content_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea cols="60" rows="5" name="wp_revenue_booster_settings[sponsored_content]">' . (isset($options['sponsored_content']) ? esc_attr($options['sponsored_content']) : '') . '</textarea>';
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

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function inject_monetization_content($content) {
        $options = get_option('wp_revenue_booster_settings');
        $affiliate_links = isset($options['affiliate_links']) ? json_decode($options['affiliate_links'], true) : array();
        $coupons = isset($options['coupons']) ? json_decode($options['coupons'], true) : array();
        $sponsored_content = isset($options['sponsored_content']) ? json_decode($options['sponsored_content'], true) : array();

        $monetization_html = '';

        if (!empty($affiliate_links)) {
            $monetization_html .= '<div class="wp-revenue-booster-affiliate">
                <h3>Recommended Products</h3>
                <ul>';
            foreach ($affiliate_links as $link) {
                $monetization_html .= '<li><a href="' . esc_url($link['url']) . '" target="_blank">' . esc_html($link['title']) . '</a></li>';
            }
            $monetization_html .= '</ul></div>';
        }

        if (!empty($coupons)) {
            $monetization_html .= '<div class="wp-revenue-booster-coupons">
                <h3>Exclusive Coupons</h3>
                <ul>';
            foreach ($coupons as $coupon) {
                $monetization_html .= '<li><strong>' . esc_html($coupon['code']) . '</strong>: ' . esc_html($coupon['description']) . '</li>';
            }
            $monetization_html .= '</ul></div>';
        }

        if (!empty($sponsored_content)) {
            $monetization_html .= '<div class="wp-revenue-booster-sponsored">
                <h3>Sponsored Content</h3>';
            foreach ($sponsored_content as $content_item) {
                $monetization_html .= '<p>' . esc_html($content_item['text']) . '</p>';
            }
            $monetization_html .= '</div>';
        }

        if (!empty($monetization_html)) {
            $content .= '<div class="wp-revenue-booster-container">' . $monetization_html . '</div>';
        }

        return $content;
    }
}

new WP_Revenue_Booster();
?>