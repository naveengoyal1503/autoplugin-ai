<?php
/*
Plugin Name: AffiliateLink Booster
Plugin URI: https://example.com/affiliate-link-booster
Description: Automatically optimize and cloak affiliate links with enhanced tracking and personalized recommendations to boost your affiliate income.
Version: 1.0
Author: YourName
Author URI: https://example.com
License: GPL2
*/

if (!defined('ABSPATH')) exit;

class AffiliateLinkBooster {

    public function __construct() {
        add_action('init', [$this, 'process_affiliate_links']);
        add_filter('the_content', [$this, 'replace_affiliate_links']);
        add_action('admin_menu', [$this, 'plugin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    // Process clicks on cloaked links
    public function process_affiliate_links() {
        if (isset($_GET['alb_ref'])) {
            $affiliate_url = esc_url_raw(base64_decode($_GET['alb_ref']));
            if ($affiliate_url) {
                // Track click
                $this->track_click($affiliate_url);
                // Redirect
                wp_redirect($affiliate_url);
                exit;
            }
        }
    }

    // Track clicks stored in option transient
    private function track_click($url) {
        $clicks = get_option('alb_clicks', []);
        if (!isset($clicks[$url])) {
            $clicks[$url] = 0;
        }
        $clicks[$url]++;
        update_option('alb_clicks', $clicks);
    }

    // Replace affiliate links in content with cloaked affiliate links
    public function replace_affiliate_links($content) {
        $pattern = '/https?:\/\/(www\.)?([\w\-]+)\.(com|net|org|io|co)([\/\w\-\?\=\&\%\.\#]*)/i';
        preg_match_all($pattern, $content, $matches);
        if (!empty($matches)) {
            foreach ($matches as $original_url) {
                if ($this->is_affiliate_link($original_url)) {
                    $cloaked_url = $this->generate_cloaked_link($original_url);
                    $content = str_replace($original_url, esc_url($cloaked_url), $content);
                }
            }
        }
        return $content;
    }

    // Basic heuristic to identify affiliate URLs - domains listed in settings
    private function is_affiliate_link($url) {
        $affiliate_domains = get_option('alb_affiliate_domains', ['amazon.com', 'clickbank.net', 'shareasale.com']);
        foreach ($affiliate_domains as $domain) {
            if (strpos($url, $domain) !== false) {
                return true;
            }
        }
        return false;
    }

    // Generate cloaked redirect URL
    private function generate_cloaked_link($url) {
        return home_url('/?alb_ref=' . base64_encode($url));
    }

    // Admin menu
    public function plugin_menu() {
        add_options_page('AffiliateLink Booster', 'AffiliateLink Booster', 'manage_options', 'affiliate-link-booster', [$this, 'settings_page']);
    }

    // Register settings
    public function register_settings() {
        register_setting('alb_settings_group', 'alb_affiliate_domains');
    }

    // Settings page
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AffiliateLink Booster Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('alb_settings_group'); ?>
                <?php do_settings_sections('alb_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Affiliate Domains (comma separated)</th>
                        <td><input type="text" name="alb_affiliate_domains" value="<?php echo esc_attr(implode(",", get_option('alb_affiliate_domains', ['amazon.com','clickbank.net','shareasale.com']))); ?>" size="50" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Click Statistics</h2>
            <?php
            $clicks = get_option('alb_clicks', []);
            if ($clicks) {
                echo '<table class="widefat"><thead><tr><th>Affiliate URL</th><th>Clicks</th></tr></thead><tbody>';
                foreach ($clicks as $url => $count) {
                    echo '<tr><td>' . esc_html($url) . '</td><td>' . intval($count) . '</td></tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>No clicks tracked yet.</p>';
            }
            ?>
        </div>
        <?php
    }
}

new AffiliateLinkBooster();
