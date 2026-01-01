/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automate affiliate link management, cloaking, tracking, and performance analytics to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateManager {
    private static $instance = null;
    public $db_version = '1.0';
    public $table_name;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'smart_affiliate_links';
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('the_content', array($this, 'replace_links'));
        add_shortcode('sa_link', array($this, 'shortcode_link'));
        add_action('wp_ajax_sa_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sa_track_click', array($this, 'track_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            affiliate_url varchar(500) NOT NULL,
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY keyword (keyword)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option('sa_db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sa-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sa-tracker', 'sa_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_menu_page('Smart Affiliate', 'Smart Affiliate', 'manage_options', 'smart-affiliate', array($this, 'admin_page'), 'dashicons-money-alt');
        add_submenu_page('smart-affiliate', 'Add Link', 'Add Link', 'manage_options', 'smart-affiliate-add', array($this, 'add_link_page'));
        add_submenu_page('smart-affiliate', 'Analytics', 'Analytics', 'manage_options', 'smart-affiliate-analytics', array($this, 'analytics_page'));
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>Smart Affiliate Links</h1><p>Manage your affiliate links below.</p>';
        $this->list_links();
        echo '</div>';
    }

    public function list_links() {
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC");
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Keyword</th><th>Affiliate URL</th><th>Clicks</th><th>Actions</th></tr></thead><tbody>';
        foreach ($links as $link) {
            echo '<tr><td>' . esc_html($link->keyword) . '</td><td>' . esc_html($link->affiliate_url) . '</td><td>' . $link->clicks . '</td><td><a href="?page=smart-affiliate-add&edit=' . $link->id . '">Edit</a> | <a href="#" onclick="deleteLink(' . $link->id . ')">Delete</a></td></tr>';
        }
        echo '</tbody></table>';
    }

    public function add_link_page() {
        global $wpdb;
        $id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $link = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id)) : null;
        if (isset($_POST['submit'])) {
            $keyword = sanitize_text_field($_POST['keyword']);
            $url = esc_url_raw($_POST['url']);
            if ($id) {
                $wpdb->update($this->table_name, array('keyword' => $keyword, 'affiliate_url' => $url), array('id' => $id));
            } else {
                $wpdb->insert($this->table_name, array('keyword' => $keyword, 'affiliate_url' => $url));
            }
            echo '<div class="notice notice-success"><p>Link saved!</p></div>';
            $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE keyword = %s", $keyword));
        }
        echo '<div class="wrap"><h1>' . ($id ? 'Edit' : 'Add') . ' Affiliate Link</h1>
        <form method="post"><table class="form-table">
        <tr><th>Keyword</th><td><input type="text" name="keyword" value="' . esc_attr($link ? $link->keyword : '') . '" placeholder="e.g. best-hosting" required /></td></tr>
        <tr><th>Affiliate URL</th><td><input type="url" name="url" value="' . esc_attr($link ? $link->affiliate_url : '') . '" style="width: 50%;" required /></td></tr>
        </table><p><input type="submit" name="submit" class="button-primary" value="Save Link" /></p></form></div>';
    }

    public function analytics_page() {
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY clicks DESC LIMIT 10");
        echo '<div class="wrap"><h1>Top Links Analytics (Free Version - Upgrade for Full)</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>Keyword</th><th>Clicks</th></tr></thead><tbody>';
        foreach ($links as $link) {
            echo '<tr><td>' . esc_html($link->keyword) . '</td><td>' . $link->clicks . '</td></tr>';
        }
        echo '</tbody></table><p><strong>Pro: Advanced charts, A/B testing, geo-tracking.</strong></p></div>';
    }

    public function replace_links($content) {
        if (is_feed() || is_preview()) return $content;
        global $wpdb;
        $links = $wpdb->get_results("SELECT keyword, affiliate_url FROM $this->table_name");
        foreach ($links as $link) {
            $cloaked = '<a href="' . home_url('/go/' . $link->keyword . '/') . '" target="_blank" rel="nofollow noopener">' . $link->keyword . '</a>';
            $content = preg_replace('/\b' . preg_quote($link->keyword, '/') . '\b/i', $cloaked, $content, 1);
        }
        return $content;
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('keyword' => ''), $atts);
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT affiliate_url FROM $this->table_name WHERE keyword = %s", $atts['keyword']));
        if (!$link) return '';
        return '<a href="' . home_url('/go/' . $atts['keyword'] . '/') . '" target="_blank" rel="nofollow noopener">' . $atts['keyword'] . '</a>';
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'sa_nonce')) wp_die('Security check failed');
        global $wpdb;
        $keyword = sanitize_text_field($_POST['keyword']);
        $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE keyword = %s", $keyword));
        $link = $wpdb->get_row($wpdb->prepare("SELECT affiliate_url FROM $this->table_name WHERE keyword = %s", $keyword));
        if ($link) {
            wp_redirect(esc_url_raw($link->affiliate_url), 302);
            exit;
        }
        wp_die('Link not found');
    }
}

SmartAffiliateManager::get_instance();

// Rewrite rules
add_action('init', function() {
    add_rewrite_rule('^go/([^/]+)/?', 'index.php?sa_go=$matches[1]', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'sa_go';
    return $vars;
});

add_action('template_redirect', function() {
    $go = get_query_var('sa_go');
    if ($go) {
        wp_redirect(home_url('/'), 302);
        exit;
    }
});

// Pro upsell notice
add_action('admin_notices', function() {
    if (get_option('sa_pro_activated') !== 'yes') {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Pro</strong> for advanced analytics & A/B testing! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
    }
});

// Dummy tracker.js content (inline for single file)
add_action('wp_head', function() {
    echo '<script>jQuery(document).ready(function($){$(".sa-go-link").on("click",function(e){var keyword=$(this).data("keyword");$.post(sa_ajax.ajaxurl,{action:"sa_track_click",keyword:keyword,nonce:$("#sa-nonce").val()},function(){});});});</script>';
    echo '<input type="hidden" id="sa-nonce" value="' . wp_create_nonce('sa_nonce') . '" />';
});