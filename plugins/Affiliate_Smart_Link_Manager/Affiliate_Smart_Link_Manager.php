<?php
/*
Plugin Name: Affiliate Smart Link Manager
Plugin URI: https://example.com/affiliate-smart-link-manager
Description: Automatically detects, manages, and cloaks affiliate links to improve affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Smart_Link_Manager.php
License: GPLv2 or later
Text Domain: affiliate-smart-link-manager
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateSmartLinkManager {

    private $option_name = 'aslm_affiliate_links';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'auto_cloak_links'));

        // Redirect affiliate links
        add_action('template_redirect', array($this, 'redirect_affiliate_link'));
    }

    public function admin_menu() {
        add_menu_page('Affiliate Links', 'Affiliate Links', 'manage_options', 'affiliate-smart-link-manager', array($this, 'settings_page'), 'dashicons-admin-links');
    }

    public function register_settings() {
        register_setting('aslm_settings_group', $this->option_name);
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) wp_die(__('You do not have sufficient permissions to access this page.'));

        $affiliate_links = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>Affiliate Smart Link Manager</h1>
            <form method="post" action="options.php">
                <?php settings_fields('aslm_settings_group'); ?>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Affiliate URL</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($affiliate_links as $keyword => $url): ?>
                        <tr>
                            <td><input type="text" name="<?php echo $this->option_name; ?>[keyword][]" value="<?php echo esc_attr($keyword); ?>" required></td>
                            <td><input type="url" name="<?php echo $this->option_name; ?>[url][]" value="<?php echo esc_url($url); ?>" required></td>
                            <td><input type="checkbox" name="<?php echo $this->option_name; ?>[delete][]" value="<?php echo esc_attr($keyword); ?>"></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><input type="text" name="<?php echo $this->option_name; ?>[keyword][]" placeholder="Add new keyword"></td>
                            <td><input type="url" name="<?php echo $this->option_name; ?>[url][]" placeholder="Add new affiliate URL"></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button('Save Affiliate Links'); ?>
            </form>
            <h2>How it works</h2>
            <p>When visitors read your posts, any keywords in your content matching configured keywords are automatically linked to your affiliate URL, cloaked behind your domain.</p>
            <h2>Short link usage</h2>
            <p>You can also create short links by adding <code>?aslm=keyword</code> in your URLs to redirect visitors to the affiliate URL.</p>
        </div>
        <?php
    }

    public function auto_cloak_links($content) {
        $affiliate_links = get_option($this->option_name, array());

        if (empty($affiliate_links)) return $content;

        // Clean up deleted entries and re-map
        $clean_links = array();
        if (isset($affiliate_links['keyword']) && isset($affiliate_links['url'])) {
            for ($i = 0; $i < count($affiliate_links['keyword']); $i++) {
                $keyword = sanitize_text_field($affiliate_links['keyword'][$i]);
                $url = esc_url_raw($affiliate_links['url'][$i]);
                if (!empty($keyword) && !empty($url)) {
                    $clean_links[$keyword] = $url;
                }
            }
            update_option($this->option_name, $clean_links);
        } else {
            $clean_links = $affiliate_links;
        }

        // Avoid replacing inside existing links
        foreach ($clean_links as $keyword => $url) {
            $pattern = '/\b(' . preg_quote($keyword, '/') . ')\b(?![^<]*>)/i';
            $replacement = '<a href="' . esc_url(add_query_arg('aslm', urlencode($keyword), home_url('/'))) . '" rel="nofollow noopener" target="_blank">$1</a>';
            // Only replace first occurrence in content for better UX
            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    public function redirect_affiliate_link() {
        if (isset($_GET['aslm'])) {
            $keyword = sanitize_text_field($_GET['aslm']);
            $affiliate_links = get_option($this->option_name, array());
            if (isset($affiliate_links[$keyword])) {
                wp_redirect(esc_url_raw($affiliate_links[$keyword]), 302);
                exit;
            }
        }
    }

}

new AffiliateSmartLinkManager();
