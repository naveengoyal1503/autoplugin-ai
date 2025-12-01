/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automatically optimizes ad, affiliate, and membership revenue by analyzing visitor behavior.
 * Version: 1.0
 * Author: Cozmo Labs
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_elements'));
        add_action('wp_ajax_wprb_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_nopriv_wprb_save_settings', array($this, 'save_settings'));
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        $settings = get_option('wprb_settings', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="javascript:void(0);" id="wprb-settings-form">
                <table class="form-table">
                    <tr>
                        <th><label>Ad Code</label></th>
                        <td><textarea name="ad_code" rows="5" cols="50"><?php echo esc_textarea($settings['ad_code'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Affiliate Link</label></th>
                        <td><input type="text" name="affiliate_link" value="<?php echo esc_attr($settings['affiliate_link'] ?? ''); ?>" size="50" /></td>
                    </tr>
                    <tr>
                        <th><label>Membership CTA</label></th>
                        <td><input type="text" name="membership_cta" value="<?php echo esc_attr($settings['membership_cta'] ?? ''); ?>" size="50" /></td>
                    </tr>
                </table>
                <button type="submit" class="button button-primary">Save Settings</button>
            </form>
            <div id="wprb-message"></div>
        </div>
        <script>
            jQuery('#wprb-settings-form').on('submit', function() {
                jQuery.post(ajaxurl, {
                    action: 'wprb_save_settings',
                    ad_code: jQuery('[name=ad_code]').val(),
                    affiliate_link: jQuery('[name=affiliate_link]').val(),
                    membership_cta: jQuery('[name=membership_cta]').val(),
                    security: '<?php echo wp_create_nonce('wprb_save_settings'); ?>'
                }, function(response) {
                    jQuery('#wprb-message').html('<p>Settings saved!</p>');
                });
            });
        </script>
        <?php
    }

    public function save_settings() {
        check_ajax_referer('wprb_save_settings', 'security');
        $settings = array(
            'ad_code' => sanitize_textarea_field($_POST['ad_code']),
            'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
            'membership_cta' => sanitize_text_field($_POST['membership_cta'])
        );
        update_option('wprb_settings', $settings);
        wp_die('Settings saved.');
    }

    public function inject_monetization_elements() {
        $settings = get_option('wprb_settings', array());
        if (empty($settings)) return;

        // Simple logic: show ad if user is new, affiliate if returning, CTA if engaged
        $is_returning = isset($_COOKIE['wprb_returning']) ? true : false;
        setcookie('wprb_returning', 1, time() + 3600 * 24 * 30, '/');

        if (!$is_returning && !empty($settings['ad_code'])) {
            echo '<div class="wprb-ad">' . $settings['ad_code'] . '</div>';
        } elseif (!empty($settings['affiliate_link'])) {
            echo '<div class="wprb-affiliate"><a href="' . $settings['affiliate_link'] . '" target="_blank">Check out this deal!</a></div>';
        } elseif (!empty($settings['membership_cta'])) {
            echo '<div class="wprb-membership"><a href="#">' . $settings['membership_cta'] . '</a></div>';
        }
    }
}

new WP_Revenue_Booster();
?>