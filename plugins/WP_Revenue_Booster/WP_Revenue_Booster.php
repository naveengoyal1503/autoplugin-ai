/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Automatically optimizes and manages multiple monetization streams (ads, affiliate links, coupons, memberships) from a single dashboard.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_code'));
        add_shortcode('wp_revenue_booster', array($this, 'shortcode_handler'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            6
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_booster_options'); ?>
                <?php do_settings_sections('wp-revenue-booster'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">AdSense Code</th>
                        <td><textarea name="wp_revenue_booster_adsense" rows="4" cols="50"><?php echo esc_textarea(get_option('wp_revenue_booster_adsense')); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Affiliate Link</th>
                        <td><input type="text" name="wp_revenue_booster_affiliate" value="<?php echo esc_attr(get_option('wp_revenue_booster_affiliate')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Coupon Code</th>
                        <td><input type="text" name="wp_revenue_booster_coupon" value="<?php echo esc_attr(get_option('wp_revenue_booster_coupon')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Membership Link</th>
                        <td><input type="text" name="wp_revenue_booster_membership" value="<?php echo esc_attr(get_option('wp_revenue_booster_membership')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function inject_monetization_code() {
        $adsense = get_option('wp_revenue_booster_adsense', '');
        $affiliate = get_option('wp_revenue_booster_affiliate', '');
        $coupon = get_option('wp_revenue_booster_coupon', '');
        $membership = get_option('wp_revenue_booster_membership', '');

        if (!empty($adsense)) {
            echo '<div class="wp-revenue-adsense">' . $adsense . '</div>';
        }
        if (!empty($affiliate)) {
            echo '<div class="wp-revenue-affiliate">Check out our <a href="' . esc_url($affiliate) . '" target="_blank">affiliate link</a>.</div>';
        }
        if (!empty($coupon)) {
            echo '<div class="wp-revenue-coupon">Use coupon code: <strong>' . esc_html($coupon) . '</strong></div>';
        }
        if (!empty($membership)) {
            echo '<div class="wp-revenue-membership">Join our <a href="' . esc_url($membership) . '" target="_blank">membership</a>.</div>';
        }
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'type' => 'all'
        ), $atts, 'wp_revenue_booster');

        $output = '';
        if ($atts['type'] == 'all' || $atts['type'] == 'adsense') {
            $adsense = get_option('wp_revenue_booster_adsense', '');
            if (!empty($adsense)) {
                $output .= '<div class="wp-revenue-adsense">' . $adsense . '</div>';
            }
        }
        if ($atts['type'] == 'all' || $atts['type'] == 'affiliate') {
            $affiliate = get_option('wp_revenue_booster_affiliate', '');
            if (!empty($affiliate)) {
                $output .= '<div class="wp-revenue-affiliate">Check out our <a href="' . esc_url($affiliate) . '" target="_blank">affiliate link</a>.</div>';
            }
        }
        if ($atts['type'] == 'all' || $atts['type'] == 'coupon') {
            $coupon = get_option('wp_revenue_booster_coupon', '');
            if (!empty($coupon)) {
                $output .= '<div class="wp-revenue-coupon">Use coupon code: <strong>' . esc_html($coupon) . '</strong></div>';
            }
        }
        if ($atts['type'] == 'all' || $atts['type'] == 'membership') {
            $membership = get_option('wp_revenue_booster_membership', '');
            if (!empty($membership)) {
                $output .= '<div class="wp-revenue-membership">Join our <a href="' . esc_url($membership) . '" target="_blank">membership</a>.</div>';
            }
        }
        return $output;
    }
}

new WP_Revenue_Booster();
