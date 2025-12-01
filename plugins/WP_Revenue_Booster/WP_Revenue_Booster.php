/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automates affiliate links, ad placements, and premium content gating for maximum revenue.
 * Version: 1.0
 * Author: WP Revenue Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_head', array($this, 'inject_ad_code'));
        add_action('the_content', array($this, 'gate_premium_content'));
        add_action('the_content', array($this, 'inject_affiliate_links'));
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
            update_option('wp_revenue_booster_affiliate_links', sanitize_text_field($_POST['affiliate_links']));
            update_option('wp_revenue_booster_premium_keyword', sanitize_text_field($_POST['premium_keyword']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $ad_code = get_option('wp_revenue_booster_ad_code', '');
        $affiliate_links = get_option('wp_revenue_booster_affiliate_links', '');
        $premium_keyword = get_option('wp_revenue_booster_premium_keyword', 'premium');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="ad_code">Ad Code (HTML)</label></th>
                        <td><textarea name="ad_code" id="ad_code" rows="5" cols="50"><?php echo esc_textarea($ad_code); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_links">Affiliate Links (comma-separated)</label></th>
                        <td><input type="text" name="affiliate_links" id="affiliate_links" value="<?php echo esc_attr($affiliate_links); ?>" size="50" /></td>
                    </tr>
                    <tr>
                        <th><label for="premium_keyword">Premium Content Keyword</label></th>
                        <td><input type="text" name="premium_keyword" id="premium_keyword" value="<?php echo esc_attr($premium_keyword); ?>" /></td>
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
        if (!empty($affiliate_links)) {
            $links = explode(',', $affiliate_links);
            foreach ($links as $link) {
                $link = trim($link);
                if (!empty($link)) {
                    $content = str_replace('href="' . $link . '"', 'href="' . $link . '?ref=wp-revenue-booster"', $content);
                }
            }
        }
        return $content;
    }

    public function gate_premium_content($content) {
        $premium_keyword = get_option('wp_revenue_booster_premium_keyword', 'premium');
        if (strpos($content, '[' . $premium_keyword . ']') !== false && !current_user_can('manage_options')) {
            $content = str_replace('[' . $premium_keyword . ']', '<div class="premium-content">This content is premium. <a href="/subscribe">Subscribe to unlock.</a></div>', $content);
        }
        return $content;
    }
}

new WP_Revenue_Booster();
?>