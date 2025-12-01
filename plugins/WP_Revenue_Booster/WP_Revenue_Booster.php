<?php
/*
Plugin Name: WP Revenue Booster
Description: Boost your WordPress site's revenue with smart ad, affiliate, and sponsored content optimization.
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
        add_menu_page(
            'Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line'
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
                        <th scope="row">Ad Code</th>
                        <td><textarea name="wp_revenue_booster_ad_code" rows="5" cols="50"><?php echo esc_textarea(get_option('wp_revenue_booster_ad_code')); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Affiliate Link</th>
                        <td><input type="text" name="wp_revenue_booster_affiliate_link" value="<?php echo esc_attr(get_option('wp_revenue_booster_affiliate_link')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Sponsored Content</th>
                        <td><textarea name="wp_revenue_booster_sponsored_content" rows="5" cols="50"><?php echo esc_textarea(get_option('wp_revenue_booster_sponsored_content')); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster-js', plugin_dir_url(__FILE__) . 'revenue-booster.js', array(), '1.0', true);
    }

    public function inject_optimized_content() {
        $ad_code = get_option('wp_revenue_booster_ad_code', '');
        $affiliate_link = get_option('wp_revenue_booster_affiliate_link', '');
        $sponsored_content = get_option('wp_revenue_booster_sponsored_content', '');

        if (!empty($ad_code)) {
            echo '<div class="wp-revenue-booster-ad">' . $ad_code . '</div>';
        }
        if (!empty($affiliate_link)) {
            echo '<div class="wp-revenue-booster-affiliate"><a href="' . esc_url($affiliate_link) . '" target="_blank">Recommended Product</a></div>';
        }
        if (!empty($sponsored_content)) {
            echo '<div class="wp-revenue-booster-sponsored">' . $sponsored_content . '</div>';
        }
    }
}

function wp_revenue_booster_init() {
    new WP_Revenue_Booster();
}
add_action('plugins_loaded', 'wp_revenue_booster_init');

function wp_revenue_booster_register_settings() {
    register_setting('wp_revenue_booster_options', 'wp_revenue_booster_ad_code');
    register_setting('wp_revenue_booster_options', 'wp_revenue_booster_affiliate_link');
    register_setting('wp_revenue_booster_options', 'wp_revenue_booster_sponsored_content');
}
add_action('admin_init', 'wp_revenue_booster_register_settings');
?>