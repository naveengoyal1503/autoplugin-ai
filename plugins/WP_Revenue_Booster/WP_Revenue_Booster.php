<?php
/*
Plugin Name: WP Revenue Booster
Description: Maximize your WordPress site's revenue by rotating and optimizing affiliate links, ads, and sponsored content.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'inject_revenue_elements'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster', plugins_url('/js/revenue-booster.js', __FILE__), array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-booster', 'wpRevenueBooster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('revenue_booster_nonce')
        ));
    }

    public function inject_revenue_elements() {
        $options = get_option('wp_revenue_booster_settings');
        if (!$options) return;

        $elements = array();
        if (!empty($options['affiliate_links'])) {
            $elements['affiliate'] = $options['affiliate_links'];
        }
        if (!empty($options['ads'])) {
            $elements['ad'] = $options['ads'];
        }
        if (!empty($options['sponsored'])) {
            $elements['sponsored'] = $options['sponsored'];
        }

        if (!empty($elements)) {
            $selected = array_rand($elements);
            $content = $elements[$selected][array_rand($elements[$selected])];
            echo '<div class="wp-revenue-element">' . wp_kses_post($content) . '</div>';
        }
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
        register_setting('wpRevenueBooster', 'wp_revenue_booster_settings');

        add_settings_section(
            'wp_revenue_booster_section',
            'Revenue Elements',
            null,
            'wpRevenueBooster'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links',
            array($this, 'affiliate_links_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'ads',
            'Display Ads',
            array($this, 'ads_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'sponsored',
            'Sponsored Content',
            array($this, 'sponsored_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_settings');
        $links = isset($options['affiliate_links']) ? $options['affiliate_links'] : array();
        echo '<textarea name="wp_revenue_booster_settings[affiliate_links][]" rows="3" cols="50">' . implode('\n', $links) . '</textarea><br>';
        echo '<button type="button" onclick="addRow(this)">Add Another</button>';
    }

    public function ads_render() {
        $options = get_option('wp_revenue_booster_settings');
        $ads = isset($options['ads']) ? $options['ads'] : array();
        echo '<textarea name="wp_revenue_booster_settings[ads][]" rows="3" cols="50">' . implode('\n', $ads) . '</textarea><br>';
        echo '<button type="button" onclick="addRow(this)">Add Another</button>';
    }

    public function sponsored_render() {
        $options = get_option('wp_revenue_booster_settings');
        $sponsored = isset($options['sponsored']) ? $options['sponsored'] : array();
        echo '<textarea name="wp_revenue_booster_settings[sponsored][]" rows="3" cols="50">' . implode('\n', $sponsored) . '</textarea><br>';
        echo '<button type="button" onclick="addRow(this)">Add Another</button>';
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
        <script>
            function addRow(button) {
                var container = button.parentNode;
                var textarea = document.createElement('textarea');
                textarea.name = button.previousElementSibling.name;
                textarea.rows = 3;
                textarea.cols = 50;
                container.insertBefore(textarea, button);
            }
        </script>
        <?php
    }
}

new WP_Revenue_Booster();
?>