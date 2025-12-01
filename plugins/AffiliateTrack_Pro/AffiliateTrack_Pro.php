<?php
/*
Plugin Name: AffiliateTrack Pro
Plugin URI: https://affiliatetrackpro.local
Description: Advanced affiliate link tracking and performance analytics with commission management
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateTrack_Pro.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

define('ATP_VERSION', '1.0.0');
define('ATP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ATP_PLUGIN_URL', plugin_dir_url(__FILE__));

class AffiliateTrackPro {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('template_redirect', array($this, 'trackAffiliateClick'));
        add_shortcode('affiliate_link', array($this, 'affiliateLinkShortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}atp_affiliate_links (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            link_name varchar(255) NOT NULL,
            target_url longtext NOT NULL,
            affiliate_code varchar(100) NOT NULL UNIQUE,
            commission_rate float DEFAULT 0,
            clicks int DEFAULT 0,
            conversions int DEFAULT 0,
            revenue decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}atp_clicks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            affiliate_id mediumint(9) NOT NULL,
            click_time datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(100),
            user_agent longtext,
            PRIMARY KEY (id),
            FOREIGN KEY (affiliate_id) REFERENCES {$wpdb->prefix}atp_affiliate_links(id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    public function addAdminMenu() {
        add_menu_page(
            'AffiliateTrack Pro',
            'AffiliateTrack Pro',
            'manage_options',
            'affiliatetrack-dashboard',
            array($this, 'dashboardPage'),
            'dashicons-chart-line',
            25
        );
        
        add_submenu_page(
            'affiliatetrack-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'affiliatetrack-dashboard',
            array($this, 'dashboardPage')
        );
        
        add_submenu_page(
            'affiliatetrack-dashboard',
            'Manage Links',
            'Manage Links',
            'manage_options',
            'affiliatetrack-links',
            array($this, 'linksPage')
        );
        
        add_submenu_page(
            'affiliatetrack-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'affiliatetrack-settings',
            array($this, 'settingsPage')
        );
    }
    
    public function registerSettings() {
        register_setting('affiliatetrack-settings', 'atp_license_key');
        register_setting('affiliatetrack-settings', 'atp_enable_tracking');
    }
    
    public function dashboardPage() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $wpdb;
        $stats = $wpdb->get_row(
            "SELECT COUNT(*) as total_links, SUM(clicks) as total_clicks, SUM(conversions) as total_conversions, SUM(revenue) as total_revenue FROM {$wpdb->prefix}atp_affiliate_links"
        );
        
        echo '<div class="wrap" style="font-family: Arial, sans-serif; padding: 20px;">';
        echo '<h1>AffiliateTrack Pro Dashboard</h1>';
        echo '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">';
        echo '<div style="background: #f5f5f5; padding: 20px; border-radius: 5px;"><h3>Total Links</h3><p style="font-size: 24px; font-weight: bold;">' . ($stats->total_links ?? 0) . '</p></div>';
        echo '<div style="background: #f5f5f5; padding: 20px; border-radius: 5px;"><h3>Total Clicks</h3><p style="font-size: 24px; font-weight: bold;">' . ($stats->total_clicks ?? 0) . '</p></div>';
        echo '<div style="background: #f5f5f5; padding: 20px; border-radius: 5px;"><h3>Total Conversions</h3><p style="font-size: 24px; font-weight: bold;">' . ($stats->total_conversions ?? 0) . '</p></div>';
        echo '<div style="background: #f5f5f5; padding: 20px; border-radius: 5px;"><h3>Total Revenue</h3><p style="font-size: 24px; font-weight: bold;">$' . number_format($stats->total_revenue ?? 0, 2) . '</p></div>';
        echo '</div>';
        echo '</div>';
    }
    
    public function linksPage() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $wpdb;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_link'])) {
            $link_name = sanitize_text_field($_POST['link_name']);
            $target_url = esc_url_raw($_POST['target_url']);
            $commission_rate = floatval($_POST['commission_rate']);
            $affiliate_code = sanitize_text_field($_POST['affiliate_code']);
            
            $wpdb->insert(
                $wpdb->prefix . 'atp_affiliate_links',
                array(
                    'user_id' => get_current_user_id(),
                    'link_name' => $link_name,
                    'target_url' => $target_url,
                    'affiliate_code' => $affiliate_code,
                    'commission_rate' => $commission_rate
                ),
                array('%d', '%s', '%s', '%s', '%f')
            );
        }
        
        $links = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}atp_affiliate_links WHERE user_id = " . get_current_user_id()
        );
        
        echo '<div class="wrap" style="font-family: Arial, sans-serif; padding: 20px;">';
        echo '<h1>Manage Affiliate Links</h1>';
        echo '<form method="POST" style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">';
        echo '<h3>Add New Link</h3>';
        echo '<table style="width: 100%;">';
        echo '<tr><td><label>Link Name:</label><input type="text" name="link_name" required style="width: 100%; padding: 8px;"></td></tr>';
        echo '<tr><td><label>Target URL:</label><input type="url" name="target_url" required style="width: 100%; padding: 8px;"></td></tr>';
        echo '<tr><td><label>Affiliate Code:</label><input type="text" name="affiliate_code" required style="width: 100%; padding: 8px;"></td></tr>';
        echo '<tr><td><label>Commission Rate (%):</label><input type="number" name="commission_rate" step="0.01" required style="width: 100%; padding: 8px;"></td></tr>';
        echo '</table>';
        echo '<button type="submit" name="add_link" style="background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; margin-top: 10px;">Add Link</button>';
        echo '</form>';
        
        if ($links) {
            echo '<table style="width: 100%; border-collapse: collapse;">';
            echo '<thead style="background: #f5f5f5;"><tr><th style="padding: 10px; border: 1px solid #ddd;">Name</th><th style="padding: 10px; border: 1px solid #ddd;">Code</th><th style="padding: 10px; border: 1px solid #ddd;">Clicks</th><th style="padding: 10px; border: 1px solid #ddd;">Conversions</th><th style="padding: 10px; border: 1px solid #ddd;">Revenue</th></tr></thead>';
            echo '<tbody>';
            foreach ($links as $link) {
                echo '<tr><td style="padding: 10px; border: 1px solid #ddd;">' . $link->link_name . '</td>';
                echo '<td style="padding: 10px; border: 1px solid #ddd;">' . $link->affiliate_code . '</td>';
                echo '<td style="padding: 10px; border: 1px solid #ddd;">' . $link->clicks . '</td>';
                echo '<td style="padding: 10px; border: 1px solid #ddd;">' . $link->conversions . '</td>';
                echo '<td style="padding: 10px; border: 1px solid #ddd;">$' . number_format($link->revenue, 2) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }
    
    public function settingsPage() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        echo '<div class="wrap" style="font-family: Arial, sans-serif; padding: 20px;">';
        echo '<h1>AffiliateTrack Pro Settings</h1>';
        echo '<form method="POST" action="options.php" style="max-width: 600px;">';
        settings_fields('affiliatetrack-settings');
        
        echo '<table style="width: 100%;">';
        echo '<tr><td><label for="atp_enable_tracking">Enable Click Tracking:</label></td>';
        echo '<td><input type="checkbox" id="atp_enable_tracking" name="atp_enable_tracking" value="1" ' . (get_option('atp_enable_tracking') ? 'checked' : '') . '></td></tr>';
        echo '</table>';
        
        submit_button();
        echo '</form>';
        echo '</div>';
    }
    
    public function affiliateLinkShortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => '',
            'text' => 'Click here'
        ), $atts);
        
        if (!$atts['code']) {
            return '';
        }
        
        global $wpdb;
        $link = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}atp_affiliate_links WHERE affiliate_code = %s",
                $atts['code']
            )
        );
        
        if (!$link) {
            return '';
        }
        
        $tracking_url = add_query_arg('atp_track', $link->id, $link->target_url);
        return '<a href="' . esc_url($tracking_url) . '" target="_blank">' . esc_html($atts['text']) . '</a>';
    }
    
    public function trackAffiliateClick() {
        if (!isset($_GET['atp_track']) || !get_option('atp_enable_tracking')) {
            return;
        }
        
        global $wpdb;
        $affiliate_id = intval($_GET['atp_track']);
        
        $wpdb->insert(
            $wpdb->prefix . 'atp_clicks',
            array(
                'affiliate_id' => $affiliate_id,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
            ),
            array('%d', '%s', '%s')
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}atp_affiliate_links SET clicks = clicks + 1 WHERE id = %d",
                $affiliate_id
            )
        );
    }
}

AffiliateTrackPro::getInstance();
?>