/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automate affiliate link creation, cloaking, tracking, and performance analytics to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateLinkManager {
    private static $instance = null;
    public $db_version = '1.0';
    public $table_name;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sal_links';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sal_track_click', array($this, 'track_click'));
        add_shortcode('sal_link', array($this, 'shortcode_link'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'template_redirect'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            affiliate_url text NOT NULL,
            slug varchar(200) NOT NULL,
            clicks bigint DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('sal_db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function init() {
        wp_register_style('sal-admin', plugin_dir_url(__FILE__) . 'sal-style.css', array(), '1.0');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-track', plugin_dir_url(__FILE__) . 'sal-track.js', array('jquery'), '1.0', true);
        wp_localize_script('sal-track', 'sal_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_menu_page('Affiliate Links', 'Affiliate Links', 'manage_options', 'sal-links', array($this, 'admin_page'), 'dashicons-link');
    }

    public function admin_page() {
        global $wpdb;
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $message = '';

        if (isset($_POST['submit'])) {
            $name = sanitize_text_field($_POST['name']);
            $url = esc_url_raw($_POST['url']);
            $slug = sanitize_title($_POST['slug']);

            if (empty($name) || empty($url) || empty($slug)) {
                $message = '<div class="notice notice-error"><p>Missing fields.</p></div>';
            } else {
                $wpdb->insert($this->table_name, array(
                    'name' => $name,
                    'affiliate_url' => $url,
                    'slug' => $slug
                ));
                $message = '<div class="notice notice-success"><p>Link added.</p></div>';
            }
        }

        if ($action == 'list') {
            echo '<div class="wrap"><h1>Affiliate Links</h1>' . $message;
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Name</th><th>Slug</th><th>Clicks</th><th>Shortcode</th><th>Link</th></tr></thead><tbody>';
            $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY id DESC");
            foreach ($links as $link) {
                echo '<tr><td>' . esc_html($link->name) . '</td><td>/' . esc_html($link->slug) . '/</td><td>' . $link->clicks . '</td><td>[sal_link id="' . $link->id . '"]</td><td><a href="' . home_url($link->slug) . '" target="_blank">View</a></td></tr>';
            }
            echo '</tbody></table>';

            echo '<h2>Add New Link</h2><form method="post"><table class="form-table">';
            echo '<tr><th>Name</th><td><input type="text" name="name" required /></td></tr>';
            echo '<tr><th>Affiliate URL</th><td><input type="url" name="url" style="width:50%;" required /></td></tr>';
            echo '<tr><th>Slug</th><td><input type="text" name="slug" required /></td></tr>';
            echo '</table><p><input type="submit" name="submit" class="button-primary" value="Add Link" /></p></form></div>';
        }
    }

    public function track_click() {
        global $wpdb;
        $id = intval($_POST['id']);
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id));
        if ($link) {
            $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE id = %d", $id));
            wp_redirect($link->affiliate_url, 301);
            exit;
        }
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT slug FROM $this->table_name WHERE id = %d", $atts['id']));
        if ($link) {
            return '<a href="' . home_url($link->slug) . '" onclick="salTrack(' . $atts['id'] . '); return false;">Click here</a>';
        }
        return '';
    }

    public function add_query_vars($vars) {
        $vars[] = 'sal';
        return $vars;
    }

    public function template_redirect() {
        $slug = get_query_var('sal');
        if ($slug) {
            global $wpdb;
            $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE slug = %s", $slug));
            if ($link) {
                $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE id = %d", $link->id));
                wp_redirect($link->affiliate_url, 301);
                exit;
            }
        }
    }
}

SmartAffiliateLinkManager::get_instance();

// Pro notice
function sal_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate Pro:</strong> Unlock A/B testing, analytics dashboard, and unlimited links for $49/year. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'sal_pro_notice');

// JS for tracking
add_action('wp_footer', function() {
    ?><script>jQuery(document).ready(function($){window.salTrack=function(id){$.post(sal_ajax.ajaxurl,{action:'sal_track_click',id:id});};});</script><?php
});

// Minimal CSS
add_action('admin_head', function() {
    echo '<style>.sal-pro{color:#0073aa;}</style>';
});