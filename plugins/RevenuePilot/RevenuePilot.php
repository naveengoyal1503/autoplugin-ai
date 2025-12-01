<?php
/*
Plugin Name: RevenuePilot
Description: Automated revenue optimization for WordPress.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=RevenuePilot.php
*/

class RevenuePilot {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_head', array($this, 'inject_tracking_code'));
        add_action('the_content', array($this, 'auto_insert_affiliate_links'));
        add_action('wp_footer', array($this, 'display_ad'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'RevenuePilot',
            'RevenuePilot',
            'manage_options',
            'revenuepilot',
            array($this, 'admin_page'),
            'dashicons-chart-line'
        );
    }

    public function admin_page() {
        if (isset($_POST['save_revenuepilot_settings'])) {
            update_option('revenuepilot_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            update_option('revenuepilot_ad_code', sanitize_textarea_field($_POST['ad_code']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $affiliate_id = get_option('revenuepilot_affiliate_id', '');
        $ad_code = get_option('revenuepilot_ad_code', '');
        ?>
        <div class="wrap">
            <h1>RevenuePilot Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="affiliate_id">Affiliate ID</label></th>
                        <td><input type="text" name="affiliate_id" id="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="ad_code">Ad Code</label></th>
                        <td><textarea name="ad_code" id="ad_code" rows="5" class="regular-text"><?php echo esc_textarea($ad_code); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="save_revenuepilot_settings" class="button-primary" value="Save Settings" />
                </p>
            </form>
        </div>
        <?php
    }

    public function inject_tracking_code() {
        echo '<script>console.log("RevenuePilot tracking active");</script>';
    }

    public function auto_insert_affiliate_links($content) {
        $affiliate_id = get_option('revenuepilot_affiliate_id', '');
        if ($affiliate_id && is_single()) {
            $content .= '<p>Check out our <a href="https://example.com/?ref=' . esc_attr($affiliate_id) . '" target="_blank">affiliate offers</a>.</p>';
        }
        return $content;
    }

    public function display_ad() {
        $ad_code = get_option('revenuepilot_ad_code', '');
        if ($ad_code) {
            echo '<div class="revenuepilot-ad">' . $ad_code . '</div>';
        }
    }
}

new RevenuePilot();
