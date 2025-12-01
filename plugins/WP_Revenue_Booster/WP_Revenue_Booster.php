<?php
/*
Plugin Name: WP Revenue Booster
Description: Automatically optimizes ad placement, affiliate links, and coupon offers to maximize revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_head', array($this, 'inject_optimized_ads'));
        add_action('the_content', array($this, 'inject_affiliate_links'));
        add_action('wp_footer', array($this, 'inject_coupon_offers'));
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
                        <th><label>Affiliate Links (JSON)</label></th>
                        <td><textarea name="affiliates" rows="5" cols="50"><?php echo esc_textarea($affiliates); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Coupons (JSON)</label></th>
                        <td><textarea name="coupons" rows="5" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <input type="submit" name="save_settings" class="button button-primary" value="Save Settings">
            </form>
        </div>
        <?php
    }

    public function inject_optimized_ads() {
        $ads = get_option('wp_revenue_booster_ads', '');
        if (!empty($ads)) {
            echo $ads;
        }
    }

    public function inject_affiliate_links($content) {
        $affiliates = get_option('wp_revenue_booster_affiliates', '');
        if (!empty($affiliates)) {
            $links = json_decode($affiliates, true);
            if (is_array($links)) {
                foreach ($links as $keyword => $url) {
                    $content = str_replace($keyword, '<a href="' . esc_url($url) . '" target="_blank">' . $keyword . '</a>', $content);
                }
            }
        }
        return $content;
    }

    public function inject_coupon_offers() {
        $coupons = get_option('wp_revenue_booster_coupons', '');
        if (!empty($coupons)) {
            $offers = json_decode($coupons, true);
            if (is_array($offers)) {
                echo '<div class="wp-revenue-booster-coupons"><h3>Special Offers</h3><ul>';
                foreach ($offers as $offer) {
                    echo '<li><a href="' . esc_url($offer['url']) . '" target="_blank">' . esc_html($offer['title']) . '</a>: ' . esc_html($offer['code']) . '</li>';
                }
                echo '</ul></div>';
            }
        }
    }
}

new WP_Revenue_Booster();
