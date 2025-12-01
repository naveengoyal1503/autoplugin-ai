/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automatically optimizes ad placements, affiliate links, and premium content access to maximize revenue.
 * Version: 1.0
 * Author: WP Revenue Team
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_head', array($this, 'inject_ad_code'));
        add_action('the_content', array($this, 'inject_affiliate_links'));
        add_action('template_redirect', array($this, 'handle_premium_content'));
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
        if (isset($_POST['save_settings'])) {
            update_option('wp_revenue_booster_ad_code', sanitize_textarea_field($_POST['ad_code']));
            update_option('wp_revenue_booster_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            update_option('wp_revenue_booster_premium_content', sanitize_textarea_field($_POST['premium_content']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $ad_code = get_option('wp_revenue_booster_ad_code', '');
        $affiliate_links = get_option('wp_revenue_booster_affiliate_links', '');
        $premium_content = get_option('wp_revenue_booster_premium_content', '');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Ad Code</th>
                        <td><textarea name="ad_code" rows="5" cols="50"><?php echo esc_textarea($ad_code); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (one per line: keyword|url)</th>
                        <td><textarea name="affiliate_links" rows="5" cols="50"><?php echo esc_textarea($affiliate_links); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Premium Content (comma-separated post IDs)</th>
                        <td><input type="text" name="premium_content" value="<?php echo esc_attr($premium_content); ?>" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="save_settings" class="button-primary" value="Save Settings" />
                </p>
            </form>
        </div>
        <?php
    }

    public function inject_ad_code() {
        $ad_code = get_option('wp_revenue_booster_ad_code', '');
        if (!empty($ad_code)) {
            echo $ad_code;
        }
    }

    public function inject_affiliate_links($content) {
        $affiliate_links = get_option('wp_revenue_booster_affiliate_links', '');
        if (empty($affiliate_links)) return $content;

        $lines = explode("\n", $affiliate_links);
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) == 2) {
                $keyword = trim($parts);
                $url = trim($parts[1]);
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<a href="' . esc_url($url) . '" target="_blank">' . $keyword . '</a>', $content, 1);
            }
        }
        return $content;
    }

    public function handle_premium_content() {
        if (is_single()) {
            $premium_content = get_option('wp_revenue_booster_premium_content', '');
            $post_id = get_the_ID();
            $premium_ids = array_map('trim', explode(',', $premium_content));
            if (in_array($post_id, $premium_ids) && !current_user_can('edit_posts')) {
                wp_die('This content is premium. Please <a href="/wp-login.php">log in</a> or <a href="/subscribe">subscribe</a> to access.');
            }
        }
    }
}

new WP_Revenue_Booster();
