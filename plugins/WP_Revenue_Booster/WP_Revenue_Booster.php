<?php
/*
Plugin Name: WP Revenue Booster
Description: Maximize revenue by rotating affiliate links, coupons, and sponsored content.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_content'));
        add_action('wp_ajax_save_revenue_settings', array($this, 'save_settings'));
        add_action('wp_ajax_nopriv_save_revenue_settings', array($this, 'save_settings'));
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
        $settings = get_option('wp_revenue_booster_settings', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th><label>Affiliate Links (one per line)</label></th>
                        <td><textarea name="affiliate_links" rows="5" cols="50"><?php echo esc_textarea(implode("\n", $settings['affiliate_links'] ?? [])); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Coupons (one per line)</label></th>
                        <td><textarea name="coupons" rows="5" cols="50"><?php echo esc_textarea(implode("\n", $settings['coupons'] ?? [])); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Sponsored Content (HTML allowed)</label></th>
                        <td><textarea name="sponsored_content" rows="5" cols="50"><?php echo esc_textarea($settings['sponsored_content'] ?? ''); ?></textarea></td>
                    </tr>
                </table>
                <?php wp_nonce_field('save_revenue_settings'); ?>
                <input type="submit" name="submit" class="button button-primary" value="Save Settings">
            </form>
        </div>
        <?php
    }

    public function save_settings() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'], 'save_revenue_settings')) {
            wp_die('Unauthorized');
        }
        $settings = array(
            'affiliate_links' => array_filter(array_map('trim', explode("\n", $_POST['affiliate_links']))),
            'coupons' => array_filter(array_map('trim', explode("\n", $_POST['coupons']))),
            'sponsored_content' => wp_kses_post($_POST['sponsored_content'])
        );
        update_option('wp_revenue_booster_settings', $settings);
        wp_die('Settings saved.');
    }

    public function inject_content() {
        $settings = get_option('wp_revenue_booster_settings', array());
        if (empty($settings)) return;

        $content_type = array_rand(array('affiliate', 'coupon', 'sponsored'));
        $output = '';

        switch ($content_type) {
            case 'affiliate':
                if (!empty($settings['affiliate_links'])) {
                    $link = $settings['affiliate_links'][array_rand($settings['affiliate_links'])];
                    $output = '<div class="wp-revenue-booster"><a href="' . esc_url($link) . '" target="_blank">Check out this offer!</a></div>';
                }
                break;
            case 'coupon':
                if (!empty($settings['coupons'])) {
                    $coupon = $settings['coupons'][array_rand($settings['coupons'])];
                    $output = '<div class="wp-revenue-booster">Coupon: <strong>' . esc_html($coupon) . '</strong></div>';
                }
                break;
            case 'sponsored':
                if (!empty($settings['sponsored_content'])) {
                    $output = '<div class="wp-revenue-booster sponsored">' . $settings['sponsored_content'] . '</div>';
                }
                break;
        }

        if ($output) {
            echo '<div style="margin: 20px 0; padding: 10px; background: #f0f0f0; border: 1px solid #ccc; text-align: center;">' . $output . '</div>';
        }
    }
}

new WP_Revenue_Booster();
?>