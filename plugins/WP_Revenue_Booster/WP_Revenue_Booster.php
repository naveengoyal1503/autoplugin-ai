/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automatically optimizes ad placement, affiliate links, and upsells to maximize revenue.
 * Version: 1.0
 * Author: RevenueBoost Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'inject_optimized_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function inject_optimized_content() {
        $settings = get_option('wp_revenue_booster_settings');
        if (!$settings || empty($settings['enabled'])) return;

        $content = '';

        // Inject ad code
        if (!empty($settings['ad_code'])) {
            $content .= '<div class="wp-revenue-ad">' . $settings['ad_code'] . '</div>';
        }

        // Inject affiliate links
        if (!empty($settings['affiliate_links'])) {
            $links = explode(',', $settings['affiliate_links']);
            $content .= '<div class="wp-revenue-affiliate">';
            foreach ($links as $link) {
                $content .= '<a href="' . trim($link) . '" target="_blank">Check this deal</a><br>';
            }
            $content .= '</div>';
        }

        // Inject upsell
        if (!empty($settings['upsell_message'])) {
            $content .= '<div class="wp-revenue-upsell">' . $settings['upsell_message'] . '</div>';
        }

        echo $content;
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
            'Revenue Booster Settings',
            null,
            'wp_revenue_booster'
        );

        add_settings_field(
            'enabled',
            'Enable Revenue Booster',
            array($this, 'enabled_render'),
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
            'affiliate_links',
            'Affiliate Links (comma-separated)',
            array($this, 'affiliate_links_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'upsell_message',
            'Upsell Message',
            array($this, 'upsell_message_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );
    }

    public function enabled_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<input type="checkbox" name="wp_revenue_booster_settings[enabled]" value="1" ' . checked(1, $settings['enabled'], false) . ' />';
    }

    public function ad_code_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<textarea name="wp_revenue_booster_settings[ad_code]" rows="4" cols="50">' . esc_textarea($settings['ad_code']) . '</textarea>';
    }

    public function affiliate_links_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<input type="text" name="wp_revenue_booster_settings[affiliate_links]" value="' . esc_attr($settings['affiliate_links']) . '" style="width:100%;" />';
    }

    public function upsell_message_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<input type="text" name="wp_revenue_booster_settings[upsell_message]" value="' . esc_attr($settings['upsell_message']) . '" style="width:100%;" />';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wpRevenueBooster');
                do_settings_sections('wp_revenue_booster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new WP_Revenue_Booster();
?>