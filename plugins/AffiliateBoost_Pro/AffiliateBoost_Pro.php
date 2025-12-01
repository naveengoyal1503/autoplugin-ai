/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateBoost_Pro.php
*/
<?php
/**
 * Plugin Name: AffiliateBoost Pro
 * Description: Advanced affiliate link management and performance tracking
 * Version: 1.0.0
 * Author: AffiliateBoost
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

define('AFFILIATEBOOST_VERSION', '1.0.0');
define('AFFILIATEBOOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AFFILIATEBOOST_PLUGIN_URL', plugin_dir_url(__FILE__));

class AffiliateBoostPro {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
        add_shortcode('affiliateboost_link', array($this, 'shortcodeAffiliateLink'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendAssets'));
    }
    
    public function init() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'affiliateboost_links';
        $tracking_table = $wpdb->prefix . 'affiliateboost_tracking';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                url text NOT NULL,
                affiliate_id varchar(255) NOT NULL,
                category varchar(100),
                created_date datetime DEFAULT CURRENT_TIMESTAMP,
                status varchar(50) DEFAULT 'active',
                PRIMARY KEY (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$tracking_table'") != $tracking_table) {
            $sql = "CREATE TABLE $tracking_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                link_id mediumint(9) NOT NULL,
                clicks mediumint(9) DEFAULT 0,
                conversions mediumint(9) DEFAULT 0,
                revenue decimal(10,2) DEFAULT 0,
                tracking_date date NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY link_date (link_id, tracking_date)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    public function addAdminMenu() {
        add_menu_page(
            'AffiliateBoost Pro',
            'AffiliateBoost',
            'manage_options',
            'affiliateboost',
            array($this, 'renderDashboard'),
            'dashicons-link',
            30
        );
        
        add_submenu_page(
            'affiliateboost',
            'Manage Links',
            'Manage Links',
            'manage_options',
            'affiliateboost-links',
            array($this, 'renderLinksPage')
        );
        
        add_submenu_page(
            'affiliateboost',
            'Analytics',
            'Analytics',
            'manage_options',
            'affiliateboost-analytics',
            array($this, 'renderAnalytics')
        );
        
        add_submenu_page(
            'affiliateboost',
            'Settings',
            'Settings',
            'manage_options',
            'affiliateboost-settings',
            array($this, 'renderSettings')
        );
    }
    
    public function enqueueAdminAssets() {
        wp_enqueue_style('affiliateboost-admin', AFFILIATEBOOST_PLUGIN_URL . 'assets/admin-style.css');
        wp_enqueue_script('affiliateboost-admin', AFFILIATEBOOST_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), AFFILIATEBOOST_VERSION);
        wp_localize_script('affiliateboost-admin', 'affiliateboostAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }
    
    public function enqueueFrontendAssets() {
        wp_enqueue_script('affiliateboost-frontend', AFFILIATEBOOST_PLUGIN_URL . 'assets/frontend-script.js', array('jquery'), AFFILIATEBOOST_VERSION);
    }
    
    public function renderDashboard() {
        global $wpdb;
        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "affiliateboost_links WHERE status='active'");
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM " . $wpdb->prefix . "affiliateboost_tracking");
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM " . $wpdb->prefix . "affiliateboost_tracking");
        
        echo '<div class="wrap"><h1>AffiliateBoost Pro Dashboard</h1>';
        echo '<div class="affiliateboost-dashboard">';
        echo '<div class="stat-box"><h3>Active Links</h3><p class="stat-value">' . intval($total_links) . '</p></div>';
        echo '<div class="stat-box"><h3>Total Clicks</h3><p class="stat-value">' . intval($total_clicks) . '</p></div>';
        echo '<div class="stat-box"><h3>Total Revenue</h3><p class="stat-value">$' . number_format(floatval($total_revenue), 2) . '</p></div>';
        echo '</div></div>';
    }
    
    public function renderLinksPage() {
        global $wpdb;
        $table = $wpdb->prefix . 'affiliateboost_links';
        
        if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_link') {
            check_admin_referer('affiliateboost_add_link');
            $wpdb->insert($table, array(
                'name' => sanitize_text_field($_POST['link_name']),
                'url' => esc_url($_POST['link_url']),
                'affiliate_id' => sanitize_text_field($_POST['affiliate_id']),
                'category' => sanitize_text_field($_POST['category'])
            ));
        }
        
        $links = $wpdb->get_results("SELECT * FROM $table ORDER BY created_date DESC");
        
        echo '<div class="wrap"><h1>Manage Affiliate Links</h1>';
        echo '<form method="post" class="affiliateboost-form">';
        wp_nonce_field('affiliateboost_add_link');
        echo '<input type="hidden" name="action" value="add_link">';
        echo '<input type="text" name="link_name" placeholder="Link Name" required>';
        echo '<input type="url" name="link_url" placeholder="Affiliate URL" required>';
        echo '<input type="text" name="affiliate_id" placeholder="Affiliate ID" required>';
        echo '<input type="text" name="category" placeholder="Category">';
        echo '<button type="submit" class="button button-primary">Add Link</button>';
        echo '</form>';
        
        echo '<table class="wp-list-table fixedd striped">';
        echo '<thead><tr><th>Name</th><th>URL</th><th>Category</th><th>Created</th><th>Status</th></tr></thead>';
        echo '<tbody>';
        foreach ($links as $link) {
            echo '<tr><td>' . esc_html($link->name) . '</td><td>' . esc_html($link->url) . '</td><td>' . esc_html($link->category) . '</td><td>' . esc_html($link->created_date) . '</td><td>' . esc_html($link->status) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    
    public function renderAnalytics() {
        global $wpdb;
        $tracking = $wpdb->get_results("SELECT l.name, SUM(t.clicks) as total_clicks, SUM(t.conversions) as total_conversions, SUM(t.revenue) as total_revenue FROM " . $wpdb->prefix . "affiliateboost_links l LEFT JOIN " . $wpdb->prefix . "affiliateboost_tracking t ON l.id = t.link_id GROUP BY l.id");
        
        echo '<div class="wrap"><h1>Analytics</h1>';
        echo '<table class="wp-list-table fixed striped">';
        echo '<thead><tr><th>Link</th><th>Clicks</th><th>Conversions</th><th>Revenue</th></tr></thead>';
        echo '<tbody>';
        foreach ($tracking as $row) {
            echo '<tr><td>' . esc_html($row->name) . '</td><td>' . intval($row->total_clicks) . '</td><td>' . intval($row->total_conversions) . '</td><td>$' . number_format(floatval($row->total_revenue), 2) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    
    public function renderSettings() {
        echo '<div class="wrap"><h1>AffiliateBoost Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('affiliateboost_settings');
        do_settings_sections('affiliateboost_settings');
        echo '<label>Enable Click Tracking: <input type="checkbox" name="affiliateboost_tracking_enabled" value="1" ' . checked(get_option('affiliateboost_tracking_enabled'), 1, false) . '></label>';
        echo '<button type="submit" class="button button-primary">Save Settings</button>';
        echo '</form></div>';
    }
    
    public function shortcodeAffiliateLink($atts) {
        global $wpdb;
        $atts = shortcode_atts(array('id' => 0, 'text' => 'Click Here'), $atts);
        
        if (!$atts['id']) return '';
        
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "affiliateboost_links WHERE id = %d", $atts['id']));
        
        if (!$link) return '';
        
        return '<a href="' . esc_url($link->url) . '" class="affiliateboost-link" data-link-id="' . intval($link->id) . '" target="_blank" rel="noopener noreferrer">' . esc_html($atts['text']) . '</a>';
    }
}

AffiliateBoostPro::getInstance();

register_activation_hook(__FILE__, array('AffiliateBoostPro', 'init'));
?>