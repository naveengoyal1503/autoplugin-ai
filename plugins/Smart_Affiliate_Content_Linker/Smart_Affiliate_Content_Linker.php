<?php
/*
Plugin Name: Smart Affiliate Content Linker
Plugin URI: https://smartaffiliatelinker.com
Description: Automatically convert product mentions into affiliate links with tracking and analytics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Content_Linker.php
License: GPL v2 or later
Text Domain: smart-affiliate-linker
*/

if (!defined('ABSPATH')) exit;

define('SACL_VERSION', '1.0.0');
define('SACL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SACL_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartAffiliateContentLinker {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->initHooks();
        $this->createTables();
    }
    
    private function initHooks() {
        add_action('admin_menu', [$this, 'addMenus']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_filter('the_content', [$this, 'convertProductMentions'], 99);
        add_action('wp_ajax_sacl_add_affiliate_link', [$this, 'ajaxAddAffiliateLink']);
        add_action('wp_ajax_sacl_get_analytics', [$this, 'ajaxGetAnalytics']);
    }
    
    private function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sacl_links (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            product_name varchar(255) NOT NULL,
            affiliate_url text NOT NULL,
            affiliate_id varchar(100),
            commission_rate float,
            clicks bigint(20) DEFAULT 0,
            conversions bigint(20) DEFAULT 0,
            revenue float DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sacl_clicks (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            link_id bigint(20) NOT NULL,
            visitor_ip varchar(45),
            referer text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_id (link_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function addMenus() {
        add_menu_page(
            'Affiliate Content Linker',
            'Affiliate Linker',
            'manage_options',
            'sacl-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-link',
            25
        );
        
        add_submenu_page(
            'sacl-dashboard',
            'Manage Links',
            'Manage Links',
            'manage_options',
            'sacl-links',
            [$this, 'renderLinksPage']
        );
        
        add_submenu_page(
            'sacl-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'sacl-analytics',
            [$this, 'renderAnalytics']
        );
        
        add_submenu_page(
            'sacl-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'sacl-settings',
            [$this, 'renderSettings']
        );
    }
    
    public function renderDashboard() {
        global $wpdb;
        $stats = $wpdb->get_row(
            "SELECT COUNT(*) as total_links, SUM(clicks) as total_clicks, SUM(conversions) as total_conversions, SUM(revenue) as total_revenue FROM {$wpdb->prefix}sacl_links"
        );
        echo '<div class="wrap"><h1>Affiliate Content Linker Dashboard</h1>';
        echo '<div class="sacl-stats">';
        echo '<div class="stat-box"><h3>' . intval($stats->total_links) . '</h3><p>Total Links</p></div>';
        echo '<div class="stat-box"><h3>' . intval($stats->total_clicks) . '</h3><p>Total Clicks</p></div>';
        echo '<div class="stat-box"><h3>' . intval($stats->total_conversions) . '</h3><p>Conversions</p></div>';
        echo '<div class="stat-box"><h3>$' . number_format(floatval($stats->total_revenue), 2) . '</h3><p>Total Revenue</p></div>';
        echo '</div></div>';
    }
    
    public function renderLinksPage() {
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sacl_links ORDER BY created_at DESC LIMIT 50");
        echo '<div class="wrap"><h1>Manage Affiliate Links</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>Product</th><th>Post</th><th>Clicks</th><th>Conversions</th><th>Revenue</th><th>Actions</th></tr></thead><tbody>';
        foreach ($links as $link) {
            $post_title = get_the_title($link->post_id);
            echo '<tr><td>' . esc_html($link->product_name) . '</td><td>' . esc_html($post_title) . '</td><td>' . intval($link->clicks) . '</td><td>' . intval($link->conversions) . '</td><td>$' . number_format(floatval($link->revenue), 2) . '</td><td><button class="button" data-link-id="' . esc_attr($link->id) . '">Edit</button></td></tr>';
        }
        echo '</tbody></table></div>';
    }
    
    public function renderAnalytics() {
        echo '<div class="wrap"><h1>Analytics</h1><p>Detailed performance metrics coming soon...</p></div>';
    }
    
    public function renderSettings() {
        echo '<div class="wrap"><h1>Settings</h1><form method="post"><table class="form-table"><tr><th><label for="sacl_api_key">API Key</label></th><td><input type="text" id="sacl_api_key" name="sacl_api_key" value="' . esc_attr(get_option('sacl_api_key')) . '" /></td></tr></table><submit class="button button-primary">Save Settings</submit></form></div>';
    }
    
    public function convertProductMentions($content) {
        if (is_admin() || !is_single()) return $content;
        
        global $wpdb, $post;
        $links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sacl_links WHERE post_id = %d",
            $post->ID
        ));
        
        foreach ($links as $link) {
            $pattern = '/\b' . preg_quote($link->product_name, '/') . '\b/i';
            $replacement = '<a href="' . esc_url($link->affiliate_url) . '" class="sacl-affiliate-link" data-link-id="' . esc_attr($link->id) . '" onclick="sacl_trackClick(' . esc_attr($link->id) . ');">' . esc_html($link->product_name) . '</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        
        return $content;
    }
    
    public function ajaxAddAffiliateLink() {
        check_ajax_referer('sacl_nonce', 'nonce');
        
        global $wpdb;
        $post_id = intval($_POST['post_id']);
        $product_name = sanitize_text_field($_POST['product_name']);
        $affiliate_url = esc_url($_POST['affiliate_url']);
        $commission_rate = floatval($_POST['commission_rate'] ?? 0);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'sacl_links',
            [
                'post_id' => $post_id,
                'product_name' => $product_name,
                'affiliate_url' => $affiliate_url,
                'commission_rate' => $commission_rate
            ]
        );
        
        wp_send_json_success(['link_id' => $wpdb->insert_id]);
    }
    
    public function ajaxGetAnalytics() {
        check_ajax_referer('sacl_nonce', 'nonce');
        
        global $wpdb;
        $link_id = intval($_POST['link_id']);
        $analytics = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sacl_links WHERE id = %d",
            $link_id
        ));
        
        wp_send_json_success($analytics);
    }
    
    public function enqueueScripts() {
        wp_enqueue_script('sacl-frontend', SACL_PLUGIN_URL . 'js/frontend.js', [], SACL_VERSION, true);
        wp_localize_script('sacl-frontend', 'sacl_vars', ['nonce' => wp_create_nonce('sacl_nonce')]);
    }
    
    public function enqueueAdminScripts() {
        wp_enqueue_style('sacl-admin', SACL_PLUGIN_URL . 'css/admin.css', [], SACL_VERSION);
        wp_enqueue_script('sacl-admin', SACL_PLUGIN_URL . 'js/admin.js', ['jquery'], SACL_VERSION, true);
        wp_localize_script('sacl-admin', 'sacl_admin', ['nonce' => wp_create_nonce('sacl_nonce')]);
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    SmartAffiliateContentLinker::getInstance();
});

// Activation hook
register_activation_hook(__FILE__, function() {
    SmartAffiliateContentLinker::getInstance();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Cleanup if needed
});
?>