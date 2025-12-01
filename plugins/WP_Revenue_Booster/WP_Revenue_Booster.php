<?php
/*
Plugin Name: WP Revenue Booster
Description: Automatically optimizes and manages multiple monetization streams for WordPress sites.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
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
            'dashicons-chart-line'
        );
    }

    public function admin_page() {
        if (isset($_POST['save_settings'])) {
            update_option('wp_revenue_booster_ads', $_POST['ads']);
            update_option('wp_revenue_booster_affiliate', $_POST['affiliate']);
            update_option('wp_revenue_booster_premium', $_POST['premium']);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $ads = get_option('wp_revenue_booster_ads', '');
        $affiliate = get_option('wp_revenue_booster_affiliate', '');
        $premium = get_option('wp_revenue_booster_premium', '');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Ad Code</th>
                        <td><textarea name="ads" rows="5" cols="50"><?php echo esc_textarea($ads); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="text" name="affiliate" value="<?php echo esc_attr($affiliate); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Premium Content Message</th>
                        <td><input type="text" name="premium" value="<?php echo esc_attr($premium); ?>" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="save_settings" class="button-primary" value="Save Settings" />
                </p>
            </form>
        </div>
        <?php
    }

    public function inject_monetization_code() {
        $ads = get_option('wp_revenue_booster_ads', '');
        $affiliate = get_option('wp_revenue_booster_affiliate', '');
        if (!empty($ads)) {
            echo $ads;
        }
        if (!empty($affiliate)) {
            echo '<p>Check out our <a href="' . esc_url($affiliate) . '" target="_blank">affiliate offer</a>.</p>';
        }
    }

    public function premium_content_shortcode($atts, $content = null) {
        $premium = get_option('wp_revenue_booster_premium', 'Upgrade to premium for exclusive content.');
        if (is_user_logged_in()) {
            return $content;
        } else {
            return '<p>' . $premium . '</p>';
        }
    }
}

new WP_Revenue_Booster();
