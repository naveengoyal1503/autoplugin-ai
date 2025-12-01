<?php
/*
Plugin Name: AffiliateTrack Pro
Plugin URI: https://affiliatetrackpro.com
Description: Advanced affiliate link management and revenue tracking for WordPress monetization
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateTrack_Pro.php
License: GPL2
Text Domain: affiliatetrack-pro
*/

if (!defined('ABSPATH')) exit;

define('ATP_VERSION', '1.0.0');
define('ATP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ATP_PLUGIN_URL', plugin_dir_url(__FILE__));

class AffiliateTrackPro {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('init', array($this, 'register_post_types'));
        add_action('wp_ajax_atp_add_link', array($this, 'ajax_add_link'));
        add_action('wp_ajax_atp_get_stats', array($this, 'ajax_get_stats'));
        add_shortcode('affiliate_link', array($this, 'affiliate_link_shortcode'));
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}atp_links (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            original_url LONGTEXT NOT NULL,
            short_code VARCHAR(50) UNIQUE NOT NULL,
            clicks INT(11) DEFAULT 0,
            conversions INT(11) DEFAULT 0,
            revenue DECIMAL(10, 2) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY user_id (user_id),
            KEY short_code (short_code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('atp_version', ATP_VERSION);
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'AffiliateTrack Pro',
            'AffiliateTrack',
            'manage_options',
            'affiliatetrack-pro',
            array($this, 'render_dashboard'),
            'dashicons-link',
            30
        );
        
        add_submenu_page(
            'affiliatetrack-pro',
            'Links',
            'Links',
            'manage_options',
            'affiliatetrack-links',
            array($this, 'render_links_page')
        );
        
        add_submenu_page(
            'affiliatetrack-pro',
            'Settings',
            'Settings',
            'manage_options',
            'affiliatetrack-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function render_dashboard() {
        echo '<div class="wrap"><h1>AffiliateTrack Pro Dashboard</h1>';
        echo '<div class="dashboard-stats">';
        
        global $wpdb;
        $stats = $wpdb->get_row("SELECT SUM(clicks) as total_clicks, SUM(conversions) as total_conversions, SUM(revenue) as total_revenue FROM {$wpdb->prefix}atp_links WHERE user_id = " . get_current_user_id());
        
        echo '<div class="stat-box"><h3>Total Clicks</h3><p>' . ($stats->total_clicks ?: 0) . '</p></div>';
        echo '<div class="stat-box"><h3>Total Conversions</h3><p>' . ($stats->total_conversions ?: 0) . '</p></div>';
        echo '<div class="stat-box"><h3>Total Revenue</h3><p>\$' . number_format($stats->total_revenue ?: 0, 2) . '</p></div>';
        
        echo '</div></div>';
    }
    
    public function render_links_page() {
        echo '<div class="wrap"><h1>Manage Affiliate Links</h1>';
        echo '<button class="button button-primary" id="atp-add-new">Add New Link</button>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        echo '<th>Name</th><th>Short Code</th><th>Clicks</th><th>Conversions</th><th>Revenue</th><th>Actions</th>';
        echo '</tr></thead><tbody id="atp-links-table"></tbody></table>';
        echo '</div>';
    }
    
    public function render_settings_page() {
        echo '<div class="wrap"><h1>AffiliateTrack Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('atp_settings');
        do_settings_sections('atp_settings');
        submit_button();
        echo '</form></div>';
    }
    
    public function register_post_types() {
        // Rewrite rules for short links
        add_rewrite_rule('^go/([a-zA-Z0-9-]+)/?$', 'index.php?atp_short=$matches[1]', 'top');
        
        add_filter('query_vars', function($vars) {
            $vars[] = 'atp_short';
            return $vars;
        });
        
        add_action('template_redirect', array($this, 'handle_short_link'));
    }
    
    public function handle_short_link() {
        global $wpdb;
        $short_code = get_query_var('atp_short');
        
        if ($short_code) {
            $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}atp_links WHERE short_code = %s", $short_code));
            
            if ($link) {
                $wpdb->update(
                    $wpdb->prefix . 'atp_links',
                    array('clicks' => $link->clicks + 1),
                    array('id' => $link->id)
                );
                
                wp_redirect($link->original_url);
                exit;
            }
        }
    }
    
    public function ajax_add_link() {
        check_ajax_referer('atp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $name = sanitize_text_field($_POST['name']);
        $url = esc_url($_POST['url']);
        $short_code = sanitize_key($_POST['short_code'] ?: substr(md5(time()), 0, 8));
        
        $wpdb->insert(
            $wpdb->prefix . 'atp_links',
            array(
                'user_id' => get_current_user_id(),
                'name' => $name,
                'original_url' => $url,
                'short_code' => $short_code
            )
        );
        
        wp_send_json_success(array('id' => $wpdb->insert_id, 'short_code' => $short_code));
    }
    
    public function ajax_get_stats() {
        check_ajax_referer('atp_nonce', 'nonce');
        
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}atp_links WHERE user_id = " . get_current_user_id());
        
        wp_send_json_success($links);
    }
    
    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => '',
            'text' => 'Click Here'
        ), $atts);
        
        $short_code = sanitize_key($atts['code']);
        $link_url = site_url('/go/' . $short_code);
        
        return '<a href="' . esc_url($link_url) . '" class="atp-affiliate-link">' . esc_html($atts['text']) . '</a>';
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'affiliatetrack') !== false) {
            wp_enqueue_script('atp-admin', ATP_PLUGIN_URL . 'admin.js', array('jquery'), ATP_VERSION);
            wp_localize_script('atp-admin', 'atpData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('atp_nonce')
            ));
            wp_enqueue_style('atp-admin', ATP_PLUGIN_URL . 'admin.css', array(), ATP_VERSION);
        }
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('atp-frontend', ATP_PLUGIN_URL . 'frontend.css', array(), ATP_VERSION);
    }
}

AffiliateTrackPro::get_instance();
?>