/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Maximize revenue by rotating and optimizing affiliate links, ads, and sponsored content.
 * Version: 1.0
 * Author: Your Company
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'output_tracking_code'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'js/revenue-booster.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-booster', 'wpRevenueBooster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('revenue_booster_nonce')
        ));
    }

    public function output_tracking_code() {
        echo '<script>/* Tracking code for conversion events */</script>';
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'plugin_settings_page')
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
            'affiliate_links',
            'Affiliate Links (one per line)',
            array($this, 'affiliate_links_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'ad_codes',
            'Ad Codes (one per line)',
            array($this, 'ad_codes_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'sponsored_content',
            'Sponsored Content (one per line)',
            array($this, 'sponsored_content_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea name="wp_revenue_booster_settings[affiliate_links]" rows="5" cols="50">' . (isset($options['affiliate_links']) ? esc_attr($options['affiliate_links']) : '') . '</textarea>';
    }

    public function ad_codes_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea name="wp_revenue_booster_settings[ad_codes]" rows="5" cols="50">' . (isset($options['ad_codes']) ? esc_attr($options['ad_codes']) : '') . '</textarea>';
    }

    public function sponsored_content_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea name="wp_revenue_booster_settings[sponsored_content]" rows="5" cols="50">' . (isset($options['sponsored_content']) ? esc_attr($options['sponsored_content']) : '') . '</textarea>';
    }

    public function plugin_settings_page() {
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
        </div>
        <?php
    }
}

new WP_Revenue_Booster();
?>