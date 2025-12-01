/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Boost your WordPress site's revenue by intelligently displaying affiliate offers, coupons, and sponsored content.
 * Version: 1.0.0
 * Author: Revenue Labs
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('revenue_booster', array($this, 'revenue_booster_shortcode'));
        add_action('wp_footer', array($this, 'display_smart_offers'));
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
        if (isset($_POST['save_revenue_booster_settings'])) {
            update_option('revenue_booster_affiliate_links', $_POST['affiliate_links']);
            update_option('revenue_booster_coupons', $_POST['coupons']);
            update_option('revenue_booster_sponsored', $_POST['sponsored']);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $affiliate_links = get_option('revenue_booster_affiliate_links', '');
        $coupons = get_option('revenue_booster_coupons', '');
        $sponsored = get_option('revenue_booster_sponsored', '');
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
                        <th><label>Sponsored Content (one per line: title|url)</label></th>
                        <td><textarea name="sponsored" rows="5" cols="50"><?php echo esc_textarea($sponsored); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="save_revenue_booster_settings" class="button-primary" value="Save Settings" />
                </p>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('revenue-booster', plugin_dir_url(__FILE__) . 'revenue-booster.css');
    }

    public function revenue_booster_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'random'
        ), $atts, 'revenue_booster');

        $output = '';
        if ($atts['type'] === 'affiliate') {
            $links = explode("\n", get_option('revenue_booster_affiliate_links', ''));
            $link = trim($links[array_rand($links)]);
            $output = '<a href="' . esc_url($link) . '" target="_blank" class="revenue-booster-affiliate">Visit our affiliate offer</a>';
        } elseif ($atts['type'] === 'coupon') {
            $coupons = explode("\n", get_option('revenue_booster_coupons', ''));
            $coupon = trim($coupons[array_rand($coupons)]);
            $parts = explode('|', $coupon);
            $output = '<div class="revenue-booster-coupon"><strong>' . esc_html($parts) . '</strong> - ' . esc_html($parts[1]) . '</div>';
        } elseif ($atts['type'] === 'sponsored') {
            $sponsored = explode("\n", get_option('revenue_booster_sponsored', ''));
            $item = trim($sponsored[array_rand($sponsored)]);
            $parts = explode('|', $item);
            $output = '<a href="' . esc_url($parts[1]) . '" target="_blank" class="revenue-booster-sponsored">Sponsored: ' . esc_html($parts) . '</a>';
        } else {
            $types = array('affiliate', 'coupon', 'sponsored');
            $type = $types[array_rand($types)];
            $output = $this->revenue_booster_shortcode(array('type' => $type));
        }
        return $output;
    }

    public function display_smart_offers() {
        if (is_single() || is_page()) {
            echo '<div class="revenue-booster-widget">';
            echo do_shortcode('[revenue_booster type="random"]');
            echo '</div>';
        }
    }
}

new WP_Revenue_Booster();
?>