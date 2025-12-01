/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Boost your WordPress site's revenue with smart ad, affiliate, and coupon optimization.
 * Version: 1.0
 * Author: WP Revenue Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_optimized_content'));
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
        register_setting('wp_revenue_booster', 'wp_revenue_booster_settings');

        add_settings_section(
            'wp_revenue_booster_section',
            'Revenue Booster Settings',
            null,
            'wp_revenue_booster'
        );

        add_settings_field(
            'ads_enabled',
            'Enable Ad Optimization',
            array($this, 'ads_enabled_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'affiliate_enabled',
            'Enable Affiliate Link Optimization',
            array($this, 'affiliate_enabled_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'coupons_enabled',
            'Enable Coupon Optimization',
            array($this, 'coupons_enabled_render'),
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

    public function coupons_enabled_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <input type='checkbox' name='wp_revenue_booster_settings[coupons_enabled]' <?php checked($options['coupons_enabled'] ?? 0, 1); ?> value='1'>
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

    public function inject_optimized_content() {
        $options = get_option('wp_revenue_booster_settings');

        if ($options['ads_enabled'] ?? 0) {
            echo '<div class="wp-revenue-ads">Smart Ad Placement</div>';
        }

        if ($options['affiliate_enabled'] ?? 0) {
            echo '<div class="wp-revenue-affiliate">Optimized Affiliate Links</div>';
        }

        if ($options['coupons_enabled'] ?? 0) {
            echo '<div class="wp-revenue-coupons">Best Coupon Offers</div>';
        }
    }
}

new WP_Revenue_Booster();
?>