<?php
/*
Plugin Name: WP Revenue Booster
Description: Boost your WordPress site's revenue with automated coupon distribution, affiliate link tracking, and sponsored content management.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('revenue_booster_coupons', array($this, 'coupons_shortcode'));
        add_shortcode('revenue_booster_affiliate', array($this, 'affiliate_shortcode'));
        add_shortcode('revenue_booster_sponsored', array($this, 'sponsored_shortcode'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-bar',
            6
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <p>Manage coupons, affiliate links, and sponsored content from this dashboard.</p>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_booster_options'); ?>
                <?php do_settings_sections('wp-revenue-booster'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Coupon Code</th>
                        <td><input type="text" name="coupon_code" value="<?php echo esc_attr(get_option('coupon_code')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Affiliate Link</th>
                        <td><input type="url" name="affiliate_link" value="<?php echo esc_attr(get_option('affiliate_link')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Sponsored Content</th>
                        <td><textarea name="sponsored_content" rows="5" cols="50"><?php echo esc_textarea(get_option('sponsored_content')); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function coupons_shortcode($atts) {
        $coupon = get_option('coupon_code', 'SAVE10');
        return '<div class="revenue-booster-coupon">Use coupon code: <strong>' . $coupon . '</strong> at checkout!</div>';
    }

    public function affiliate_shortcode($atts) {
        $link = get_option('affiliate_link', '#');
        return '<div class="revenue-booster-affiliate"><a href="' . $link . '" target="_blank">Click here for special offers</a></div>';
    }

    public function sponsored_shortcode($atts) {
        $content = get_option('sponsored_content', 'Sponsored content goes here.');
        return '<div class="revenue-booster-sponsored">' . $content . '</div>';
    }
}

new WP_Revenue_Booster();
