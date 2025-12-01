/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Maximize revenue with smart affiliate link rotation, contextual ads, and dynamic coupon codes.
 * Version: 1.0
 * Author: RevenueBoost Team
 */

define('WP_REVENUE_BOOSTER_VERSION', '1.0');

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'inject_revenue_elements'));
        add_shortcode('revenue_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'assets/style.css');
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), WP_REVENUE_BOOSTER_VERSION, true);
    }

    public function inject_revenue_elements() {
        if (is_single()) {
            $affiliate_links = get_option('wp_revenue_booster_affiliate_links', array());
            $ads = get_option('wp_revenue_booster_ads', array());
            $coupons = get_option('wp_revenue_booster_coupons', array());

            if (!empty($affiliate_links)) {
                $random_link = $affiliate_links[array_rand($affiliate_links)];
                echo '<div class="wp-revenue-booster-affiliate"><a href="' . esc_url($random_link['url']) . '" target="_blank">' . esc_html($random_link['text']) . '</a></div>';
            }

            if (!empty($ads)) {
                $random_ad = $ads[array_rand($ads)];
                echo '<div class="wp-revenue-booster-ad">' . wp_kses_post($random_ad['content']) . '</div>';
            }

            if (!empty($coupons)) {
                $random_coupon = $coupons[array_rand($coupons)];
                echo '<div class="wp-revenue-booster-coupon">Coupon: <strong>' . esc_html($random_coupon['code']) . '</strong> - ' . esc_html($random_coupon['description']) . '</div>';
            }
        }
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => '',
        ), $atts, 'revenue_coupon');

        $coupons = get_option('wp_revenue_booster_coupons', array());
        foreach ($coupons as $coupon) {
            if (strtolower($coupon['brand']) == strtolower($atts['brand'])) {
                return '<div class="wp-revenue-booster-coupon">Coupon: <strong>' . esc_html($coupon['code']) . '</strong> - ' . esc_html($coupon['description']) . '</div>';
            }
        }
        return '';
    }

    public function admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page')
        );
    }

    public function settings_init() {
        register_setting('wp_revenue_booster', 'wp_revenue_booster_affiliate_links');
        register_setting('wp_revenue_booster', 'wp_revenue_booster_ads');
        register_setting('wp_revenue_booster', 'wp_revenue_booster_coupons');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_booster'); ?>
                <?php do_settings_sections('wp_revenue_booster'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Affiliate Links</th>
                        <td>
                            <textarea name="wp_revenue_booster_affiliate_links" rows="5" cols="50"><?php echo esc_textarea(json_encode(get_option('wp_revenue_booster_affiliate_links', array()))); ?></textarea><br />
                            Format: [{"url":"https://example.com", "text":"Visit Example"}]
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Ads</th>
                        <td>
                            <textarea name="wp_revenue_booster_ads" rows="5" cols="50"><?php echo esc_textarea(json_encode(get_option('wp_revenue_booster_ads', array()))); ?></textarea><br />
                            Format: [{"content":"<img src=\"ad.jpg\" />"}]
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Coupons</th>
                        <td>
                            <textarea name="wp_revenue_booster_coupons" rows="5" cols="50"><?php echo esc_textarea(json_encode(get_option('wp_revenue_booster_coupons', array()))); ?></textarea><br />
                            Format: [{"brand":"Brand", "code":"ABC123", "description":"10% off"}]
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new WP_Revenue_Booster();
