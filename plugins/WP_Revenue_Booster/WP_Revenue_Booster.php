/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Boost your WordPress site revenue with smart affiliate, coupon, and sponsored content placement.
 * Version: 1.0
 * Author: Revenue Labs
 */

define('WP_REVENUE_BOOSTER_VERSION', '1.0');
define('WP_REVENUE_BOOSTER_PLUGIN_DIR', plugin_dir_path(__FILE__));

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('revenue_booster', array($this, 'shortcode_handler'));
        add_action('wp_footer', array($this, 'inject_smart_content'));
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
        if (isset($_POST['save_revenue_booster_settings'])) {
            update_option('revenue_booster_affiliate_links', $_POST['affiliate_links']);
            update_option('revenue_booster_coupons', $_POST['coupons']);
            update_option('revenue_booster_sponsored', $_POST['sponsored']);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $affiliate_links = get_option('revenue_booster_affiliate_links', '');
        $coupons = get_option('revenue_booster_coupons', '');
        $sponsored = get_option('revenue_booster_sponsored', '');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label>Affiliate Links (JSON)</label></th>
                        <td><textarea name="affiliate_links" rows="5" cols="50"><?php echo esc_textarea($affiliate_links); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Coupons (JSON)</label></th>
                        <td><textarea name="coupons" rows="5" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Sponsored Content (JSON)</label></th>
                        <td><textarea name="sponsored" rows="5" cols="50"><?php echo esc_textarea($sponsored); ?></textarea></td>
                    </tr>
                </table>
                <input type="submit" name="save_revenue_booster_settings" class="button button-primary" value="Save Settings">
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('revenue-booster', plugins_url('assets/style.css', __FILE__));
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array('type' => 'affiliate'), $atts);
        return $this->get_smart_content($atts['type']);
    }

    public function inject_smart_content() {
        if (is_single()) {
            echo '<div class="revenue-booster-widget">';
            echo $this->get_smart_content('affiliate');
            echo $this->get_smart_content('coupon');
            echo $this->get_smart_content('sponsored');
            echo '</div>';
        }
    }

    private function get_smart_content($type) {
        $data = get_option('revenue_booster_' . $type . 's', '');
        if (empty($data)) return '';
        $items = json_decode($data, true);
        if (!is_array($items) || empty($items)) return '';
        $item = $items[array_rand($items)];
        return '<div class="revenue-booster-item"><a href="' . esc_url($item['url']) . '" target="_blank">' . esc_html($item['title']) . '</a></div>';
    }
}

new WP_Revenue_Booster();
?>