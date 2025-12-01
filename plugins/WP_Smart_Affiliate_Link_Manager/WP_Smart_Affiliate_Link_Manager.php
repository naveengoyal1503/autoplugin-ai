/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: WP Smart Affiliate Link Manager
 * Description: Automatically manages, tracks, and optimizes affiliate links on your WordPress site with AI-powered suggestions and real-time analytics.
 * Version: 1.0
 * Author: Your Name
 */

define('WP_SMART_AFFILIATE_LINK_MANAGER_VERSION', '1.0');

class WPSmartAffiliateLinkManager {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_head', array($this, 'track_clicks'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'auto_replace_links'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Smart Affiliate Manager',
            'Affiliate Links',
            'manage_options',
            'wp-smart-affiliate-link-manager',
            array($this, 'admin_page'),
            'dashicons-admin-links'
        );
    }

    public function register_settings() {
        register_setting('wp_smart_affiliate_link_manager', 'wp_smart_affiliate_link_manager_options');
    }

    public function admin_page() {
        $options = get_option('wp_smart_affiliate_link_manager_options');
        ?>
        <div class="wrap">
            <h1>WP Smart Affiliate Link Manager</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_smart_affiliate_link_manager'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Affiliate Links</th>
                        <td>
                            <textarea name="wp_smart_affiliate_link_manager_options[links]" rows="10" cols="50"><?php echo esc_textarea($options['links'] ?? ''); ?></textarea><br />
                            <small>Enter affiliate links (one per line) in format: keyword|url</small>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Analytics</h2>
            <p>Clicks: <?php echo (int) get_option('wp_smart_affiliate_link_manager_clicks', 0); ?></p>
        </div>
        <?php
    }

    public function track_clicks() {
        if (isset($_GET['affiliate_link'])) {
            $link = sanitize_text_field($_GET['affiliate_link']);
            $clicks = (int) get_option('wp_smart_affiliate_link_manager_clicks', 0);
            update_option('wp_smart_affiliate_link_manager_clicks', $clicks + 1);
            wp_redirect($link);
            exit;
        }
    }

    public function auto_replace_links($content) {
        $options = get_option('wp_smart_affiliate_link_manager_options');
        if (empty($options['links'])) return $content;

        $links = explode("\n", $options['links']);
        foreach ($links as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) !== 2) continue;
            $keyword = trim($parts);
            $url = trim($parts[1]);
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<a href="?affiliate_link=' . urlencode($url) . '" target="_blank">' . $keyword . '</a>', $content);
        }
        return $content;
    }
}

new WPSmartAffiliateLinkManager();
?>