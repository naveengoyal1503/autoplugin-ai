/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automatically optimizes and manages multiple monetization streams for WordPress sites.
 * Version: 1.0
 * Author: WP Revenue Team
 */

if (!defined('ABSPATH')) {
    exit;
}

// Main plugin class
class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_code'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'plugin_settings_page'),
            'dashicons-chart-bar',
            60
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

    public function plugin_settings_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('wp_revenue_booster');
                do_settings_sections('wp_revenue_booster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_wp-revenue-booster' !== $hook) {
            return;
        }
        wp_enqueue_style('wp-revenue-booster-admin', plugin_dir_url(__FILE__) . 'admin.css');
    }

    public function inject_monetization_code() {
        $options = get_option('wp_revenue_booster_settings');

        if (!empty($options['ads_enabled'])) {
            echo '<div class="wp-revenue-ads">Ad placeholder</div>';
        }

        if (!empty($options['affiliate_enabled'])) {
            echo '<div class="wp-revenue-affiliate">Affiliate link placeholder</div>';
        }

        if (!empty($options['premium_content_enabled'])) {
            echo '<div class="wp-revenue-premium">Premium content placeholder</div>';
        }
    }
}

new WP_Revenue_Booster();

// Activation hook
register_activation_hook(__FILE__, function() {
    add_option('wp_revenue_booster_settings', array(
        'ads_enabled' => 1,
        'affiliate_enabled' => 1,
        'premium_content_enabled' => 1
    ));
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    delete_option('wp_revenue_booster_settings');
});
