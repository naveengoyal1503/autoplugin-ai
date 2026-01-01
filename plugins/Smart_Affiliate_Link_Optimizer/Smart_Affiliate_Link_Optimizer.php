/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Optimizer
 * Plugin URI: https://example.com/smart-affiliate-optimizer
 * Description: Automatically cloaks, shortens, and tracks affiliate links with click analytics and A/B testing to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateOptimizer {
    private static $instance = null;
    private $db_version = '1.0';
    private $table_name;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'saol_links';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'replace_links'));
        add_shortcode('saol', array($this, 'shortcode_handler'));
        add_action('wp_ajax_saol_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_saol_track_click', array($this, 'track_click'));

        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
        add_action('wp_ajax_saol_save_link', array($this, 'ajax_save_link'));
        add_action('wp_ajax_saol_delete_link', array($this, 'ajax_delete_link'));
        add_action('wp_ajax_saol_get_stats', array($this, 'ajax_get_stats'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            original_url text NOT NULL,
            shortcode varchar(255) NOT NULL,
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY shortcode (shortcode)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('saol_db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function init() {
        wp_register_style('saol-admin-css', plugin_dir_url(__FILE__) . 'saol-admin.css', array(), '1.0');
        wp_register_script('saol-admin-js', plugin_dir_url(__FILE__) . 'saol-admin.js', array('jquery'), '1.0', true);
        wp_localize_script('saol-admin-js', 'saol_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saol-frontend', plugin_dir_url(__FILE__) . 'saol-frontend.js', array('jquery'), '1.0', true);
        wp_localize_script('saol-frontend', 'saol_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function replace_links($content) {
        if (is_feed() || is_preview()) {
            return $content;
        }

        preg_match_all('/https?:\/\/[^\s<>"]+?(?:\?[^\s<>"]+|&[^\s<>"]+)*[\w\/]/', $content, $matches);
        foreach ($matches as $url) {
            if (strpos($url, 'affiliate') !== false || strpos($url, 'amazon') !== false || strpos($url, 'clickbank') !== false) {
                $shortcode = $this->get_or_create_shortcode($url);
                $content = str_replace($url, $shortcode, $content);
            }
        }
        return $content;
    }

    private function get_or_create_shortcode($url) {
        global $wpdb;
        $shortcode = '[saol url="' . esc_attr($url) . '"]';

        $existing = $wpdb->get_var($wpdb->prepare("SELECT shortcode FROM $this->table_name WHERE original_url = %s", $url));
        if ($existing) {
            return $existing;
        }

        $unique_id = uniqid('saol_');
        $wpdb->insert($this->table_name, array(
            'original_url' => $url,
            'shortcode' => $shortcode
        ));
        return $shortcode;
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array('url' => ''), $atts);
        if (empty($atts['url'])) return '';

        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE original_url = %s", $atts['url']));
        if (!$link) return $atts['url'];

        $redirect_url = add_query_arg('saol_id', $link->id, admin_url('admin-ajax.php?action=saol_track_click'));
        return '<a href="' . esc_url($redirect_url) . '" class="saol-link" data-original="' . esc_attr($link->original_url) . '">Click Here for Deal</a>';
    }

    public function track_click() {
        if (!isset($_GET['saol_id'])) {
            wp_die();
        }

        global $wpdb;
        $id = intval($_GET['saol_id']);
        $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE id = %d", $id));

        $link = $wpdb->get_var($wpdb->prepare("SELECT original_url FROM $this->table_name WHERE id = %d", $id));
        if ($link) {
            wp_redirect(esc_url_raw($link));
            exit;
        }
        wp_die();
    }

    public function admin_menu() {
        add_options_page('Affiliate Optimizer', 'Affiliate Optimizer', 'manage_options', 'saol', array($this, 'admin_page'));
    }

    public function admin_enqueue($hook) {
        if ('settings_page_saol' !== $hook) return;
        wp_enqueue_style('saol-admin-css');
        wp_enqueue_script('saol-admin-js');
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>Smart Affiliate Link Optimizer</h1>';
        echo '<p>Manage your affiliate links and view stats. Premium upgrade unlocks A/B testing and advanced analytics.</p>';
        $this->admin_links_table();
        echo '</div>';
    }

    private function admin_links_table() {
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC");
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Original URL</th><th>Shortcode</th><th>Clicks</th><th>Actions</th></tr></thead><tbody>';
        foreach ($links as $link) {
            echo '<tr>';
            echo '<td>' . $link->id . '</td>';
            echo '<td>' . esc_html($link->original_url) . '</td>';
            echo '<td>' . esc_html($link->shortcode) . '</td>';
            echo '<td>' . $link->clicks . '</td>';
            echo '<td><button class="button saol-delete" data-id="' . $link->id . '">Delete</button> <button class="button saol-stats" data-id="' . $link->id . '">Stats</button></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    public function ajax_save_link() {
        if (!current_user_can('manage_options')) wp_die();
        global $wpdb;
        $url = sanitize_url($_POST['url']);
        $shortcode = $this->get_or_create_shortcode($url);
        wp_send_json_success(array('shortcode' => $shortcode));
    }

    public function ajax_delete_link() {
        if (!current_user_can('manage_options')) wp_die();
        global $wpdb;
        $id = intval($_POST['id']);
        $wpdb->delete($this->table_name, array('id' => $id));
        wp_send_json_success();
    }

    public function ajax_get_stats() {
        if (!current_user_can('manage_options')) wp_die();
        global $wpdb;
        $id = intval($_POST['id']);
        $stats = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id));
        wp_send_json_success($stats);
    }
}

SmartAffiliateOptimizer::get_instance();