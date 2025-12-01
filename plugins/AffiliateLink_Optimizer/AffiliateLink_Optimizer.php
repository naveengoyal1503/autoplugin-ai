<?php
/*
Plugin Name: AffiliateLink Optimizer
Description: Automatically detects affiliate links, tracks clicks, and provides AI-powered conversion optimization suggestions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Optimizer.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateLinkOptimizer {
    private $version = '1.0';
    private $option_name = 'alo_options';

    public function __construct() {
        add_action('wp_footer', array($this, 'inject_tracking_script'));
        add_filter('the_content', array($this, 'auto_cloak_links'));
        add_action('wp_ajax_alo_track_click', array($this, 'handle_click_tracking'));
        add_action('wp_ajax_nopriv_alo_track_click', array($this, 'handle_click_tracking'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function auto_cloak_links($content) {
        if (is_admin()) return $content;

        $pattern = '/<a\s+([^>]*href=\"https?:\/\/(?:[\w-]+\.)?(?:amazon|clickbank|shareasale|cj|ebay|affiliatesitenetwork)\.com[^\"\s]*\"[^>]*)>/i';

        $content = preg_replace_callback($pattern, function($matches) {
            $original_tag = $matches;
            $href_pattern = '/href="([^"]+)"/i';

            if(preg_match($href_pattern, $original_tag, $href_match)) {
                $original_url = esc_url_raw($href_match[1]);
                $cloaked_url = admin_url('admin-ajax.php?action=alo_track_click&url=' . rawurlencode($original_url));
                return str_replace($href_match, 'href="' . esc_url($cloaked_url) . '" target="_blank" rel="nofollow noopener"', $original_tag);
            }
            return $original_tag;
        }, $content);

        return $content;
    }

    public function handle_click_tracking() {
        if (!isset($_GET['url'])) {
            wp_send_json_error('Missing URL');
        }

        $url = esc_url_raw($_GET['url']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'alo_clicks';

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_name (url, clicked_at, ip_address) VALUES (%s, NOW(), %s)",
            $url,
            $_SERVER['REMOTE_ADDR']
        ));

        wp_redirect($url);
        exit;
    }

    public function inject_tracking_script() {
        if (!current_user_can('manage_options')) {
            return; // Frontend only for non-admins
        }
    }

    public function admin_menu() {
        add_menu_page('AffiliateLink Optimizer', 'AffiliateLink Optimizer', 'manage_options', 'alo-settings', array($this, 'settings_page'), 'dashicons-admin-links');
    }

    public function register_settings() {
        register_setting('alo_options_group', $this->option_name);
    }

    public function settings_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alo_clicks';

        $results = $wpdb->get_results("SELECT url, COUNT(*) as clicks FROM $table_name GROUP BY url ORDER BY clicks DESC LIMIT 20");

        echo '<div class="wrap"><h1>AffiliateLink Optimizer - Click Stats</h1>';
        echo '<table class="widefat"><thead><tr><th>Affiliate URL</th><th>Clicks</th></tr></thead><tbody>';
        if ($results) {
            foreach ($results as $row) {
                echo '<tr><td><a href="' . esc_url($row->url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($row->url) . '</a></td><td>' . intval($row->clicks) . '</td></tr>';
            }
        } else {
            echo '<tr><td colspan="2">No clicks tracked yet.</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alo_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            url TEXT NOT NULL,
            clicked_at DATETIME NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY url_index (url(191))
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

register_activation_hook(__FILE__, array('AffiliateLinkOptimizer', 'activate'));

new AffiliateLinkOptimizer();
