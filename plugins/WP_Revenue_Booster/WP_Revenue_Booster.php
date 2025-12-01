/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automatically optimizes and manages multiple monetization streams for WordPress sites.
 * Version: 1.0
 * Author: WP Revenue Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_code'));
        add_shortcode('premium_content', array($this, 'premium_content_shortcode'));
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
                        <td><textarea name="wp_revenue_booster_adsense" rows="5" cols="50"><?php echo esc_textarea(get_option('wp_revenue_booster_adsense')); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Affiliate Link</th>
                        <td><input type="text" name="wp_revenue_booster_affiliate" value="<?php echo esc_attr(get_option('wp_revenue_booster_affiliate')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Premium Content Message</th>
                        <td><input type="text" name="wp_revenue_booster_premium_msg" value="<?php echo esc_attr(get_option('wp_revenue_booster_premium_msg')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function inject_monetization_code() {
        $adsense = get_option('wp_revenue_booster_adsense');
        $affiliate = get_option('wp_revenue_booster_affiliate');
        if (!empty($adsense)) {
            echo '<div class="wp-revenue-adsense">' . $adsense . '</div>';
        }
        if (!empty($affiliate)) {
            echo '<div class="wp-revenue-affiliate"><a href="' . esc_url($affiliate) . '" target="_blank">Visit our affiliate partner</a></div>';
        }
    }

    public function premium_content_shortcode($atts, $content = null) {
        $msg = get_option('wp_revenue_booster_premium_msg', 'This content is for premium members only.');
        if (is_user_logged_in()) {
            return '<div class="wp-revenue-premium">' . $content . '</div>';
        } else {
            return '<div class="wp-revenue-premium-msg">' . $msg . '</div>';
        }
    }
}

new WP_Revenue_Booster();

// Register settings
add_action('admin_init', function() {
    register_setting('wp_revenue_booster_options', 'wp_revenue_booster_adsense');
    register_setting('wp_revenue_booster_options', 'wp_revenue_booster_affiliate');
    register_setting('wp_revenue_booster_options', 'wp_revenue_booster_premium_msg');
});
?>