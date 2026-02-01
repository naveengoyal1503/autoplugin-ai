/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Automatically cloaks and tracks affiliate links in posts, pages, and widgets to boost conversions and provide analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCloaker {
    private $db_version = '1.0';
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'affiliate_links';
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'cloak_links'));
        add_filter('widget_text', array($this, 'cloak_links'));
        add_shortcode('afflink', array($this, 'afflink_shortcode'));
        add_action('wp_ajax_sal_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sal_track_click', array($this, 'track_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            original_url text NOT NULL,
            cloaked_slug varchar(100) NOT NULL,
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (cloaked_slug)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option('sal_db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function init() {
        if (isset($_GET['sal_stats'])) {
            $this->show_stats();
            exit;
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-js', plugin_dir_url(__FILE__) . 'sal.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sal-js', 'sal_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function cloak_links($content) {
        if (is_feed() || is_preview()) return $content;
        preg_match_all('/https?:\/\/[^\s"\']+?(?:\?[^\s"\']+)?/i', $content, $matches);
        foreach ($matches as $url) {
            if ($this->is_affiliate_link($url)) {
                $cloaked = $this->get_or_create_cloaked($url);
                $content = str_replace($url, $cloaked, $content);
            }
        }
        return $content;
    }

    private function is_affiliate_link($url) {
        $aff_domains = apply_filters('sal_aff_domains', array('amazon.com', 'clickbank.net', 'shareasale.com'));
        foreach ($aff_domains as $domain) {
            if (strpos($url, $domain) !== false) return true;
        }
        return false;
    }

    private function get_or_create_cloaked($url) {
        global $wpdb;
        $slug = substr(md5($url), 0, 8);
        $existing = $wpdb->get_var($wpdb->prepare("SELECT cloaked_slug FROM $this->table_name WHERE original_url = %s", $url));
        if ($existing) {
            $cloaked_slug = $existing;
        } else {
            $wpdb->insert($this->table_name, array(
                'original_url' => $url,
                'cloaked_slug' => $slug
            ));
            $cloaked_slug = $slug;
        }
        return home_url('/go/' . $cloaked_slug . '/');
    }

    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(array('url' => ''), $atts);
        return $this->get_or_create_cloaked($atts['url']);
    }

    public function track_click() {
        global $wpdb;
        if (!isset($_GET['slug'])) wp_die('Invalid request');
        $slug = sanitize_text_field($_GET['slug']);
        $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE cloaked_slug = %s", $slug));
        $link = $wpdb->get_var($wpdb->prepare("SELECT original_url FROM $this->table_name WHERE cloaked_slug = %s", $slug));
        if ($link) {
            wp_redirect($link, 301);
            exit;
        }
        wp_die('Link not found');
    }

    public function show_stats() {
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY clicks DESC LIMIT 20");
        echo '<div class="wrap"><h1>Affiliate Link Stats (Free Version)</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>Slug</th><th>Clicks</th><th>Original URL</th></tr></thead><tbody>';
        foreach ($links as $link) {
            echo '<tr><td>' . esc_html($link->cloaked_slug) . '</td><td>' . intval($link->clicks) . '</td><td>' . esc_html($link->original_url) . '</td></tr>';
        }
        echo '</tbody></table><p><a href="' . home_url() . '">Back to site</a> | <strong>Upgrade to Pro for advanced analytics!</strong></p></div>';
    }
}

new SmartAffiliateCloaker();

// Add rewrite rules
add_action('init', function() {
    add_rewrite_rule('^go/([^/]+)/?', 'index.php?sal_slug=$matches[1]', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'sal_slug';
    return $vars;
});

add_action('template_redirect', function() {
    if (get_query_var('sal_slug')) {
        global $smart_affiliate_cloaker;
        $smart_affiliate_cloaker->track_click();
    }
});