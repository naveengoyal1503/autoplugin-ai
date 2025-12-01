/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automatically inserts affiliate links, coupons, and sponsored content into posts.
 * Version: 1.0
 * Author: WP Dev Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('the_content', array($this, 'inject_monetization_content'));
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
            'Affiliate Links (JSON)',
            array($this, 'affiliate_links_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'coupons',
            'Coupons (JSON)',
            array($this, 'coupons_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'sponsored_content',
            'Sponsored Content (JSON)',
            array($this, 'sponsored_content_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <textarea cols='60' rows='5' name='wp_revenue_booster_settings[affiliate_links]'>
            <?php echo esc_textarea($options['affiliate_links'] ?? ''); ?>
        </textarea>
        <?php
    }

    public function coupons_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <textarea cols='60' rows='5' name='wp_revenue_booster_settings[coupons]'>
            <?php echo esc_textarea($options['coupons'] ?? ''); ?>
        </textarea>
        <?php
    }

    public function sponsored_content_render() {
        $options = get_option('wp_revenue_booster_settings');
        ?>
        <textarea cols='60' rows='5' name='wp_revenue_booster_settings[sponsored_content]'>
            <?php echo esc_textarea($options['sponsored_content'] ?? ''); ?>
        </textarea>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>WP Revenue Booster</h2>
            <?php
            settings_fields('wp_revenue_booster');
            do_settings_sections('wp_revenue_booster');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function inject_monetization_content($content) {
        $options = get_option('wp_revenue_booster_settings');
        $affiliate_links = json_decode($options['affiliate_links'] ?? '[]', true);
        $coupons = json_decode($options['coupons'] ?? '[]', true);
        $sponsored_content = json_decode($options['sponsored_content'] ?? '[]', true);

        // Inject affiliate links
        foreach ($affiliate_links as $keyword => $url) {
            $content = str_replace($keyword, "<a href='$url' target='_blank'>$keyword</a>", $content);
        }

        // Inject coupons
        if (!empty($coupons)) {
            $coupon_html = '<div class="wp-revenue-coupons"><h3>Exclusive Coupons</h3><ul>';
            foreach ($coupons as $coupon) {
                $coupon_html .= '<li>' . esc_html($coupon['code']) . ' - ' . esc_html($coupon['description']) . '</li>';
            }
            $coupon_html .= '</ul></div>';
            $content .= $coupon_html;
        }

        // Inject sponsored content
        if (!empty($sponsored_content)) {
            $sponsored_html = '<div class="wp-revenue-sponsored"><h3>Sponsored Content</h3>';
            foreach ($sponsored_content as $sp) {
                $sponsored_html .= '<p>' . esc_html($sp['title']) . ': ' . esc_html($sp['description']) . '</p>';
            }
            $sponsored_html .= '</div>';
            $content .= $sponsored_html;
        }

        return $content;
    }
}

new WP_Revenue_Booster();
?>