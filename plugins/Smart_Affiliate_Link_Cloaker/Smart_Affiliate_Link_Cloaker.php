/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Automatically cloaks and tracks affiliate links in posts, boosts clicks with smart redirects, and displays performance analytics for higher commissions.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('the_content', array($this, 'cloak_links'));
        add_shortcode('aff-stats', array($this, 'stats_shortcode'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            original_url text NOT NULL,
            cloaked_slug varchar(50) NOT NULL,
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
        add_rewrite_rule('^aff/([a-z0-9-]+)$', 'index.php?aff_link=$matches[1]', 'top');
        flush_rewrite_rules();
        add_rewrite_tag('%aff_link%', '([^&]+)');
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('template_redirect', array($this, 'handle_redirect'));
    }

    public function query_vars($vars) {
        $vars[] = 'aff_link';
        return $vars;
    }

    public function handle_redirect() {
        if (get_query_var('aff_link')) {
            $slug = sanitize_text_field(get_query_var('aff_link'));
            global $wpdb;
            $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE cloaked_slug = %s", $slug));
            if ($link) {
                $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE id = %d", $link->id));
                wp_redirect($link->original_url, 301);
                exit;
            }
        }
    }

    public function cloak_links($content) {
        if (is_feed() || is_admin()) return $content;
        preg_match_all('/https?:\/\/[^\s"\']+?(?:\b|(?=[\?\!]))/i', $content, $matches);
        foreach ($matches as $url) {
            if (strpos($url, 'amazon.com') !== false || strpos($url, 'clickbank.net') !== false || strpos($url, 'affiliate') !== false) {
                $slug = $this->generate_slug($url);
                $this->save_link($url, $slug);
                $cloaked = home_url('/aff/' . $slug . '/');
                $content = str_replace($url, $cloaked, $content);
            }
        }
        return $content;
    }

    private function generate_slug($url) {
        return substr(md5($url), 0, 8);
    }

    private function save_link($url, $slug) {
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $this->table_name WHERE original_url = %s", $url));
        if (!$exists) {
            $wpdb->insert($this->table_name, array('original_url' => $url, 'cloaked_slug' => $slug));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-script', plugin_dir_url(__FILE__) . 'sal-script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Links', 'Affiliate Links', 'manage_options', 'smart-affiliate-cloaker', array($this, 'admin_page'));
    }

    public function admin_page() {
        global $wpdb;
        if (isset($_POST['add_link'])) {
            $url = sanitize_url($_POST['original_url']);
            $slug = sanitize_text_field($_POST['slug']);
            $wpdb->insert($this->table_name, array('original_url' => $url, 'cloaked_slug' => $slug));
        }
        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC");
        echo '<div class="wrap"><h1>Affiliate Links</h1><form method="post"><table class="form-table"><tr><th>Original URL</th><td><input type="url" name="original_url" required style="width:300px;"></td></tr><tr><th>Custom Slug</th><td><input type="text" name="slug" required style="width:200px;"></td></tr></table><p><input type="submit" name="add_link" value="Add Link" class="button-primary"></p></form><h2>Links</h2><table class="wp-list-table widefat"><thead><tr><th>ID</th><th>Original</th><th>Cloaked</th><th>Clicks</th></tr></thead><tbody>';
        foreach ($links as $link) {
            echo '<tr><td>' . $link->id . '</td><td>' . esc_html($link->original_url) . '</td><td><a href="' . home_url('/aff/' . $link->cloaked_slug . '/') . '" target="_blank">' . home_url('/aff/' . $link->cloaked_slug . '/') . '</a></td><td>' . $link->clicks . '</td></tr>';
        }
        echo '</tbody></table><p><strong>Pro Upgrade:</strong> Unlock A/B testing, geo-targeting, and export analytics for $49/year. <a href="https://example.com/pro">Buy Pro</a></p></div>';
    }

    public function stats_shortcode($atts) {
        global $wpdb;
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM $this->table_name");
        $top_link = $wpdb->get_row("SELECT * FROM $this->table_name ORDER BY clicks DESC LIMIT 1");
        return '<div style="border:1px solid #ccc; padding:10px; background:#f9f9f9;"><strong>Total Clicks:</strong> ' . ($total_clicks ?: 0) . '<br><strong>Top Link:</strong> ' . ($top_link ? $top_link->clicks . ' clicks' : 'None') . ' <small>(Pro: Detailed dashboard)</small></div>';
    }
}

new SmartAffiliateCloaker();

// Pro teaser
add_action('admin_notices', function() {
    if (!get_option('sal_pro_dismissed')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Cloaker Pro</strong> for advanced analytics & A/B testing! <a href="https://example.com/pro">Learn More</a> | <a href="?sal_dismiss=1">Dismiss</a></p></div>';
    }
});
if (isset($_GET['sal_dismiss'])) {
    update_option('sal_pro_dismissed', 1);
}