<?php
/*
Plugin Name: WP Revenue Booster
Description: Boost your WordPress site's revenue with automated affiliate links, smart ad placement, and dynamic coupon offers.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_smart_ads'));
        add_filter('the_content', array($this, 'inject_affiliate_links'));
        add_shortcode('wp_revenue_coupons', array($this, 'coupon_shortcode'));
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;
        if (isset($_POST['save_revenue_settings'])) {
            update_option('wp_revenue_affiliate_links', $_POST['affiliate_links']);
            update_option('wp_revenue_ad_code', $_POST['ad_code']);
            update_option('wp_revenue_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $affiliate_links = get_option('wp_revenue_affiliate_links', []);
        $ad_code = get_option('wp_revenue_ad_code', '');
        $coupons = get_option('wp_revenue_coupons', []);
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <h2>Affiliate Links</h2>
                <textarea name="affiliate_links" rows="5" cols="50"><?php echo esc_textarea(implode("\n", $affiliate_links)); ?></textarea>
                <p>Enter one affiliate link per line.</p>

                <h2>Ad Code</h2>
                <textarea name="ad_code" rows="5" cols="50"><?php echo esc_textarea($ad_code); ?></textarea>
                <p>Paste your ad code (e.g., Google AdSense).</p>

                <h2>Coupons</h2>
                <textarea name="coupons" rows="5" cols="50"><?php echo esc_textarea(implode("\n", $coupons)); ?></textarea>
                <p>Enter one coupon per line (format: code|description|brand).</p>

                <input type="submit" name="save_revenue_settings" class="button button-primary" value="Save Settings">
            </form>
        </div>
        <?php
    }

    public function inject_smart_ads() {
        $ad_code = get_option('wp_revenue_ad_code', '');
        if (!empty($ad_code)) {
            echo '<div class="wp-revenue-ad">' . $ad_code . '</div>';
        }
    }

    public function inject_affiliate_links($content) {
        $affiliate_links = get_option('wp_revenue_affiliate_links', []);
        if (!empty($affiliate_links)) {
            $link = $affiliate_links[array_rand($affiliate_links)];
            $content .= '<p><strong>Recommended:</strong> <a href="' . esc_url($link) . '" target="_blank">Check this out</a></p>';
        }
        return $content;
    }

    public function coupon_shortcode($atts) {
        $coupons = get_option('wp_revenue_coupons', []);
        if (empty($coupons)) return '<p>No coupons available.</p>';
        $output = '<div class="wp-revenue-coupons"><h3>Exclusive Coupons</h3><ul>';
        foreach ($coupons as $coupon) {
            $parts = explode('|', $coupon);
            if (count($parts) === 3) {
                $output .= '<li><strong>' . esc_html($parts) . '</strong> - ' . esc_html($parts[1]) . ' (' . esc_html($parts[2]) . ')</li>';
            }
        }
        $output .= '</ul></div>';
        return $output;
    }
}

new WP_Revenue_Booster();
