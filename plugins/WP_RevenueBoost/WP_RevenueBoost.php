/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_RevenueBoost.php
*/
<?php
/**
 * Plugin Name: WP RevenueBoost
 * Description: Automatically optimizes and manages multiple monetization streams for WordPress sites.
 * Version: 1.0
 * Author: RevenueBoost Team
 */

class WP_RevenueBoost {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_head', array($this, 'inject_ad_code'));
        add_action('the_content', array($this, 'inject_affiliate_links'));
        add_action('template_redirect', array($this, 'handle_paywall'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'RevenueBoost',
            'RevenueBoost',
            'manage_options',
            'wp_revenueboost',
            array($this, 'options_page'),
            'dashicons-chart-line'
        );
    }

    public function settings_init() {
        register_setting('wp_revenueboost', 'wp_revenueboost_settings');

        add_settings_section(
            'wp_revenueboost_section',
            'RevenueBoost Settings',
            null,
            'wp_revenueboost'
        );

        add_settings_field(
            'ad_code',
            'Ad Code',
            array($this, 'ad_code_render'),
            'wp_revenueboost',
            'wp_revenueboost_section'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links (JSON)',
            array($this, 'affiliate_links_render'),
            'wp_revenueboost',
            'wp_revenueboost_section'
        );

        add_settings_field(
            'paywall_enabled',
            'Enable Paywall',
            array($this, 'paywall_enabled_render'),
            'wp_revenueboost',
            'wp_revenueboost_section'
        );

        add_settings_field(
            'paywall_percentage',
            'Paywall Percentage (%)',
            array($this, 'paywall_percentage_render'),
            'wp_revenueboost',
            'wp_revenueboost_section'
        );
    }

    public function ad_code_render() {
        $settings = get_option('wp_revenueboost_settings');
        echo '<textarea name="wp_revenueboost_settings[ad_code]" rows="5" cols="50">' . esc_textarea($settings['ad_code']) . '</textarea>';
    }

    public function affiliate_links_render() {
        $settings = get_option('wp_revenueboost_settings');
        echo '<textarea name="wp_revenueboost_settings[affiliate_links]" rows="5" cols="50">' . esc_textarea($settings['affiliate_links']) . '</textarea>';
    }

    public function paywall_enabled_render() {
        $settings = get_option('wp_revenueboost_settings');
        echo '<input type="checkbox" name="wp_revenueboost_settings[paywall_enabled]" value="1" ' . checked(1, $settings['paywall_enabled'], false) . ' />';
    }

    public function paywall_percentage_render() {
        $settings = get_option('wp_revenueboost_settings');
        echo '<input type="number" name="wp_revenueboost_settings[paywall_percentage]" value="' . esc_attr($settings['paywall_percentage']) . '" min="0" max="100" />';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP RevenueBoost</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_revenueboost');
                do_settings_sections('wp_revenueboost');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function inject_ad_code() {
        $settings = get_option('wp_revenueboost_settings');
        if (!empty($settings['ad_code'])) {
            echo $settings['ad_code'];
        }
    }

    public function inject_affiliate_links($content) {
        $settings = get_option('wp_revenueboost_settings');
        if (!empty($settings['affiliate_links'])) {
            $links = json_decode($settings['affiliate_links'], true);
            if (is_array($links)) {
                foreach ($links as $keyword => $url) {
                    $content = str_replace($keyword, '<a href="' . esc_url($url) . '" target="_blank">' . $keyword . '</a>', $content);
                }
            }
        }
        return $content;
    }

    public function handle_paywall() {
        $settings = get_option('wp_revenueboost_settings');
        if (!is_admin() && is_single() && !current_user_can('manage_options')) {
            if (!empty($settings['paywall_enabled']) && !empty($settings['paywall_percentage'])) {
                if (rand(1, 100) <= $settings['paywall_percentage']) {
                    wp_die('This content is behind a paywall. Please subscribe to access.');
                }
            }
        }
    }
}

new WP_RevenueBoost();
?>