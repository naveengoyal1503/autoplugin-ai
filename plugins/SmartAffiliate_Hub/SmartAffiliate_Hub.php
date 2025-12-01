<?php
/*
Plugin Name: SmartAffiliate Hub
Plugin URI: https://smartaffiliatehub.com
Description: Advanced affiliate marketing management with link tracking, analytics, and optimization tools
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Hub.php
License: GPL-2.0+
Text Domain: smartaffiliate-hub
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SAH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SAH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SAH_VERSION', '1.0.0');

class SmartAffiliateHub {
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
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_shortcode('affiliate_link', array($this, 'affiliateLinkShortcode'));
        add_action('init', array($this, 'createDatabase'));
        register_activation_hook(__FILE__, array($this, 'activatePlugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivatePlugin'));
    }

    public function addAdminMenu() {
        add_menu_page(
            'SmartAffiliate Hub',
            'SmartAffiliate',
            'manage_options',
            'smartaffiliate-hub',
            array($this, 'renderDashboard'),
            'dashicons-chart-line',
            25
        );
        
        add_submenu_page(
            'smartaffiliate-hub',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'smartaffiliate-hub',
            array($this, 'renderDashboard')
        );
        
        add_submenu_page(
            'smartaffiliate-hub',
            'Links',
            'Affiliate Links',
            'manage_options',
            'sah-links',
            array($this, 'renderLinksPage')
        );
        
        add_submenu_page(
            'smartaffiliate-hub',
            'Analytics',
            'Analytics',
            'manage_options',
            'sah-analytics',
            array($this, 'renderAnalytics')
        );
        
        add_submenu_page(
            'smartaffiliate-hub',
            'Settings',
            'Settings',
            'manage_options',
            'sah-settings',
            array($this, 'renderSettings')
        );
    }

    public function registerSettings() {
        register_setting('sah-settings-group', 'sah_commission_rate');
        register_setting('sah-settings-group', 'sah_tracking_enabled');
        register_setting('sah-settings-group', 'sah_email_reports');
    }

    public function createDatabase() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $links_table = $wpdb->prefix . 'sah_affiliate_links';
        $clicks_table = $wpdb->prefix . 'sah_link_clicks';

        if ($wpdb->get_var("SHOW TABLES LIKE '$links_table'") !== $links_table) {
            $sql = "CREATE TABLE $links_table (
                id bigint(20) unsigned NOT NULL auto_increment,
                link_code varchar(50) NOT NULL unique,
                original_url longtext NOT NULL,
                short_url varchar(255),
                program_name varchar(255) NOT NULL,
                commission_rate float,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$clicks_table'") !== $clicks_table) {
            $sql = "CREATE TABLE $clicks_table (
                id bigint(20) unsigned NOT NULL auto_increment,
                link_id bigint(20) unsigned NOT NULL,
                ip_address varchar(45),
                user_agent longtext,
                referrer longtext,
                clicked_at datetime DEFAULT CURRENT_TIMESTAMP,
                converted int(1) DEFAULT 0,
                PRIMARY KEY (id),
                KEY link_id (link_id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    public function affiliateLinkShortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => '',
            'text' => 'Click here',
            'class' => 'sah-affiliate-link'
        ), $atts);

        global $wpdb;
        $links_table = $wpdb->prefix . 'sah_affiliate_links';
        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $links_table WHERE link_code = %s",
            $atts['code']
        ));

        if (!$link) {
            return '';
        }

        $tracking_id = wp_generate_uuid4();
        update_option('sah_last_tracking_' . $tracking_id, $link->id);

        $redirect_url = add_query_arg('sah_tracking', $tracking_id, home_url('/'));

        return sprintf(
            '<a href="%s" class="%s" data-sah-link="%d">%s</a>',
            esc_url($redirect_url),
            esc_attr($atts['class']),
            intval($link->id),
            esc_html($atts['text'])
        );
    }

    public function enqueueScripts() {
        if (is_admin()) {
            wp_enqueue_style('sah-admin-style', SAH_PLUGIN_URL . 'admin-style.css', array(), SAH_VERSION);
        }
    }

    public function renderDashboard() {
        global $wpdb;
        $links_table = $wpdb->prefix . 'sah_affiliate_links';
        $clicks_table = $wpdb->prefix . 'sah_link_clicks';
        
        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM $links_table");
        $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM $clicks_table");
        $this_month_clicks = $wpdb->get_var(
            "SELECT COUNT(*) FROM $clicks_table WHERE MONTH(clicked_at) = MONTH(CURDATE()) AND YEAR(clicked_at) = YEAR(CURDATE())"
        );
        
        echo '<div class="wrap">';
        echo '<h1>SmartAffiliate Hub Dashboard</h1>';
        echo '<div class="sah-stats-container">';
        echo '<div class="sah-stat-box"><h3>Total Links</h3><p>' . intval($total_links) . '</p></div>';
        echo '<div class="sah-stat-box"><h3>Total Clicks</h3><p>' . intval($total_clicks) . '</p></div>';
        echo '<div class="sah-stat-box"><h3>Clicks This Month</h3><p>' . intval($this_month_clicks) . '</p></div>';
        echo '</div>';
        echo '</div>';
    }

    public function renderLinksPage() {
        global $wpdb;
        $links_table = $wpdb->prefix . 'sah_affiliate_links';
        
        if (isset($_POST['action']) && $_POST['action'] === 'add_link' && check_admin_referer('sah_add_link')) {
            $link_code = sanitize_text_field($_POST['link_code']);
            $original_url = esc_url_raw($_POST['original_url']);
            $program_name = sanitize_text_field($_POST['program_name']);
            $commission_rate = floatval($_POST['commission_rate']);
            
            $wpdb->insert($links_table, array(
                'link_code' => $link_code,
                'original_url' => $original_url,
                'program_name' => $program_name,
                'commission_rate' => $commission_rate
            ));
        }
        
        $links = $wpdb->get_results("SELECT * FROM $links_table ORDER BY created_at DESC");
        
        echo '<div class="wrap">';
        echo '<h1>Affiliate Links</h1>';
        echo '<form method="post" class="sah-form">';
        wp_nonce_field('sah_add_link');
        echo '<input type="hidden" name="action" value="add_link">';
        echo '<table class="form-table">';
        echo '<tr><th>Link Code</th><td><input type="text" name="link_code" required></td></tr>';
        echo '<tr><th>Original URL</th><td><input type="url" name="original_url" required></td></tr>';
        echo '<tr><th>Program</th><td><input type="text" name="program_name" required></td></tr>';
        echo '<tr><th>Commission %</th><td><input type="number" name="commission_rate" step="0.01" min="0"></td></tr>';
        echo '</table>';
        echo '<p><input type="submit" value="Add Link" class="button button-primary"></p>';
        echo '</form>';
        
        echo '<h2>Your Affiliate Links</h2>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>Code</th><th>Program</th><th>Commission</th><th>Added</th></tr></thead>';
        echo '<tbody>';
        foreach ($links as $link) {
            echo '<tr>';
            echo '<td><code>' . esc_html($link->link_code) . '</code></td>';
            echo '<td>' . esc_html($link->program_name) . '</td>';
            echo '<td>' . floatval($link->commission_rate) . '%</td>';
            echo '<td>' . esc_html($link->created_at) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    public function renderAnalytics() {
        global $wpdb;
        $links_table = $wpdb->prefix . 'sah_affiliate_links';
        $clicks_table = $wpdb->prefix . 'sah_link_clicks';
        
        $results = $wpdb->get_results(
            "SELECT l.link_code, l.program_name, COUNT(c.id) as click_count 
             FROM $links_table l 
             LEFT JOIN $clicks_table c ON l.id = c.link_id 
             GROUP BY l.id 
             ORDER BY click_count DESC"
        );
        
        echo '<div class="wrap">';
        echo '<h1>Analytics</h1>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>Link Code</th><th>Program</th><th>Clicks</th></tr></thead>';
        echo '<tbody>';
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td><code>' . esc_html($row->link_code) . '</code></td>';
            echo '<td>' . esc_html($row->program_name) . '</td>';
            echo '<td>' . intval($row->click_count) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    public function renderSettings() {
        echo '<div class="wrap">';
        echo '<h1>SmartAffiliate Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('sah-settings-group');
        echo '<table class="form-table">';
        echo '<tr><th>Commission Rate (%)</th><td><input type="number" name="sah_commission_rate" value="' . esc_attr(get_option('sah_commission_rate', 10)) . '" step="0.01"></td></tr>';
        echo '<tr><th>Enable Tracking</th><td><input type="checkbox" name="sah_tracking_enabled" value="1" ' . checked(get_option('sah_tracking_enabled'), 1) . '></td></tr>';
        echo '<tr><th>Email Reports</th><td><input type="email" name="sah_email_reports" value="' . esc_attr(get_option('sah_email_reports')) . '"></td></tr>';
        echo '</table>';
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function activatePlugin() {
        $this->createDatabase();
        add_option('sah_commission_rate', 10);
        add_option('sah_tracking_enabled', 1);
    }

    public function deactivatePlugin() {
        // Cleanup if needed
    }
}

SmartAffiliateHub::getInstance();
?>