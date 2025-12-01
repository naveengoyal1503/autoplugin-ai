/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automatically optimizes ad placement, affiliate links, and upsell offers to maximize revenue.
 * Version: 1.0
 * Author: Revenue Labs
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_head', array($this, 'add_head_scripts'));
        add_action('the_content', array($this, 'inject_optimized_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function add_head_scripts() {
        // Inject lightweight tracking script
        echo '<script>document.addEventListener("DOMContentLoaded", function(){console.log("WP Revenue Booster Active");});</script>';
    }

    public function inject_optimized_content($content) {
        $settings = get_option('wp_revenue_booster_settings');
        $ad_code = isset($settings['ad_code']) ? $settings['ad_code'] : '';
        $affiliate_link = isset($settings['affiliate_link']) ? $settings['affiliate_link'] : '';
        $upsell_text = isset($settings['upsell_text']) ? $settings['upsell_text'] : '';

        if (is_single() && !empty($ad_code)) {
            $content .= '<div class="wp-revenue-ad">' . $ad_code . '</div>';
        }

        if (is_single() && !empty($affiliate_link)) {
            $content .= '<div class="wp-revenue-affiliate"><p>Recommended: <a href="' . esc_url($affiliate_link) . '" target="_blank">Check this out</a></p></div>';
        }

        if (is_single() && !empty($upsell_text)) {
            $content .= '<div class="wp-revenue-upsell"><p>' . esc_html($upsell_text) . '</p></div>';
        }

        return $content;
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
            'ad_code',
            'Ad Code',
            array($this, 'ad_code_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'affiliate_link',
            'Affiliate Link',
            array($this, 'affiliate_link_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'upsell_text',
            'Upsell Text',
            array($this, 'upsell_text_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );
    }

    public function ad_code_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<textarea name="wp_revenue_booster_settings[ad_code]" rows="4" cols="50">' . (isset($settings['ad_code']) ? esc_textarea($settings['ad_code']) : '') . '</textarea>';
    }

    public function affiliate_link_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<input type="text" name="wp_revenue_booster_settings[affiliate_link]" value="' . (isset($settings['affiliate_link']) ? esc_url($settings['affiliate_link']) : '') . '" />';
    }

    public function upsell_text_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<input type="text" name="wp_revenue_booster_settings[upsell_text]" value="' . (isset($settings['upsell_text']) ? esc_attr($settings['upsell_text']) : '') . '" />';
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
}

new WP_Revenue_Booster();
?>