/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: WP Affiliate Link Manager
 * Description: Manage, cloak, and track affiliate links with analytics.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPALM_VERSION', '1.0');
define('WPALM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPALM_PLUGIN_URL', plugin_dir_url(__FILE__));

class WP_Affiliate_Link_Manager {

    public function __construct() {
        add_action('init', array($this, 'init'));        
        add_action('admin_menu', array($this, 'admin_menu'));        
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));        
        add_action('wp_ajax_wpalm_track_click', array($this, 'track_click'));        
        add_action('wp_ajax_nopriv_wpalm_track_click', array($this, 'track_click'));        
        add_action('wp_ajax_wpalm_get_stats', array($this, 'get_stats'));        
        add_action('wp_ajax_nopriv_wpalm_get_stats', array($this, 'get_stats'));        
        add_shortcode('wpalm_link', array($this, 'shortcode_link'));        
    }

    public function init() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpalm_links';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            slug varchar(200) NOT NULL,
            url text NOT NULL,
            clicks int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function admin_menu() {
        add_menu_page(
            'WP Affiliate Link Manager',
            'Affiliate Links',
            'manage_options',
            'wp-affiliate-link-manager',
            array($this, 'admin_page'),
            'dashicons-admin-links'
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        include_once(WPALM_PLUGIN_DIR . 'admin/page.php');
    }

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_wp-affiliate-link-manager' !== $hook) {
            return;
        }
        wp_enqueue_style('wpalm-admin', WPALM_PLUGIN_URL . 'admin/style.css');
        wp_enqueue_script('wpalm-admin', WPALM_PLUGIN_URL . 'admin/script.js', array('jquery'), WPALM_VERSION, true);
        wp_localize_script('wpalm-admin', 'wpalm_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_script('wpalm-frontend', WPALM_PLUGIN_URL . 'frontend/script.js', array('jquery'), WPALM_VERSION, true);
        wp_localize_script('wpalm-frontend', 'wpalm_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function track_click() {
        global $wpdb;
        $slug = sanitize_text_field($_POST['slug']);
        $table_name = $wpdb->prefix . 'wpalm_links';
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE slug = %s", $slug));
        wp_die();
    }

    public function get_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpalm_links';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY clicks DESC");
        wp_send_json($results);
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array(
            'slug' => '',
            'text' => 'Click here'
        ), $atts, 'wpalm_link');
        
        if (empty($atts['slug'])) {
            return 'Invalid slug';
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpalm_links';
        $link = $wpdb->get_row($wpdb->prepare("SELECT url FROM $table_name WHERE slug = %s", $atts['slug']));
        
        if (!$link) {
            return 'Link not found';
        }
        
        return '<a href="' . esc_url($link->url) . '" class="wpalm-link" data-slug="' . esc_attr($atts['slug']) . '" target="_blank">' . esc_html($atts['text']) . '</a>';
    }
}

new WP_Affiliate_Link_Manager();

// Include admin and frontend files
if (is_admin()) {
    include_once(WPALM_PLUGIN_DIR . 'admin/functions.php');
}

include_once(WPALM_PLUGIN_DIR . 'frontend/functions.php');
