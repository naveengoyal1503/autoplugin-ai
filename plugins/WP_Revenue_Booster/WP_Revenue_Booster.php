/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automate and optimize ads, affiliate links, coupons, and memberships.
 * Version: 1.0
 * Author: WP Revenue Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_elements'));
        add_shortcode('revenue_booster', array($this, 'shortcode_handler'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            80
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_booster_options'); ?>
                <?php do_settings_sections('wp_revenue_booster_options'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">AdSense Code</th>
                        <td><textarea name="wp_revenue_booster_adsense" rows="3" cols="50"><?php echo esc_textarea(get_option('wp_revenue_booster_adsense')); ?></textarea></td>
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

    public function inject_monetization_elements() {
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
            echo '<div class="wp-revenue-membership">Join our <a href="' . esc_url($membership) . '" target="_blank">membership</a> for exclusive content.</div>';
        }
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'type' => 'all'
        ), $atts, 'revenue_booster');

        $output = '';
        if ($atts['type'] == 'adsense' || $atts['type'] == 'all') {
            $adsense = get_option('wp_revenue_booster_adsense', '');
            if (!empty($adsense)) {
                $output .= '<div class="wp-revenue-adsense">' . $adsense . '</div>';
            }
        }
        if ($atts['type'] == 'affiliate' || $atts['type'] == 'all') {
            $affiliate = get_option('wp_revenue_booster_affiliate', '');
            if (!empty($affiliate)) {
                $output .= '<div class="wp-revenue-affiliate">Check out our <a href="' . esc_url($affiliate) . '" target="_blank">affiliate link</a>.</div>';
            }
        }
        if ($atts['type'] == 'coupon' || $atts['type'] == 'all') {
            $coupon = get_option('wp_revenue_booster_coupon', '');
            if (!empty($coupon)) {
                $output .= '<div class="wp-revenue-coupon">Use coupon code: <strong>' . esc_html($coupon) . '</strong></div>';
            }
        }
        if ($atts['type'] == 'membership' || $atts['type'] == 'all') {
            $membership = get_option('wp_revenue_booster_membership', '');
            if (!empty($membership)) {
                $output .= '<div class="wp-revenue-membership">Join our <a href="' . esc_url($membership) . '" target="_blank">membership</a> for exclusive content.</div>';
            }
        }
        return $output;
    }
}

new WP_Revenue_Booster();
?>