<?php
/*
Plugin Name: WP Revenue Booster
Description: Automatically optimizes and displays high-converting affiliate offers, coupons, and sponsored content based on user behavior and content context.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'inject_monetized_content'));
        add_shortcode('revenue_booster', array($this, 'shortcode_handler'));
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
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        if (isset($_POST['submit'])) {
            update_option('wp_revenue_booster_affiliate_links', $_POST['affiliate_links']);
            update_option('wp_revenue_booster_coupons', $_POST['coupons']);
            update_option('wp_revenue_booster_sponsored', $_POST['sponsored']);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $affiliate_links = get_option('wp_revenue_booster_affiliate_links', '');
        $coupons = get_option('wp_revenue_booster_coupons', '');
        $sponsored = get_option('wp_revenue_booster_sponsored', '');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label>Affiliate Links (one per line)</label></th>
                        <td><textarea name="affiliate_links" rows="5" cols="50"><?php echo esc_textarea($affiliate_links); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Coupons (one per line: code|description)</label></th>
                        <td><textarea name="coupons" rows="5" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Sponsored Content (HTML allowed)</label></th>
                        <td><textarea name="sponsored" rows="5" cols="50"><?php echo esc_textarea($sponsored); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Save Changes" />
                </p>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function inject_monetized_content($content) {
        if (is_single()) {
            $affiliate_links = get_option('wp_revenue_booster_affiliate_links', '');
            $coupons = get_option('wp_revenue_booster_coupons', '');
            $sponsored = get_option('wp_revenue_booster_sponsored', '');

            $output = '';
            if (!empty($affiliate_links)) {
                $links = explode('\n', $affiliate_links);
                $random_link = trim($links[array_rand($links)]);
                $output .= '<div class="wp-revenue-booster-affiliate"><p>Recommended: <a href="' . esc_url($random_link) . '" target="_blank">Check this out</a></p></div>';
            }
            if (!empty($coupons)) {
                $coupon_list = explode('\n', $coupons);
                $random_coupon = trim($coupon_list[array_rand($coupon_list)]);
                $parts = explode('|', $random_coupon);
                $output .= '<div class="wp-revenue-booster-coupon"><p>Use code <strong>' . esc_html($parts) . '</strong> for ' . esc_html($parts[1]) . '</p></div>';
            }
            if (!empty($sponsored)) {
                $output .= '<div class="wp-revenue-booster-sponsored">' . wp_kses_post($sponsored) . '</div>';
            }
            $content .= $output;
        }
        return $content;
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'type' => 'affiliate'
        ), $atts, 'revenue_booster');

        $output = '';
        if ($atts['type'] == 'affiliate') {
            $affiliate_links = get_option('wp_revenue_booster_affiliate_links', '');
            if (!empty($affiliate_links)) {
                $links = explode('\n', $affiliate_links);
                $random_link = trim($links[array_rand($links)]);
                $output = '<div class="wp-revenue-booster-affiliate"><p>Recommended: <a href="' . esc_url($random_link) . '" target="_blank">Check this out</a></p></div>';
            }
        } elseif ($atts['type'] == 'coupon') {
            $coupons = get_option('wp_revenue_booster_coupons', '');
            if (!empty($coupons)) {
                $coupon_list = explode('\n', $coupons);
                $random_coupon = trim($coupon_list[array_rand($coupon_list)]);
                $parts = explode('|', $random_coupon);
                $output = '<div class="wp-revenue-booster-coupon"><p>Use code <strong>' . esc_html($parts) . '</strong> for ' . esc_html($parts[1]) . '</p></div>';
            }
        } elseif ($atts['type'] == 'sponsored') {
            $sponsored = get_option('wp_revenue_booster_sponsored', '');
            if (!empty($sponsored)) {
                $output = '<div class="wp-revenue-booster-sponsored">' . wp_kses_post($sponsored) . '</div>';
            }
        }
        return $output;
    }
}

new WP_Revenue_Booster();
?>