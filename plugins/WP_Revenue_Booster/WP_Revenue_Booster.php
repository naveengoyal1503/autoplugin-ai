/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Automatically inserts high-converting affiliate links, coupons, and sponsored content into your posts.
 * Version: 1.0
 * Author: Your Company
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'insert_monetization_content'));
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
            'Monetization Settings',
            null,
            'wpRevenueBooster'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links (JSON)',
            array($this, 'affiliate_links_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'coupons',
            'Coupons (JSON)',
            array($this, 'coupons_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'sponsored_content',
            'Sponsored Content (JSON)',
            array($this, 'sponsored_content_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <textarea cols='60' rows='5' name='wp_revenue_booster_settings[affiliate_links]'>
            <?php echo esc_textarea($options['affiliate_links'] ?? ''); ?>
        </textarea>
        <p>Format: [{"keyword":"WordPress", "url":"https://example.com/affiliate/wordpress"}]</p>
        <?php
    }

    public function coupons_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <textarea cols='60' rows='5' name='wp_revenue_booster_settings[coupons]'>
            <?php echo esc_textarea($options['coupons'] ?? ''); ?>
        </textarea>
        <p>Format: [{"keyword":"WordPress", "code":"SAVE10", "url":"https://example.com/coupon"}]</p>
        <?php
    }

    public function sponsored_content_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <textarea cols='60' rows='5' name='wp_revenue_booster_settings[sponsored_content]'>
            <?php echo esc_textarea($options['sponsored_content'] ?? ''); ?>
        </textarea>
        <p>Format: [{"keyword":"WordPress", "content":"<div class=\"sponsored\">Sponsored by Example</div>"}]</p>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>WP Revenue Booster</h2>
            <?php
            settings_fields('wpRevenueBooster');
            do_settings_sections('wpRevenueBooster');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function insert_monetization_content($content) {
        $options = get_option('wp_revenue_booster_settings');
        $affiliate_links = json_decode($options['affiliate_links'] ?? '[]', true);
        $coupons = json_decode($options['coupons'] ?? '[]', true);
        $sponsored_content = json_decode($options['sponsored_content'] ?? '[]', true);

        foreach ($affiliate_links as $link) {
            $keyword = $link['keyword'] ?? '';
            $url = $link['url'] ?? '';
            if ($keyword && $url) {
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<a href="' . esc_url($url) . '" target="_blank">' . $keyword . '</a>', $content, 1);
            }
        }

        foreach ($coupons as $coupon) {
            $keyword = $coupon['keyword'] ?? '';
            $code = $coupon['code'] ?? '';
            $url = $coupon['url'] ?? '';
            if ($keyword && $code && $url) {
                $content .= '<p>Use coupon <a href="' . esc_url($url) . '" target="_blank">' . $code . '</a> for ' . $keyword . '.</p>';
            }
        }

        foreach ($sponsored_content as $sp) {
            $keyword = $sp['keyword'] ?? '';
            $content_html = $sp['content'] ?? '';
            if ($keyword && $content_html) {
                $content .= $content_html;
            }
        }

        return $content;
    }
}

new WP_Revenue_Booster();
