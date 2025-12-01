/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Automatically optimizes and manages multiple monetization streams for WordPress sites.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_code'));
        add_action('init', array($this, 'register_shortcodes'));
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
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        if (isset($_POST['wp_revenue_booster_save'])) {
            update_option('wp_revenue_booster_adsense', sanitize_text_field($_POST['adsense_code']));
            update_option('wp_revenue_booster_affiliate', sanitize_text_field($_POST['affiliate_link']));
            update_option('wp_revenue_booster_premium', sanitize_text_field($_POST['premium_content']));
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
        $adsense = get_option('wp_revenue_booster_adsense', '');
        $affiliate = get_option('wp_revenue_booster_affiliate', '');
        $premium = get_option('wp_revenue_booster_premium', '');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>AdSense Code</th>
                        <td><textarea name="adsense_code" rows="5" cols="50"><?php echo esc_textarea($adsense); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="text" name="affiliate_link" value="<?php echo esc_attr($affiliate); ?>" size="50" /></td>
                    </tr>
                    <tr>
                        <th>Premium Content Message</th>
                        <td><input type="text" name="premium_content" value="<?php echo esc_attr($premium); ?>" size="50" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="wp_revenue_booster_save" class="button-primary" value="Save Changes" />
                </p>
            </form>
        </div>
        <?php
    }

    public function inject_monetization_code() {
        $adsense = get_option('wp_revenue_booster_adsense', '');
        $affiliate = get_option('wp_revenue_booster_affiliate', '');
        if (!empty($adsense)) {
            echo '<div class="wp-revenue-adsense">' . $adsense . '</div>';
        }
        if (!empty($affiliate)) {
            echo '<div class="wp-revenue-affiliate">Check out this <a href="' . esc_url($affiliate) . '" target="_blank">affiliate offer</a>.</div>';
        }
    }

    public function register_shortcodes() {
        add_shortcode('premium_content', array($this, 'premium_content_shortcode'));
    }

    public function premium_content_shortcode($atts, $content = null) {
        $premium_message = get_option('wp_revenue_booster_premium', 'Upgrade to premium for exclusive content.');
        if (is_user_logged_in()) {
            return '<div class="wp-revenue-premium">' . do_shortcode($content) . '</div>';
        } else {
            return '<div class="wp-revenue-premium-message">' . $premium_message . '</div>';
        }
    }
}

new WP_Revenue_Booster();
?>