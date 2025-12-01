<?php
/*
Plugin Name: SmartAffiliate Dashboard
Plugin URI: https://smartaffiliate.local
Description: Comprehensive affiliate link management with analytics and optimization
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Dashboard.php
License: GPL v2 or later
Text Domain: smartaffiliate-dashboard
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SMARTAFFILIATE_VERSION', '1.0.0');
define('SMARTAFFILIATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMARTAFFILIATE_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartAffiliatePlugin {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendAssets'));
        add_action('rest_api_init', array($this, 'registerRestRoutes'));
        add_shortcode('smartaffiliate_link', array($this, 'renderAffiliateLink'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            original_url text NOT NULL,
            short_code varchar(50) UNIQUE NOT NULL,
            clicks bigint(20) DEFAULT 0,
            conversions bigint(20) DEFAULT 0,
            commission_rate float DEFAULT 0,
            category varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('smartaffiliate_version', SMARTAFFILIATE_VERSION);
    }

    public function deactivate() {
        delete_option('smartaffiliate_version');
    }

    public function addAdminMenu() {
        add_menu_page(
            'SmartAffiliate Dashboard',
            'SmartAffiliate',
            'manage_options',
            'smartaffiliate-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-link',
            25
        );

        add_submenu_page(
            'smartaffiliate-dashboard',
            'Manage Links',
            'Manage Links',
            'manage_options',
            'smartaffiliate-links',
            array($this, 'renderLinksPage')
        );

        add_submenu_page(
            'smartaffiliate-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'smartaffiliate-analytics',
            array($this, 'renderAnalyticsPage')
        );
    }

    public function enqueueAdminAssets($hook) {
        if (strpos($hook, 'smartaffiliate') === false) {
            return;
        }
        wp_enqueue_style('smartaffiliate-admin', SMARTAFFILIATE_PLUGIN_URL . 'admin/css/style.css', array(), SMARTAFFILIATE_VERSION);
        wp_enqueue_script('smartaffiliate-admin', SMARTAFFILIATE_PLUGIN_URL . 'admin/js/script.js', array('jquery'), SMARTAFFILIATE_VERSION);
        wp_localize_script('smartaffiliate-admin', 'smartaffiliateData', array(
            'nonce' => wp_create_nonce('smartaffiliate_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }

    public function enqueueFrontendAssets() {
        wp_enqueue_script('smartaffiliate-tracking', SMARTAFFILIATE_PLUGIN_URL . 'frontend/js/tracking.js', array(), SMARTAFFILIATE_VERSION, true);
        wp_localize_script('smartaffiliate-tracking', 'smartaffiliateTracking', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smartaffiliate_tracking')
        ));
    }

    public function registerRestRoutes() {
        register_rest_route('smartaffiliate/v1', '/links', array(
            'methods' => 'GET',
            'callback' => array($this, 'getLinksCallback'),
            'permission_callback' => array($this, 'permissionCallback')
        ));

        register_rest_route('smartaffiliate/v1', '/links', array(
            'methods' => 'POST',
            'callback' => array($this, 'createLinkCallback'),
            'permission_callback' => array($this, 'permissionCallback')
        ));

        register_rest_route('smartaffiliate/v1', '/links/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'updateLinkCallback'),
            'permission_callback' => array($this, 'permissionCallback')
        ));

        register_rest_route('smartaffiliate/v1', '/links/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'deleteLinkCallback'),
            'permission_callback' => array($this, 'permissionCallback')
        ));

        register_rest_route('smartaffiliate/v1', '/analytics', array(
            'methods' => 'GET',
            'callback' => array($this, 'getAnalyticsCallback'),
            'permission_callback' => array($this, 'permissionCallback')
        ));

        register_rest_route('smartaffiliate/v1', '/track-click', array(
            'methods' => 'POST',
            'callback' => array($this, 'trackClickCallback'),
            'permission_callback' => '__return_true'
        ));
    }

    public function permissionCallback() {
        return current_user_can('manage_options');
    }

    public function getLinksCallback(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        return rest_ensure_response($links);
    }

    public function createLinkCallback(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        
        $params = $request->get_json_params();
        $short_code = sanitize_text_field($params['short_code'] ?? bin2hex(random_bytes(6)));
        $original_url = esc_url($params['original_url']);
        $name = sanitize_text_field($params['name']);
        $category = sanitize_text_field($params['category'] ?? '');
        $commission_rate = floatval($params['commission_rate'] ?? 0);

        $result = $wpdb->insert($table_name, array(
            'name' => $name,
            'original_url' => $original_url,
            'short_code' => $short_code,
            'category' => $category,
            'commission_rate' => $commission_rate
        ));

        if ($result) {
            return rest_ensure_response(array('success' => true, 'id' => $wpdb->insert_id, 'short_code' => $short_code));
        }
        return new WP_Error('creation_failed', 'Could not create link', array('status' => 500));
    }

    public function updateLinkCallback(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        $id = intval($request['id']);
        $params = $request->get_json_params();

        $update_data = array();
        if (isset($params['name'])) {
            $update_data['name'] = sanitize_text_field($params['name']);
        }
        if (isset($params['original_url'])) {
            $update_data['original_url'] = esc_url($params['original_url']);
        }
        if (isset($params['category'])) {
            $update_data['category'] = sanitize_text_field($params['category']);
        }
        if (isset($params['commission_rate'])) {
            $update_data['commission_rate'] = floatval($params['commission_rate']);
        }

        $result = $wpdb->update($table_name, $update_data, array('id' => $id));
        return rest_ensure_response(array('success' => $result !== false));
    }

    public function deleteLinkCallback(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        $id = intval($request['id']);
        $result = $wpdb->delete($table_name, array('id' => $id));
        return rest_ensure_response(array('success' => $result !== false));
    }

    public function getAnalyticsCallback(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM $table_name");
        $total_conversions = $wpdb->get_var("SELECT SUM(conversions) FROM $table_name");
        $links_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        $top_links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY clicks DESC LIMIT 5");

        return rest_ensure_response(array(
            'total_clicks' => intval($total_clicks),
            'total_conversions' => intval($total_conversions),
            'links_count' => intval($links_count),
            'conversion_rate' => $total_clicks > 0 ? round((intval($total_conversions) / intval($total_clicks)) * 100, 2) : 0,
            'top_links' => $top_links
        ));
    }

    public function trackClickCallback(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        
        $params = $request->get_json_params();
        $short_code = sanitize_text_field($params['short_code']);
        
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE short_code = %s", $short_code));
        
        if ($link) {
            $wpdb->update($table_name, array('clicks' => $link->clicks + 1), array('id' => $link->id));
            return rest_ensure_response(array('success' => true, 'redirect_url' => $link->original_url));
        }
        
        return new WP_Error('link_not_found', 'Link not found', array('status' => 404));
    }

    public function renderDashboard() {
        ?>
        <div class="wrap smartaffiliate-dashboard">
            <h1>SmartAffiliate Dashboard</h1>
            <div id="smartaffiliate-root"></div>
        </div>
        <?php
    }

    public function renderLinksPage() {
        ?>
        <div class="wrap smartaffiliate-links">
            <h1>Manage Affiliate Links</h1>
            <button id="add-new-link" class="button button-primary">Add New Link</button>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Link Name</th>
                        <th>Short Code</th>
                        <th>Category</th>
                        <th>Clicks</th>
                        <th>Commission Rate</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="smartaffiliate-links-list"></tbody>
            </table>
        </div>
        <?php
    }

    public function renderAnalyticsPage() {
        ?>
        <div class="wrap smartaffiliate-analytics">
            <h1>Analytics</h1>
            <div class="smartaffiliate-stats">
                <div class="stat-box">
                    <h3>Total Clicks</h3>
                    <p id="total-clicks">--</p>
                </div>
                <div class="stat-box">
                    <h3>Total Conversions</h3>
                    <p id="total-conversions">--</p>
                </div>
                <div class="stat-box">
                    <h3>Conversion Rate</h3>
                    <p id="conversion-rate">--</p>
                </div>
            </div>
            <div id="smartaffiliate-chart"></div>
        </div>
        <?php
    }

    public function renderAffiliateLink($atts) {
        $atts = shortcode_atts(array(
            'code' => ''
        ), $atts);

        if (empty($atts['code'])) {
            return '';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE short_code = %s", sanitize_text_field($atts['code'])));

        if (!$link) {
            return '';
        }

        $tracking_url = admin_url('admin-ajax.php') . '?action=smartaffiliate_redirect&code=' . urlencode($link->short_code);
        return '<a href="' . esc_url($tracking_url) . '" class="smartaffiliate-link" data-code="' . esc_attr($link->short_code) . '">' . esc_html($link->name) . '</a>';
    }
}

SmartAffiliatePlugin::getInstance();
?>