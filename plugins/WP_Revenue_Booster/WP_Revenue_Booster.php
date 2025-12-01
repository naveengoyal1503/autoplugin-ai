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
        add_action('wp_footer', array($this, 'inject_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'js/revenue-booster.js', array(), '1.0', true);
        wp_localize_script('wp-revenue-booster', 'wpRevenueBooster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('revenue_booster_nonce')
        ));
    }

    public function inject_content() {
        $options = get_option('wp_revenue_booster_options');
        if (!$options) return;

        $content = '';
        if (!empty($options['affiliate_links'])) {
            $links = explode(',', $options['affiliate_links']);
            $link = $links[array_rand($links)];
            $content .= '<div class="wp-revenue-booster-affiliate"><a href="' . esc_url($link) . '" target="_blank">Check this out!</a></div>';
        }
        if (!empty($options['ads'])) {
            $ads = explode(',', $options['ads']);
            $ad = $ads[array_rand($ads)];
            $content .= '<div class="wp-revenue-booster-ad">' . $ad . '</div>';
        }
        if (!empty($options['sponsored'])) {
            $sponsored = explode(',', $options['sponsored']);
            $spon = $sponsored[array_rand($sponsored)];
            $content .= '<div class="wp-revenue-booster-sponsored">' . $spon . '</div>';
        }

        echo $content;
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
            'wpRevenueBooster'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links (comma-separated)',
            array($this, 'affiliate_links_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );
        add_settings_field(
            'ads',
            'Ad Codes (comma-separated)',
            array($this, 'ads_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );
        add_settings_field(
            'sponsored',
            'Sponsored Content (comma-separated)',
            array($this, 'sponsored_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_options');
        echo '<textarea name="wp_revenue_booster_options[affiliate_links]" rows="3" cols="50">' . (isset($options['affiliate_links']) ? esc_attr($options['affiliate_links']) : '') . '</textarea>';
    }

    public function ads_render() {
        $options = get_option('wp_revenue_booster_options');
        echo '<textarea name="wp_revenue_booster_options[ads]" rows="3" cols="50">' . (isset($options['ads']) ? esc_attr($options['ads']) : '') . '</textarea>';
    }

    public function sponsored_render() {
        $options = get_option('wp_revenue_booster_options');
        echo '<textarea name="wp_revenue_booster_options[sponsored]" rows="3" cols="50">' . (isset($options['sponsored']) ? esc_attr($options['sponsored']) : '') . '</textarea>';
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