/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager Pro
 * Plugin URI: https://example.com/smart-affiliate-manager
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with analytics and A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateManager {
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
        add_filter('the_content', array($this, 'cloak_links'));
        add_shortcode('sam_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Create DB table for tracking
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            original_url varchar(500) NOT NULL,
            cloaked_url varchar(500) NOT NULL,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sam-script', plugin_dir_url(__FILE__) . 'sam.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sam-style', plugin_dir_url(__FILE__) . 'sam.css', array(), '1.0.0');
    }

    public function cloak_links($content) {
        if (is_admin()) return $content;
        $pattern = '/https?:\/\/([^\s<>"\[]*?(?:aff|amazon|clickbank|commission|ref|tracking)[^\s<>"\[]*)/i';
        return preg_replace_callback($pattern, array($this, 'replace_link'), $content);
    }

    private function replace_link($matches) {
        global $wpdb;
        $original = $matches;
        $table_name = $wpdb->prefix . 'sam_links';
        $cloaked = $wpdb->get_var($wpdb->prepare("SELECT cloaked_url FROM $table_name WHERE original_url = %s", $original));
        if (!$cloaked) {
            $cloaked = site_url('/?sam=' . md5($original));
            $wpdb->insert($table_name, array('original_url' => $original, 'cloaked_url' => $cloaked));
        }
        return $cloaked;
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('url' => ''), $atts);
        if (!$atts['url']) return '';
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $cloaked = $wpdb->get_var($wpdb->prepare("SELECT cloaked_url FROM $table_name WHERE original_url = %s", $atts['url']));
        if (!$cloaked) {
            $cloaked = site_url('/?sam=' . md5($atts['url']));
            $wpdb->insert($table_name, array('original_url' => $atts['url'], 'cloaked_url' => $cloaked));
        }
        return '<a href="' . $cloaked . '" target="_blank" rel="nofollow">' . ($atts['text'] ?? 'Click Here') . '</a>';
    }

    public function admin_menu() {
        add_options_page('SAM Settings', 'SAM Pro', 'manage_options', 'sam-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY clicks DESC");
        echo '<div class="wrap"><h1>Smart Affiliate Manager - Analytics</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Original URL</th><th>Cloaked URL</th><th>Clicks</th></tr></thead><tbody>';
        foreach ($links as $link) {
            echo '<tr><td>' . $link->id . '</td><td>' . esc_html($link->original_url) . '</td><td>' . esc_html($link->cloaked_url) . '</td><td>' . $link->clicks . '</td></tr>';
        }
        echo '</tbody></table><p><strong>Pro Features:</strong> A/B Testing, Woo Integration, Export CSV - <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }

    public function activate() {
        $this->init();
    }
}

// Track clicks
add_action('init', function() {
    if (isset($_GET['sam'])) {
        global $wpdb;
        $hash = sanitize_text_field($_GET['sam']);
        $table_name = $wpdb->prefix . 'sam_links';
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE cloaked_url LIKE %s OR cloaked_url = %s", '%sam=' . $hash, site_url('/?sam=' . $hash)));
        if ($link) {
            $wpdb->update($table_name, array('clicks' => $link->clicks + 1), array('id' => $link->id));
            wp_redirect($link->original_url, 301);
            exit;
        }
    }
});

SmartAffiliateManager::get_instance();

// Minimal JS/CSS placeholders (in real plugin, use actual files)
add_action('wp_head', function() {
    echo '<style>.sam-cloaked { color: #0073aa; }</style>';
    echo '<script>console.log("SAM Pro loaded - Upgrade for analytics!");</script>';
});