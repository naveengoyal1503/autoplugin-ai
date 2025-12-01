/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Automatically optimizes ad placement, affiliate links, and upsells to maximize revenue.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_head', array($this, 'add_head_scripts'));
        add_action('the_content', array($this, 'inject_optimized_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function add_head_scripts() {
        // Optional: Add lightweight tracking or optimization scripts
        echo '<!-- WP Revenue Booster: Optimizing your monetization -->';
    }

    public function inject_optimized_content($content) {
        // Only run on single posts/pages
        if (!is_singular()) return $content;

        $settings = get_option('wp_revenue_booster_settings');
        $ad_code = isset($settings['ad_code']) ? $settings['ad_code'] : '';
        $affiliate_link = isset($settings['affiliate_link']) ? $settings['affiliate_link'] : '';
        $upsell_text = isset($settings['upsell_text']) ? $settings['upsell_text'] : '';

        // Inject ad after first paragraph
        $paragraphs = explode('</p>', $content);
        if (count($paragraphs) > 1) {
            $paragraphs[1] .= '</p>' . $ad_code;
            $content = implode('</p>', $paragraphs);
        }

        // Add affiliate link at the end
        if ($affiliate_link) {
            $content .= '<p><a href="' . esc_url($affiliate_link) . '" target="_blank">Recommended Product</a></p>';
        }

        // Add upsell text
        if ($upsell_text) {
            $content .= '<p><strong>' . esc_html($upsell_text) . '</strong></p>';
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
        echo '<textarea name="wp_revenue_booster_settings[ad_code]" rows="4" cols="50">' . esc_textarea($settings['ad_code'] ?? '') . '</textarea>';
    }

    public function affiliate_link_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<input type="text" name="wp_revenue_booster_settings[affiliate_link]" value="' . esc_attr($settings['affiliate_link'] ?? '') . '" size="50" />';
    }

    public function upsell_text_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<input type="text" name="wp_revenue_booster_settings[upsell_text]" value="' . esc_attr($settings['upsell_text'] ?? '') . '" size="50" />';
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
