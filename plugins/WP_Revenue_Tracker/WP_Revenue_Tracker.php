/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track and visualize revenue from ads, affiliate links, digital products, and memberships.
 * Version: 1.0
 * Author: Your Company
 */

define('WP_REVENUE_TRACKER_VERSION', '1.0');
define('WP_REVENUE_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_revenue_data', array($this, 'save_revenue_data'));
        add_action('wp_ajax_get_revenue_data', array($this, 'get_revenue_data'));
        add_action('init', array($this, 'create_revenue_table'));
    }

    public function create_revenue_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_dashboard'),
            'dashicons-chart-bar',
            6
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook != 'toplevel_page_wp-revenue-tracker') {
            return;
        }
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), WP_REVENUE_TRACKER_VERSION, true);
        wp_localize_script('wp-revenue-tracker-js', 'wp_revenue_tracker_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_tracker_nonce')
        ));
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), WP_REVENUE_TRACKER_VERSION);
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label for="source">Source:</label>
                <select id="source">
                    <option value="ads">Ads</option>
                    <option value="affiliate">Affiliate</option>
                    <option value="products">Digital Products</option>
                    <option value="memberships">Memberships</option>
                </select>
                <label for="amount">Amount:</label>
                <input type="number" id="amount" step="0.01" placeholder="0.00">
                <button id="save-revenue">Save Revenue</button>
            </div>
            <canvas id="revenue-chart" width="400" height="200"></canvas>
        </div>
        <?php
    }

    public function save_revenue_data() {
        check_ajax_referer('wp_revenue_tracker_nonce', 'nonce');
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $wpdb->insert($table_name, array(
            'source' => $source,
            'amount' => $amount
        ));
        wp_die();
    }

    public function get_revenue_data() {
        check_ajax_referer('wp_revenue_tracker_nonce', 'nonce');
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $results = $wpdb->get_results("SELECT source, SUM(amount) as total FROM $table_name GROUP BY source");
        wp_send_json($results);
    }
}

new WP_Revenue_Tracker();
