<?php
/*
Plugin Name: WP Revenue Booster
Description: Maximize revenue by rotating and optimizing affiliate links, ads, and sponsored content.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'output_revenue_code'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster', plugins_url('/js/revenue-booster.js', __FILE__), array(), '1.0', true);
        wp_localize_script('wp-revenue-booster', 'wpRevenueBooster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('revenue_booster_nonce')
        ));
    }

    public function output_revenue_code() {
        $options = get_option('wp_revenue_booster_options');
        if (!$options) return;

        $content = '';
        if (!empty($options['affiliate_links'])) {
            $links = explode(',', $options['affiliate_links']);
            $content .= '<div class="wp-revenue-affiliate">' . $this->rotate_content($links) . '</div>';
        }
        if (!empty($options['ad_codes'])) {
            $ads = explode('|||', $options['ad_codes']);
            $content .= '<div class="wp-revenue-ad">' . $this->rotate_content($ads) . '</div>';
        }
        if (!empty($options['sponsored_content'])) {
            $sponsored = explode('|||', $options['sponsored_content']);
            $content .= '<div class="wp-revenue-sponsored">' . $this->rotate_content($sponsored) . '</div>';
        }
        echo $content;
    }

    private function rotate_content($items) {
        $index = array_rand($items);
        return trim($items[$index]);
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
        register_setting('wpRevenueBooster', 'wp_revenue_booster_options');

        add_settings_section(
            'wp_revenue_booster_section',
            'Revenue Booster Settings',
            null,
            'wp-revenue-booster'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links (comma separated)',
            array($this, 'affiliate_links_render'),
            'wp-revenue-booster',
            'wp_revenue_booster_section'
        );
        add_settings_field(
            'ad_codes',
            'Ad Codes (separate with |||)',
            array($this, 'ad_codes_render'),
            'wp-revenue-booster',
            'wp_revenue_booster_section'
        );
        add_settings_field(
            'sponsored_content',
            'Sponsored Content (separate with |||)',
            array($this, 'sponsored_content_render'),
            'wp-revenue-booster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_options');
        echo '<textarea name="wp_revenue_booster_options[affiliate_links]" rows="5" cols="50">' . (isset($options['affiliate_links']) ? esc_attr($options['affiliate_links']) : '') . '</textarea>';
    }

    public function ad_codes_render() {
        $options = get_option('wp_revenue_booster_options');
        echo '<textarea name="wp_revenue_booster_options[ad_codes]" rows="5" cols="50">' . (isset($options['ad_codes']) ? esc_attr($options['ad_codes']) : '') . '</textarea>';
    }

    public function sponsored_content_render() {
        $options = get_option('wp_revenue_booster_options');
        echo '<textarea name="wp_revenue_booster_options[sponsored_content]" rows="5" cols="50">' . (isset($options['sponsored_content']) ? esc_attr($options['sponsored_content']) : '') . '</textarea>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wpRevenueBooster');
                do_settings_sections('wp-revenue-booster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new WP_Revenue_Booster();
?>