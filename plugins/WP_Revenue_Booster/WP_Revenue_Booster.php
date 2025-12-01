<?php
/*
Plugin Name: WP Revenue Booster
Description: Analyzes your site to recommend and automate monetization strategies.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function add_admin_menu() {
        add_menu_page('Revenue Booster', 'Revenue Booster', 'manage_options', 'wp-revenue-booster', array($this, 'plugin_page'), 'dashicons-chart-bar', 6);
    }

    public function settings_init() {
        register_setting('wpRevenueBooster', 'wp_revenue_booster_settings');
        add_settings_section('wpRevenueBooster_section', 'Monetization Settings', null, 'wpRevenueBooster');
        add_settings_field('wp_revenue_booster_ad_type', 'Preferred Ad Type', array($this, 'ad_type_render'), 'wpRevenueBooster', 'wpRevenueBooster_section');
        add_settings_field('wp_revenue_booster_affiliate', 'Affiliate Program', array($this, 'affiliate_render'), 'wpRevenueBooster', 'wpRevenueBooster_section');
    }

    public function ad_type_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <select name='wp_revenue_booster_settings[ad_type]'>
            <option value='adsense' <?php selected($options['ad_type'], 'adsense'); ?>>Google AdSense</option>
            <option value='mediavine' <?php selected($options['ad_type'], 'mediavine'); ?>>Mediavine</option>
            <option value='manual' <?php selected($options['ad_type'], 'manual'); ?>>Manual Ads</option>
        </select>
        <?php
    }

    public function affiliate_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <input type='text' name='wp_revenue_booster_settings[affiliate]' value='<?php echo $options['affiliate']; ?>'>
        <p>Enter your affiliate program URL.</p>
        <?php
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget('wp_revenue_booster_widget', 'Revenue Booster Tips', array($this, 'dashboard_widget_render'));
    }

    public function dashboard_widget_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<p><strong>Recommended Monetization:</strong> Use ' . esc_html($options['ad_type']) . ' and promote ' . esc_html($options['affiliate']) . '.</p>';
    }

    public function plugin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('wpRevenueBooster');
                do_settings_sections('wpRevenueBooster');
                submit_button();
                ?>
            </form>
            <div class="notice notice-info">
                <p><strong>Pro Tip:</strong> Upgrade to premium for advanced analytics and automated ad placement.</p>
            </div>
        </div>
        <?php
    }
}

new WP_Revenue_Booster();
?>