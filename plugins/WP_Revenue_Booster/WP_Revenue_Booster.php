/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automates and optimizes monetization streams like affiliate links, ads, and paywalls.
 * Version: 1.0
 * Author: CozmosLabs
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_elements'));
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
            'Monetization Settings',
            null,
            'wp_revenue_booster'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links (comma-separated)',
            array($this, 'affiliate_links_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'ad_code',
            'Ad Code',
            array($this, 'ad_code_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'paywall_enabled',
            'Enable Paywall',
            array($this, 'paywall_enabled_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<input type="text" name="wp_revenue_booster_settings[affiliate_links]" value="' . (isset($options['affiliate_links']) ? esc_attr($options['affiliate_links']) : '') . '" style="width: 100%;"/>';
    }

    public function ad_code_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea name="wp_revenue_booster_settings[ad_code]" rows="5" style="width: 100%;">' . (isset($options['ad_code']) ? esc_textarea($options['ad_code']) : '') . '</textarea>';
    }

    public function paywall_enabled_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<input type="checkbox" name="wp_revenue_booster_settings[paywall_enabled]" ' . (isset($options['paywall_enabled']) ? 'checked' : '') . ' />';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_revenue_booster');
                do_settings_sections('wp_revenue_booster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function inject_monetization_elements() {
        $options = get_option('wp_revenue_booster_settings');
        if (!empty($options['ad_code'])) {
            echo $options['ad_code'];
        }
        if (!empty($options['affiliate_links'])) {
            $links = explode(',', $options['affiliate_links']);
            foreach ($links as $link) {
                echo '<a href="' . esc_url(trim($link)) . '" target="_blank" style="display:none;">Affiliate Link</a>';
            }
        }
        if (!empty($options['paywall_enabled'])) {
            echo '<div id="wp-revenue-paywall" style="display:none;">Premium content is locked. Subscribe to unlock.</div>';
        }
    }
}

new WP_Revenue_Booster();
?>