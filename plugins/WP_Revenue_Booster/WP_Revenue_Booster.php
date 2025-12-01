<?php
/*
Plugin Name: WP Revenue Booster
Description: Automatically optimizes ad placements, affiliate links, and coupon offers for maximum revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_optimized_content'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
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
        if (isset($_POST['save_settings'])) {
            update_option('wp_revenue_booster_ads', $_POST['ads']);
            update_option('wp_revenue_booster_affiliates', $_POST['affiliates']);
            update_option('wp_revenue_booster_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $ads = get_option('wp_revenue_booster_ads', '');
        $affiliates = get_option('wp_revenue_booster_affiliates', '');
        $coupons = get_option('wp_revenue_booster_coupons', '');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label>Ad Code</label></th>
                        <td><textarea name="ads" rows="5" cols="50"><?php echo esc_textarea($ads); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Affiliate Links (one per line)</label></th>
                        <td><textarea name="affiliates" rows="5" cols="50"><?php echo esc_textarea($affiliates); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Coupons (one per line: code|description)</label></th>
                        <td><textarea name="coupons" rows="5" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="save_settings" class="button-primary" value="Save Settings" />
                </p>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function inject_optimized_content() {
        $ads = get_option('wp_revenue_booster_ads', '');
        $affiliates = get_option('wp_revenue_booster_affiliates', '');
        $coupons = get_option('wp_revenue_booster_coupons', '');

        if (!empty($ads)) {
            echo '<div class="wp-revenue-booster-ads">' . $ads . '</div>';
        }

        if (!empty($affiliates)) {
            $links = explode('\n', $affiliates);
            shuffle($links);
            $link = trim($links);
            if (!empty($link)) {
                echo '<div class="wp-revenue-booster-affiliate"><a href="' . esc_url($link) . '" target="_blank">Recommended Product</a></div>';
            }
        }

        if (!empty($coupons)) {
            $coupon_list = explode('\n', $coupons);
            shuffle($coupon_list);
            $coupon = trim($coupon_list);
            if (!empty($coupon)) {
                list($code, $desc) = explode('|', $coupon);
                echo '<div class="wp-revenue-booster-coupon"><strong>' . esc_html($code) . '</strong> - ' . esc_html($desc) . '</div>';
            }
        }
    }
}

new WP_Revenue_Booster;
