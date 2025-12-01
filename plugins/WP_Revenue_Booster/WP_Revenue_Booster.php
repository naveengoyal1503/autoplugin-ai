<?php
/*
Plugin Name: WP Revenue Booster
Description: Automatically optimizes and manages multiple monetization streams for WordPress sites.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_code'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function add_admin_menu() {
        add_menu_page(
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
            'Monetization Settings',
            null,
            'wp_revenue_booster'
        );

        add_settings_field(
            'ads_enabled',
            'Enable Ads',
            array($this, 'ads_enabled_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'affiliate_enabled',
            'Enable Affiliate Links',
            array($this, 'affiliate_enabled_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'premium_content_enabled',
            'Enable Premium Content',
            array($this, 'premium_content_enabled_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );
    }

    public function ads_enabled_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <input type='checkbox' name='wp_revenue_booster_settings[ads_enabled]' <?php checked($options['ads_enabled'] ?? 0, 1); ?> value='1'>
        <?php
    }

    public function affiliate_enabled_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <input type='checkbox' name='wp_revenue_booster_settings[affiliate_enabled]' <?php checked($options['affiliate_enabled'] ?? 0, 1); ?> value='1'>
        <?php
    }

    public function premium_content_enabled_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <input type='checkbox' name='wp_revenue_booster_settings[premium_content_enabled]' <?php checked($options['premium_content_enabled'] ?? 0, 1); ?> value='1'>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <?php
            settings_fields('wp_revenue_booster');
            do_settings_sections('wp_revenue_booster');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function inject_monetization_code() {
        $options = get_option('wp_revenue_booster_settings');

        if ($options['ads_enabled'] ?? 0) {
            echo '<div class="wp-revenue-ads">Ad placeholder</div>';
        }

        if ($options['affiliate_enabled'] ?? 0) {
            echo '<div class="wp-revenue-affiliate">Affiliate link placeholder</div>';
        }

        if ($options['premium_content_enabled'] ?? 0) {
            echo '<div class="wp-revenue-premium">Premium content placeholder</div>';
        }
    }
}

new WP_Revenue_Booster();
?>