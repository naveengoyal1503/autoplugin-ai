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
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-link-manager
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateLinkManager {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_filter('the_content', array($this, 'cloak_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (!session_id()) {
            session_start();
        }
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('salml-script', plugin_dir_url(__FILE__) . 'salml.js', array('jquery'), '1.0.0', true);
        wp_localize_script('salml-script', 'salml_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('salml_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Link Manager', 'Affiliate Manager', 'manage_options', 'salml-settings', array($this, 'settings_page'));
    }

    public function admin_enqueue_scripts($hook) {
        if ('settings_page' !== get_current_screen()->id) return;
        wp_enqueue_script('salml-admin', plugin_dir_url(__FILE__) . 'salml-admin.js', array('jquery'), '1.0.0', true);
    }

    public function settings_page() {
        if (isset($_POST['salml_save'])) {
            update_option('salml_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('salml_api_key', '');
        echo '<div class="wrap"><h1>Smart Affiliate Link Manager Settings</h1><form method="post"><table class="form-table"><tr><th>Premium API Key</th><td><input type="text" name="api_key" value="' . esc_attr($api_key) . '" class="regular-text" placeholder="Enter for premium features" /> <p class="description">Get premium key at <a href="https://example.com/premium" target="_blank">example.com/premium</a></p></td></tr></table><p><input type="submit" name="salml_save" class="button-primary" value="Save Settings" /></p></form>';
        $this->show_stats();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'salml_clicks';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            link text NOT NULL,
            ip text NOT NULL,
            user_agent text NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function cloak_links($content) {
        if (is_feed() || is_preview()) return $content;
        preg_match_all('/https?:\/\/[^\s<>"]+?(?=[^\w\/\-](?:[a-zA-Z]|$))/', $content, $matches);
        foreach ($matches as $url) {
            if (strpos($url, 'amazon.com') !== false || strpos($url, 'clickbank.net') !== false || strpos($url, 'youraffiliate') !== false) {
                $cloaked = add_query_arg('salml', base64_encode($url), home_url('/go/'));
                $content = str_replace($url, '<a href="' . esc_url($cloaked) . '" target="_blank" rel="nofollow">' . $url . '</a>', $content);
            }
        }
        return $content;
    }

    public function activate() {
        $this->create_table();
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function show_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'salml_clicks';
        $stats = $wpdb->get_results("SELECT COUNT(*) as total, DATE(time) as date FROM $table_name GROUP BY DATE(time) ORDER BY time DESC LIMIT 7");
        echo '<h2>Recent Stats (Free Version)</h2><ul>';
        foreach ($stats as $stat) {
            echo '<li>' . esc_html($stat->date) . ': ' . intval($stat->total) . ' clicks</li>';
        }
        echo '</ul><p><strong>Upgrade to Premium for A/B testing, conversion tracking, and auto-optimization!</strong></p>';
    }
}

// AJAX for tracking
add_action('wp_ajax_salml_track', 'salml_track_click');
add_action('wp_ajax_nopriv_salml_track', 'salml_track_click');
function salml_track_click() {
    check_ajax_referer('salml_nonce', 'nonce');
    $link = sanitize_url($_POST['link']);
    global $wpdb;
    $table_name = $wpdb->prefix . 'salml_clicks';
    $wpdb->insert($table_name, array(
        'link' => $link,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ));
    wp_redirect($link);
    exit;
}

// Handle cloaked redirects
add_action('init', 'salml_handle_redirect');
function salml_handle_redirect() {
    if (isset($_GET['salml']) && $_SERVER['REQUEST_URI'] === '/go/') {
        $url = base64_decode(sanitize_text_field($_GET['salml']));
        if ($url) {
            // Track via AJAX simulation or direct
            wp_remote_post(admin_url('admin-ajax.php'), array(
                'body' => array(
                    'action' => 'salml_track',
                    'link' => $url,
                    'nonce' => wp_create_nonce('salml_nonce')
                )
            ));
            wp_redirect($url, 301);
            exit;
        }
    }
}

SmartAffiliateLinkManager::get_instance();

// Freemium nag
add_action('admin_notices', 'salml_premium_nag');
function salml_premium_nag() {
    if (!get_option('salml_api_key') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>A/B testing, advanced analytics</strong> with <a href="' . admin_url('options-general.php?page=salml-settings') . '">Smart Affiliate Link Manager Premium</a> for $9/mo!</p></div>';
    }
}
?>