<?php
/*
Plugin Name: Revenue Optimizer Pro
Plugin URI: https://example.com/revenue-optimizer
Description: All-in-one monetization management and optimization platform
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Revenue_Optimizer_Pro.php
License: GPL v2 or later
Text Domain: revenue-optimizer
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

define('ROP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ROP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ROP_PLUGIN_VERSION', '1.0.0');

class RevenueOptimizerPro {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('init', array($this, 'register_post_types'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $revenue_table = $wpdb->prefix . 'rop_revenue_streams';
        $analytics_table = $wpdb->prefix . 'rop_analytics';

        $sql1 = "CREATE TABLE IF NOT EXISTS $revenue_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            name varchar(200) NOT NULL,
            description text,
            active tinyint(1) DEFAULT 1,
            config longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql2 = "CREATE TABLE IF NOT EXISTS $analytics_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            stream_id mediumint(9) NOT NULL,
            date date NOT NULL,
            impressions int DEFAULT 0,
            clicks int DEFAULT 0,
            conversions int DEFAULT 0,
            revenue decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY stream_date (stream_id, date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);

        update_option('rop_plugin_version', ROP_PLUGIN_VERSION);
    }

    public function deactivate_plugin() {
        // Cleanup if needed
    }

    public function register_post_types() {
        register_post_type('rop_revenue_goal', array(
            'labels' => array(
                'name' => 'Revenue Goals',
                'singular_name' => 'Revenue Goal',
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-chart-line',
        ));
    }

    public function register_rest_routes() {
        register_rest_route('rop/v1', '/revenue-streams', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_revenue_streams'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route('rop/v1', '/analytics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_analytics'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route('rop/v1', '/revenue-streams', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_revenue_stream'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }

    public function check_permission() {
        return current_user_can('manage_options');
    }

    public function get_revenue_streams() {
        global $wpdb;
        $table = $wpdb->prefix . 'rop_revenue_streams';
        $streams = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        return rest_ensure_response($streams);
    }

    public function get_analytics($request) {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'rop_analytics';
        $days = $request->get_param('days') ?: 30;
        $start_date = date('Y-m-d', strtotime("-$days days"));

        $analytics = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $analytics_table WHERE date >= %s ORDER BY date DESC",
            $start_date
        ));

        $summary = $wpdb->get_row(
            "SELECT SUM(revenue) as total_revenue, SUM(conversions) as total_conversions FROM $analytics_table WHERE date >= '$start_date'"
        );

        return rest_ensure_response(array(
            'analytics' => $analytics,
            'summary' => $summary,
        ));
    }

    public function create_revenue_stream($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'rop_revenue_streams';

        $params = $request->get_json_params();
        $type = sanitize_text_field($params['type'] ?? '');
        $name = sanitize_text_field($params['name'] ?? '');
        $description = wp_kses_post($params['description'] ?? '');
        $config = wp_json_encode($params['config'] ?? array());

        if (empty($type) || empty($name)) {
            return new WP_Error('invalid_params', 'Type and name are required', array('status' => 400));
        }

        $result = $wpdb->insert(
            $table,
            array(
                'type' => $type,
                'name' => $name,
                'description' => $description,
                'config' => $config,
            ),
            array('%s', '%s', '%s', '%s')
        );

        if ($result) {
            return rest_ensure_response(array('id' => $wpdb->insert_id, 'success' => true));
        }

        return new WP_Error('db_error', 'Failed to create revenue stream', array('status' => 500));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Optimizer Pro',
            'Revenue Optimizer',
            'manage_options',
            'rop-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            25
        );

        add_submenu_page(
            'rop-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'rop-dashboard',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'rop-dashboard',
            'Revenue Streams',
            'Revenue Streams',
            'manage_options',
            'rop-streams',
            array($this, 'render_streams')
        );

        add_submenu_page(
            'rop-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'rop-analytics',
            array($this, 'render_analytics')
        );
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>Revenue Optimizer Pro - Dashboard</h1>
            <div id="rop-dashboard-app"></div>
        </div>
        <?php
    }

    public function render_streams() {
        ?>
        <div class="wrap">
            <h1>Revenue Streams</h1>
            <div id="rop-streams-app"></div>
        </div>
        <?php
    }

    public function render_analytics() {
        ?>
        <div class="wrap">
            <h1>Analytics</h1>
            <div id="rop-analytics-app"></div>
        </div>
        <?php
    }

    public function enqueue_admin_scripts() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'rop-') !== 0) {
            return;
        }

        wp_enqueue_style('rop-admin', ROP_PLUGIN_URL . 'assets/admin.css', array(), ROP_PLUGIN_VERSION);
        wp_enqueue_script('rop-admin', ROP_PLUGIN_URL . 'assets/admin.js', array('wp-api'), ROP_PLUGIN_VERSION, true);

        wp_localize_script('rop-admin', 'ropData', array(
            'rest_url' => rest_url('rop/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('rop-frontend', ROP_PLUGIN_URL . 'assets/frontend.css', array(), ROP_PLUGIN_VERSION);
    }
}

// Initialize plugin
RevenueOptimizerPro::getInstance();

// Shortcode for revenue goals display
add_shortcode('rop_revenue_goal', function($atts) {
    $atts = shortcode_atts(array(
        'goal_id' => 0,
    ), $atts);

    if (!$atts['goal_id']) {
        return 'Invalid goal ID';
    }

    $goal = get_post($atts['goal_id']);
    if (!$goal) {
        return 'Goal not found';
    }

    ob_start();
    ?>
    <div class="rop-revenue-goal">
        <h3><?php echo esc_html($goal->post_title); ?></h3>
        <div class="rop-goal-content"><?php echo wp_kses_post($goal->post_content); ?></div>
    </div>
    <?php
    return ob_get_clean();
});
?>