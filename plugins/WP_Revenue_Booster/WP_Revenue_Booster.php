/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automatically optimizes ad, affiliate, and coupon placements for maximum revenue on WordPress sites.
 * Version: 1.0
 * Author: RevenueBoost
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('the_content', array($this, 'inject_optimized_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_styles() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function inject_optimized_content($content) {
        if (!is_admin() && is_single()) {
            $ad_placement = get_option('wp_revenue_booster_ad_placement', 'bottom');
            $affiliate_placement = get_option('wp_revenue_booster_affiliate_placement', 'middle');
            $coupon_placement = get_option('wp_revenue_booster_coupon_placement', 'top');

            $ad_code = get_option('wp_revenue_booster_ad_code', '');
            $affiliate_link = get_option('wp_revenue_booster_affiliate_link', '');
            $coupon_code = get_option('wp_revenue_booster_coupon_code', '');

            $optimized_content = '';

            if ($coupon_placement === 'top') {
                $optimized_content .= '<div class="wp-revenue-booster-coupon">' . esc_html($coupon_code) . '</div>';
            }

            if ($affiliate_placement === 'top') {
                $optimized_content .= '<div class="wp-revenue-booster-affiliate">' . esc_url($affiliate_link) . '</div>';
            }

            $optimized_content .= $content;

            if ($ad_placement === 'middle') {
                $optimized_content .= '<div class="wp-revenue-booster-ad">' . $ad_code . '</div>';
            }

            if ($affiliate_placement === 'middle') {
                $optimized_content .= '<div class="wp-revenue-booster-affiliate">' . esc_url($affiliate_link) . '</div>';
            }

            if ($ad_placement === 'bottom') {
                $optimized_content .= '<div class="wp-revenue-booster-ad">' . $ad_code . '</div>';
            }

            if ($affiliate_placement === 'bottom') {
                $optimized_content .= '<div class="wp-revenue-booster-affiliate">' . esc_url($affiliate_link) . '</div>';
            }

            $content = $optimized_content;
        }
        return $content;
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
        register_setting('wp_revenue_booster', 'wp_revenue_booster_ad_placement');
        register_setting('wp_revenue_booster', 'wp_revenue_booster_affiliate_placement');
        register_setting('wp_revenue_booster', 'wp_revenue_booster_coupon_placement');
        register_setting('wp_revenue_booster', 'wp_revenue_booster_ad_code');
        register_setting('wp_revenue_booster', 'wp_revenue_booster_affiliate_link');
        register_setting('wp_revenue_booster', 'wp_revenue_booster_coupon_code');
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_revenue_booster');
                do_settings_sections('wp_revenue_booster');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Ad Placement</th>
                        <td>
                            <select name="wp_revenue_booster_ad_placement">
                                <option value="top" <?php selected(get_option('wp_revenue_booster_ad_placement'), 'top'); ?>>Top</option>
                                <option value="middle" <?php selected(get_option('wp_revenue_booster_ad_placement'), 'middle'); ?>>Middle</option>
                                <option value="bottom" <?php selected(get_option('wp_revenue_booster_ad_placement'), 'bottom'); ?>>Bottom</option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Affiliate Placement</th>
                        <td>
                            <select name="wp_revenue_booster_affiliate_placement">
                                <option value="top" <?php selected(get_option('wp_revenue_booster_affiliate_placement'), 'top'); ?>>Top</option>
                                <option value="middle" <?php selected(get_option('wp_revenue_booster_affiliate_placement'), 'middle'); ?>>Middle</option>
                                <option value="bottom" <?php selected(get_option('wp_revenue_booster_affiliate_placement'), 'bottom'); ?>>Bottom</option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Coupon Placement</th>
                        <td>
                            <select name="wp_revenue_booster_coupon_placement">
                                <option value="top" <?php selected(get_option('wp_revenue_booster_coupon_placement'), 'top'); ?>>Top</option>
                                <option value="middle" <?php selected(get_option('wp_revenue_booster_coupon_placement'), 'middle'); ?>>Middle</option>
                                <option value="bottom" <?php selected(get_option('wp_revenue_booster_coupon_placement'), 'bottom'); ?>>Bottom</option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Ad Code</th>
                        <td><textarea name="wp_revenue_booster_ad_code" rows="4" cols="50"><?php echo esc_textarea(get_option('wp_revenue_booster_ad_code')); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Affiliate Link</th>
                        <td><input type="text" name="wp_revenue_booster_affiliate_link" value="<?php echo esc_url(get_option('wp_revenue_booster_affiliate_link')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Coupon Code</th>
                        <td><input type="text" name="wp_revenue_booster_coupon_code" value="<?php echo esc_attr(get_option('wp_revenue_booster_coupon_code')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new WP_Revenue_Booster();
?>