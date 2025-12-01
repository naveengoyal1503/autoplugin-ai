<?php
/*
Plugin Name: WP Revenue Booster
Description: Automate ad placement, affiliate link optimization, and content monetization for WordPress.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'insert_ad_code'));
        add_filter('the_content', array($this, 'optimize_affiliate_links'));
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
            return;
        }
        if (isset($_POST['save_settings'])) {
            update_option('wp_revenue_booster_ad_code', sanitize_textarea_field($_POST['ad_code']));
            update_option('wp_revenue_booster_affiliate_links', sanitize_text_field($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $ad_code = get_option('wp_revenue_booster_ad_code', '');
        $affiliate_links = get_option('wp_revenue_booster_affiliate_links', '');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="ad_code">Ad Code</label></th>
                        <td><textarea name="ad_code" id="ad_code" rows="5" cols="50"><?php echo esc_textarea($ad_code); ?></textarea><br><small>Paste your ad network code (e.g., AdSense).</small></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_links">Affiliate Link (comma-separated)</label></th>
                        <td><input type="text" name="affiliate_links" id="affiliate_links" value="<?php echo esc_attr($affiliate_links); ?>" size="50" /><br><small>Enter affiliate links to automatically optimize in content.</small></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="save_settings" class="button-primary" value="Save Settings" />
                </p>
            </form>
        </div>
        <?php
    }

    public function insert_ad_code() {
        $ad_code = get_option('wp_revenue_booster_ad_code', '');
        if (!empty($ad_code)) {
            echo $ad_code;
        }
    }

    public function optimize_affiliate_links($content) {
        $affiliate_links = get_option('wp_revenue_booster_affiliate_links', '');
        if (empty($affiliate_links)) return $content;
        $links = explode(',', $affiliate_links);
        foreach ($links as $link) {
            $link = trim($link);
            if (!empty($link)) {
                $content = preg_replace('/\b(' . preg_quote(parse_url($link, PHP_URL_HOST), '/') . ')\b/i', '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow">$1</a>', $content);
            }
        }
        return $content;
    }
}

new WP_Revenue_Booster();
?>