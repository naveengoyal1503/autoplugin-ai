/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Boost your WordPress site revenue with smart affiliate, coupon, and sponsored content placement.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'display_smart_content'));
        add_action('admin_post_save_revenue_settings', array($this, 'save_settings'));
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
        $settings = get_option('wp_revenue_booster_settings', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="admin-post.php">
                <input type="hidden" name="action" value="save_revenue_settings">
                <table class="form-table">
                    <tr>
                        <th><label>Affiliate Offers</label></th>
                        <td><textarea name="affiliate_offers" rows="5" cols="50"><?php echo esc_textarea($settings['affiliate_offers'] ?? ''); ?></textarea><br>
                        <small>One offer per line: Title|URL|Description</small></td>
                    </tr>
                    <tr>
                        <th><label>Coupons</label></th>
                        <td><textarea name="coupons" rows="5" cols="50"><?php echo esc_textarea($settings['coupons'] ?? ''); ?></textarea><br>
                        <small>One coupon per line: Code|Store|Description</small></td>
                    </tr>
                    <tr>
                        <th><label>Sponsored Content</label></th>
                        <td><textarea name="sponsored_content" rows="5" cols="50"><?php echo esc_textarea($settings['sponsored_content'] ?? ''); ?></textarea><br>
                        <small>One item per line: Title|URL|Description</small></td>
                    </tr>
                </table>
                <?php wp_nonce_field('save_revenue_settings'); ?>
                <p class="submit">
                    <input type="submit" class="button-primary" value="Save Settings">
                </p>
            </form>
        </div>
        <?php
    }

    public function save_settings() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'], 'save_revenue_settings')) {
            wp_die('Unauthorized');
        }

        $settings = array(
            'affiliate_offers' => sanitize_textarea_field($_POST['affiliate_offers']),
            'coupons' => sanitize_textarea_field($_POST['coupons']),
            'sponsored_content' => sanitize_textarea_field($_POST['sponsored_content'])
        );

        update_option('wp_revenue_booster_settings', $settings);
        wp_redirect(admin_url('options-general.php?page=wp-revenue-booster&updated=1'));
        exit;
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function display_smart_content() {
        $settings = get_option('wp_revenue_booster_settings', array());
        if (empty($settings)) return;

        $offers = $this->parse_items($settings['affiliate_offers']);
        $coupons = $this->parse_items($settings['coupons']);
        $sponsored = $this->parse_items($settings['sponsored_content']);

        $all_items = array_merge($offers, $coupons, $sponsored);
        if (empty($all_items)) return;

        // Simple random selection for demo
        $item = $all_items[array_rand($all_items)];
        ?>
        <div id="wp-revenue-booster-widget">
            <h3><?php echo esc_html($item['title']); ?></h3>
            <p><?php echo esc_html($item['description']); ?></p>
            <a href="<?php echo esc_url($item['url']); ?>" target="_blank">Learn More</a>
        </div>
        <?php
    }

    private function parse_items($text) {
        $lines = explode("\n", $text);
        $items = array();
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 3) {
                $items[] = array(
                    'title' => trim($parts),
                    'url' => trim($parts[1]),
                    'description' => trim($parts[2])
                );
            }
        }
        return $items;
    }
}

new WP_Revenue_Booster;
?>