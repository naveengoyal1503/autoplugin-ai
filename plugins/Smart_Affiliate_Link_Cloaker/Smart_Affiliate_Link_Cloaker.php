/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Automatically cloaks and tracks affiliate links with analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

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
        add_shortcode('afflink', array($this, 'afflink_shortcode'));
        add_action('wp_ajax_sal_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sal_click', array($this, 'track_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url text NOT NULL,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option('sal_db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function init() {
        if (isset($_GET['page']) && $_GET['page'] === 'smart-affiliate-cloaker') {
            add_menu_page('Affiliate Links', 'Affiliate Links', 'manage_options', 'smart-affiliate-cloaker', array($this, 'admin_page'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-script', plugin_dir_url(__FILE__) . 'sal-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sal-script', 'sal_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function cloak_links($content) {
        if (is_admin()) return $content;
        preg_match_all('/https?:\/\/[^\s"\']+?(?:\?[\w=&-]+)?/i', $content, $matches);
        foreach ($matches as $url) {
            if (strpos($url, 'amazon.com') !== false || strpos($url, 'clickbank.net') !== false || strpos($url, 'affiliate') !== false) {
                $cloaked = $this->get_or_create_cloaked_url($url);
                $content = str_replace($url, $cloaked, $content);
            }
        }
        return $content;
    }

    private function get_or_create_cloaked_url($url) {
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare("SELECT cloaked FROM $this->table_name WHERE url = %s", $url));
        if ($existing) return $existing;

        $cloaked = home_url('/go/' . wp_generate_uuid4());
        $wpdb->insert($this->table_name, array('url' => $url, 'cloaked' => $cloaked));
        return $cloaked;
    }

    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(array('url' => ''), $atts);
        return $this->get_or_create_cloaked_url($atts['url']);
    }

    public function track_click() {
        if (!isset($_POST['cloaked'])) wp_die();
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE cloaked = %s", $_POST['cloaked']));
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE cloaked = %s", $_POST['cloaked']));
        if ($link) {
            wp_redirect($link->url, 301);
            exit;
        }
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>Affiliate Links Dashboard</h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Cloaked URL</th><th>Original URL</th><th>Clicks</th></tr></thead><tbody>';
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created DESC");
        foreach ($links as $link) {
            echo '<tr><td>' . $link->id . '</td><td>' . $link->cloaked . '</td><td>' . $link->url . '</td><td>' . $link->clicks . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}

new SmartAffiliateCloaker();

add_action('init', function() {
    add_rewrite_rule('^go/([^/]*)/?', 'index.php?sal_redirect=$matches[1]', 'top');
});

function wp_generate_uuid4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Note: Add sal-script.js manually or extend for Pro. This is single-file core.