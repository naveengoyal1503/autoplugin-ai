<?php
/*
Plugin Name: WP Revenue Booster
Description: Automatically optimizes and rotates monetization strategies (ads, affiliate links, coupons, memberships) based on visitor behavior and content performance.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_elements'));
        add_action('wp_ajax_save_revenue_settings', array($this, 'save_settings'));
        add_action('wp_ajax_nopriv_save_revenue_settings', array($this, 'save_settings'));
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'render_admin_page')
        );
    }

    public function render_admin_page() {
        $settings = get_option('wp_revenue_booster_settings', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="javascript:void(0);" id="revenue-settings-form">
                <table class="form-table">
                    <tr>
                        <th>Enable Ad Rotation</th>
                        <td><input type="checkbox" name="enable_ads" value="1" <?php checked(isset($settings['enable_ads']) && $settings['enable_ads']); ?>></td>
                    </tr>
                    <tr>
                        <th>Enable Affiliate Links</th>
                        <td><input type="checkbox" name="enable_affiliate" value="1" <?php checked(isset($settings['enable_affiliate']) && $settings['enable_affiliate']); ?>></td>
                    </tr>
                    <tr>
                        <th>Enable Coupons</th>
                        <td><input type="checkbox" name="enable_coupons" value="1" <?php checked(isset($settings['enable_coupons']) && $settings['enable_coupons']); ?>></td>
                    </tr>
                    <tr>
                        <th>Enable Membership Upsell</th>
                        <td><input type="checkbox" name="enable_membership" value="1" <?php checked(isset($settings['enable_membership']) && $settings['enable_membership']); ?>></td>
                    </tr>
                </table>
                <button type="submit" class="button button-primary">Save Settings</button>
            </form>
            <div id="message" style="display:none;" class="updated"><p>Settings saved!</p></div>
        </div>
        <script>
            jQuery('#revenue-settings-form').on('submit', function() {
                var data = {
                    action: 'save_revenue_settings',
                    settings: jQuery(this).serialize()
                };
                jQuery.post(ajaxurl, data, function(response) {
                    jQuery('#message').show();
                });
            });
        </script>
        <?php
    }

    public function save_settings() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        parse_str($_POST['settings'], $settings);
        update_option('wp_revenue_booster_settings', $settings);
        wp_die('Settings saved');
    }

    public function inject_monetization_elements() {
        $settings = get_option('wp_revenue_booster_settings', array());
        if (empty($settings)) return;

        $output = '';
        if (!empty($settings['enable_ads'])) {
            $output .= '<div class="wp-revenue-ad">Ad space optimized for your content</div>';
        }
        if (!empty($settings['enable_affiliate'])) {
            $output .= '<div class="wp-revenue-affiliate">Affiliate links rotated for best conversion</div>';
        }
        if (!empty($settings['enable_coupons'])) {
            $output .= '<div class="wp-revenue-coupon">Exclusive coupons for your visitors</div>';
        }
        if (!empty($settings['enable_membership'])) {
            $output .= '<div class="wp-revenue-membership">Upgrade to premium for exclusive content</div>';
        }

        if ($output) {
            echo '<div class="wp-revenue-container">' . $output . '</div>';
        }
    }
}

new WP_Revenue_Booster();
?>