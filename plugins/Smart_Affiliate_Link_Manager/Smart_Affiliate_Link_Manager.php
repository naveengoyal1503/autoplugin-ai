/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate-link-manager
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with analytics and A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateLinkManager {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sal_update_stats', array($this, 'update_stats'));
        add_filter('wp_insert_post_data', array($this, 'cloak_links'), 10, 2);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        // Rewrite rules for tracking
        add_rewrite_rule('^track/([0-9]+)/?$', 'index.php?sal_track=$matches[1]', 'top');
        flush_rewrite_rules(false);

        add_rewrite_tag('%sal_track%', '([0-9]+)');
        add_filter('query_vars', function($vars) {
            $vars[] = 'sal_track';
            return $vars;
        });

        add_action('template_redirect', array($this, 'handle_track'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-frontend', plugin_dir_url(__FILE__) . 'sal-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sal-frontend', 'sal_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Links', 'Affiliate Links', 'manage_options', 'sal-manager', array($this, 'admin_page'));
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>Smart Affiliate Link Manager</h1>';
        echo '<p>Free version: Basic cloaking and stats. <a href="https://example.com/premium">Upgrade to Premium</a> for A/B testing and more.</p>';

        global $wpdb;
        $table_name = $wpdb->prefix . 'sal_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name");

        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Original URL</th><th>Clicks</th><th>Shortcode</th></tr></thead><tbody>';
        foreach ($links as $link) {
            echo '<tr><td>' . $link->id . '</td><td>' . esc_html($link->original_url) . '</td><td>' . $link->clicks . '</td><td>[sal id="' . $link->id . '"]</td></tr>';
        }
        echo '</tbody></table>';

        echo '<h2>Add New Link</h2><form method="post">';
        wp_nonce_field('sal_add_link');
        echo '<input type="url" name="sal_url" placeholder="https://affiliate-link.com" required><br><input type="submit" name="sal_add" value="Add Link" class="button-primary"></form>';
        echo '</div>';

        if (isset($_POST['sal_add']) && wp_verify_nonce($_POST['_wpnonce'], 'sal_add_link')) {
            $url = sanitize_url($_POST['sal_url']);
            $wpdb->insert($table_name, array('original_url' => $url, 'clicks' => 0));
            echo '<div class="notice notice-success"><p>Link added!</p></div>';
        }
    }

    public function cloak_links($data, $postarr) {
        if ($data['post_type'] === 'post' || $data['post_type'] === 'page') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sal_links';
            $links = $wpdb->get_results("SELECT id, original_url FROM $table_name");
            foreach ($links as $link) {
                $shortcode = '[sal id="' . $link->id . '"]';
                $data['post_content'] = str_replace($link->original_url, $shortcode, $data['post_content']);
            }
        }
        return $data;
    }

    public function handle_track() {
        if (get_query_var('sal_track')) {
            $id = intval(get_query_var('sal_track'));
            global $wpdb;
            $table_name = $wpdb->prefix . 'sal_links';
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $id));
            $link = $wpdb->get_var($wpdb->prepare("SELECT original_url FROM $table_name WHERE id = %d", $id));
            if ($link) {
                wp_redirect($link, 301);
                exit;
            }
        }
    }

    public function update_stats() {
        // AJAX for premium stats (free version logs only)
        wp_die();
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sal_links';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            original_url text NOT NULL,
            clicks bigint(20) DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivate() {
        // Cleanup optional
    }
}

// Shortcode handler
add_shortcode('sal', function($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts);
    $id = intval($atts['id']);
    global $wpdb;
    $table_name = $wpdb->prefix . 'sal_links';
    $link = $wpdb->get_var($wpdb->prepare("SELECT original_url FROM $table_name WHERE id = %d", $id));
    if ($link) {
        $track_url = home_url('/track/' . $id . '/');
        return '<a href="' . esc_url($track_url) . '" target="_blank" rel="nofollow">' . esc_html(parse_url($link, PHP_URL_HOST)) . '</a>';
    }
    return '';
});

SmartAffiliateLinkManager::get_instance();

// Premium upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock A/B testing and advanced analytics with <a href="https://example.com/premium">Smart Affiliate Pro</a> - only $49/year!</p></div>';
});