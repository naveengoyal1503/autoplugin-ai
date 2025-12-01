/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Maximize revenue by rotating and optimizing monetization methods based on user behavior.
 * Version: 1.0
 * Author: WP Revenue Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_footer', array($this, 'inject_monetization_content'));
    }

    public function init() {
        // Register settings
        register_setting('wp_revenue_booster', 'wp_revenue_booster_settings');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster', plugins_url('js/script.js', __FILE__), array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-booster', 'wp_revenue_booster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_booster_nonce')
        ));
    }

    public function admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        $settings = get_option('wp_revenue_booster_settings', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_booster'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Ad Rotation</th>
                        <td><input name="wp_revenue_booster_settings[enable_ads]" type="checkbox" value="1" <?php checked(isset($settings['enable_ads']) ? $settings['enable_ads'] : 0, 1); ?> /></td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Affiliate Rotation</th>
                        <td><input name="wp_revenue_booster_settings[enable_affiliate]" type="checkbox" value="1" <?php checked(isset($settings['enable_affiliate']) ? $settings['enable_affiliate'] : 0, 1); ?> /></td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Premium Content</th>
                        <td><input name="wp_revenue_booster_settings[enable_premium]" type="checkbox" value="1" <?php checked(isset($settings['enable_premium']) ? $settings['enable_premium'] : 0, 1); ?> /></td>
                    </tr>
                    <tr>
                        <th scope="row">Ad Code</th>
                        <td><textarea name="wp_revenue_booster_settings[ad_code]" rows="5" cols="50"><?php echo esc_textarea(isset($settings['ad_code']) ? $settings['ad_code'] : ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">Affiliate Link</th>
                        <td><input name="wp_revenue_booster_settings[affiliate_link]" type="text" value="<?php echo esc_attr(isset($settings['affiliate_link']) ? $settings['affiliate_link'] : ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Premium Content Message</th>
                        <td><input name="wp_revenue_booster_settings[premium_message]" type="text" value="<?php echo esc_attr(isset($settings['premium_message']) ? $settings['premium_message'] : ''); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function inject_monetization_content() {
        $settings = get_option('wp_revenue_booster_settings', array());
        if (empty($settings)) return;

        $method = $this->get_random_method($settings);
        switch ($method) {
            case 'ads':
                if (!empty($settings['ad_code'])) {
                    echo '<div class="wp-revenue-booster-ad">' . $settings['ad_code'] . '</div>';
                }
                break;
            case 'affiliate':
                if (!empty($settings['affiliate_link'])) {
                    echo '<div class="wp-revenue-booster-affiliate"><a href="' . esc_url($settings['affiliate_link']) . '" target="_blank">Check this out!</a></div>';
                }
                break;
            case 'premium':
                if (!empty($settings['premium_message'])) {
                    echo '<div class="wp-revenue-booster-premium">' . esc_html($settings['premium_message']) . '</div>';
                }
                break;
        }
    }

    private function get_random_method($settings) {
        $methods = array();
        if (!empty($settings['enable_ads'])) $methods[] = 'ads';
        if (!empty($settings['enable_affiliate'])) $methods[] = 'affiliate';
        if (!empty($settings['enable_premium'])) $methods[] = 'premium';

        if (empty($methods)) return false;
        return $methods[array_rand($methods)];
    }
}

new WP_Revenue_Booster();
