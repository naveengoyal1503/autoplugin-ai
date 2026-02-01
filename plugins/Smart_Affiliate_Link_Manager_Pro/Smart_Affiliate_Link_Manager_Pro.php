/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager Pro
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automatically cloak, track, and optimize affiliate links with analytics and A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateLinkManager {
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_filter('the_content', array($this, 'cloak_links'));
        add_shortcode('sa_link', array($this, 'shortcode_link'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sa_track_click', array($this, 'track_click'));
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function cloak_links($content) {
        if (is_admin()) return $content;
        $pattern = '/https?:\/\/(amzn|amazon|clickbank|shareasale|commissionjunction|cj)\.[a-z.]+\S*/i';
        $content = preg_replace_callback($pattern, array($this, 'replace_link'), $content);
        return $content;
    }

    public function replace_link($matches) {
        $url = $matches;
        $shortcode = '[sa_link url="' . esc_attr($url) . '"]';
        return do_shortcode($shortcode);
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('url' => ''), $atts);
        if (empty($atts['url'])) return '';
        $id = uniqid('sa_');
        $cloak_url = add_query_arg(array('sa_track' => $id, 'sa_url' => urlencode($atts['url'])), home_url('/'));
        return '<a href="' . esc_url($cloak_url) . '" class="sa-link" data-track="' . esc_attr($id) . '" onclick="saTrack(this);">' . esc_html($atts['url']) . '</a>';
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sa-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sa-tracker', 'sa_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'sa_nonce')) {
            wp_die('Security check failed');
        }
        $url = sanitize_url($_POST['url']);
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
        $data = array(
            'url' => $url,
            'ip' => $ip,
            'ua' => $ua,
            'time' => current_time('mysql'),
            'clicks' => 1
        );
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'sa_clicks', $data);
        wp_redirect($url);
        exit;
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate', 'Smart Affiliate', 'manage_options', 'sa-dashboard', array($this, 'dashboard'));
    }

    public function dashboard() {
        global $wpdb;
        $clicks = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "sa_clicks ORDER BY time DESC LIMIT 50");
        echo '<div class="wrap"><h1>Smart Affiliate Dashboard</h1><table class="wp-list-table widefat"><thead><tr><th>URL</th><th>IP</th><th>Time</th></tr></thead><tbody>';
        foreach ($clicks as $click) {
            echo '<tr><td>' . esc_html($click->url) . '</td><td>' . esc_html($click->ip) . '</td><td>' . esc_html($click->time) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'sa_clicks';
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url longtext NOT NULL,
            ip varchar(100) NOT NULL,
            ua text NOT NULL,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id)
        ) $wpdb->charset_colllate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivate() {
        // Cleanup optional
    }
}

new SmartAffiliateLinkManager();

// Premium upsell notice
function sa_upsell_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>A/B Testing, Detailed Analytics, and Custom Reports</strong> with <a href="https://example.com/premium" target="_blank">Smart Affiliate Pro Add-ons</a> starting at $49/year!</p></div>';
    }
}
add_action('admin_notices', 'sa_upsell_notice');

// Dummy tracker.js content - in real plugin, this would be a separate enqueued file
// For single-file, inline script
function sa_inline_tracker() {
    ?><script>jQuery(document).ready(function($) { window.saTrack = function(el) { $.post(sa_ajax.ajaxurl, {action: 'sa_track_click', url: $(el).data('track'), nonce: '<?php echo wp_create_nonce('sa_nonce'); ?>'}); }; });</script><?php
}
add_action('wp_footer', 'sa_inline_tracker');