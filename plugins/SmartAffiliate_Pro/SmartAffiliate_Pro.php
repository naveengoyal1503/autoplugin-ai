<?php
/*
Plugin Name: SmartAffiliate Pro
Plugin URI: https://smartaffiliatepro.com
Description: Intelligent affiliate marketing management with real-time analytics and performance tracking
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Pro.php
License: GPL v2 or later
Text Domain: smartaffiliate-pro
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SMARTAFFILIATE_VERSION', '1.0.0');
define('SMARTAFFILIATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMARTAFFILIATE_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartAffiliatePro {
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
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
        add_shortcode('affiliate_link', array($this, 'handleAffiliateShortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendAssets'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function addAdminMenu() {
        add_menu_page(
            'SmartAffiliate Pro',
            'SmartAffiliate Pro',
            'manage_options',
            'smartaffiliate-pro',
            array($this, 'renderDashboard'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'smartaffiliate-pro',
            'Manage Links',
            'Manage Links',
            'manage_options',
            'smartaffiliate-links',
            array($this, 'renderLinksPage')
        );

        add_submenu_page(
            'smartaffiliate-pro',
            'Analytics',
            'Analytics',
            'manage_options',
            'smartaffiliate-analytics',
            array($this, 'renderAnalyticsPage')
        );

        add_submenu_page(
            'smartaffiliate-pro',
            'Settings',
            'Settings',
            'manage_options',
            'smartaffiliate-settings',
            array($this, 'renderSettingsPage')
        );
    }

    public function renderDashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM {$table_name}");
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Pro Dashboard</h1>
            <div class="sap-dashboard">
                <div class="sap-stat-box">
                    <h3>Total Affiliate Links</h3>
                    <p class="sap-stat-number"><?php echo intval($total_links); ?></p>
                </div>
                <div class="sap-stat-box">
                    <h3>Total Clicks</h3>
                    <p class="sap-stat-number"><?php echo intval($total_clicks); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function renderLinksPage() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] === 'add' && isset($_POST['affiliate_url'])) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'link_name' => sanitize_text_field($_POST['link_name']),
                        'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                        'short_code' => sanitize_title($_POST['link_name']),
                        'created_at' => current_time('mysql')
                    )
                );
            }
        }

        $links = $wpdb->get_results("SELECT * FROM {$table_name}");
        ?>
        <div class="wrap">
            <h1>Manage Affiliate Links</h1>
            <form method="post" class="sap-form">
                <table class="form-table">
                    <tr>
                        <th><label for="link_name">Link Name</label></th>
                        <td><input type="text" id="link_name" name="link_name" required></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_url">Affiliate URL</label></th>
                        <td><input type="url" id="affiliate_url" name="affiliate_url" required></td>
                    </tr>
                </table>
                <input type="hidden" name="action" value="add">
                <p class="submit"><input type="submit" class="button button-primary" value="Add Link"></p>
            </form>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Link Name</th>
                        <th>Clicks</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td><?php echo esc_html($link->link_name); ?></td>
                            <td><?php echo intval($link->clicks); ?></td>
                            <td><?php echo esc_html($link->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderAnalyticsPage() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        $top_links = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY clicks DESC LIMIT 10");
        ?>
        <div class="wrap">
            <h1>Analytics</h1>
            <h2>Top Performing Links</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Link</th>
                        <th>Clicks</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_links as $link): ?>
                        <tr>
                            <td><?php echo esc_html($link->link_name); ?></td>
                            <td><?php echo intval($link->clicks); ?></td>
                            <td><div class="sap-progress" style="width: <?php echo (intval($link->clicks) / 100) * 10; ?>%;"></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderSettingsPage() {
        ?>
        <div class="wrap">
            <h1>Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="tracking_enabled">Enable Click Tracking</label></th>
                        <td><input type="checkbox" id="tracking_enabled" name="tracking_enabled" checked></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" class="button button-primary" value="Save Settings"></p>
            </form>
        </div>
        <?php
    }

    public function handleAffiliateShortcode($atts) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        $atts = shortcode_atts(array('name' => ''), $atts);
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE link_name = %s", $atts['name']));

        if ($link) {
            $wpdb->update($table_name, array('clicks' => $link->clicks + 1), array('id' => $link->id));
            return '<a href="' . esc_url($link->affiliate_url) . '" class="sap-affiliate-link" target="_blank" rel="noopener noreferrer">' . esc_html($link->link_name) . '</a>';
        }
        return '';
    }

    public function enqueueAdminAssets() {
        wp_enqueue_style('smartaffiliate-admin', SMARTAFFILIATE_PLUGIN_URL . 'css/admin.css', array(), SMARTAFFILIATE_VERSION);
    }

    public function enqueueFrontendAssets() {
        wp_enqueue_style('smartaffiliate-frontend', SMARTAFFILIATE_PLUGIN_URL . 'css/frontend.css', array(), SMARTAFFILIATE_VERSION);
    }

    private function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'smartaffiliate_links';

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_name varchar(255) NOT NULL,
            affiliate_url text NOT NULL,
            short_code varchar(100) NOT NULL,
            clicks int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY short_code (short_code)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->createTables();
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

SmartAffiliatePro::getInstance();
?>