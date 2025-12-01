/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WPRevenueBoost.php
*/
<?php
/**
 * Plugin Name: WPRevenueBoost
 * Plugin URI: https://wprevenueboost.com
 * Description: Automatically optimizes ad placements, affiliate links, and content monetization for maximum revenue.
 * Version: 1.0
 * Author: WPRevenueBoost Team
 * Author URI: https://wprevenueboost.com
 * License: GPL2
 */

class WPRevenueBoost {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_code'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_options_page(
            'WPRevenueBoost Settings',
            'WPRevenueBoost',
            'manage_options',
            'wprevenueboost',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        if (isset($_POST['wprevenueboost_save'])) {
            update_option('wprevenueboost_ad_code', sanitize_textarea_field($_POST['ad_code']));
            update_option('wprevenueboost_affiliate_code', sanitize_textarea_field($_POST['affiliate_code']));
            update_option('wprevenueboost_enabled', isset($_POST['enabled']) ? 1 : 0);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $ad_code = get_option('wprevenueboost_ad_code', '');
        $affiliate_code = get_option('wprevenueboost_affiliate_code', '');
        $enabled = get_option('wprevenueboost_enabled', 1);
        ?>
        <div class="wrap">
            <h1>WPRevenueBoost Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Monetization</th>
                        <td><input type="checkbox" name="enabled" value="1" <?php checked($enabled, 1); ?> /></td>
                    </tr>
                    <tr>
                        <th scope="row">Ad Code</th>
                        <td><textarea name="ad_code" rows="5" cols="50"><?php echo esc_textarea($ad_code); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">Affiliate Code</th>
                        <td><textarea name="affiliate_code" rows="5" cols="50"><?php echo esc_textarea($affiliate_code); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="wprevenueboost_save" class="button-primary" value="Save Changes" />
                </p>
            </form>
        </div>
        <?php
    }

    public function inject_monetization_code() {
        if (!get_option('wprevenueboost_enabled', 1)) return;
        $ad_code = get_option('wprevenueboost_ad_code', '');
        $affiliate_code = get_option('wprevenueboost_affiliate_code', '');
        echo '<div class="wprevenueboost-ad">' . $ad_code . '</div>';
        echo '<div class="wprevenueboost-affiliate">' . $affiliate_code . '</div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wprevenueboost-js', plugin_dir_url(__FILE__) . 'wprevenueboost.js', array(), '1.0', true);
    }
}

new WPRevenueBoost();
?>