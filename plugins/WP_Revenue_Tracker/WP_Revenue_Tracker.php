/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track and visualize revenue from ads, affiliate links, digital products, and sponsored content.
 * Version: 1.0
 * Author: WP Dev Team
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_revenue', array($this, 'save_revenue'));
        add_action('wp_ajax_get_revenue_data', array($this, 'get_revenue_data'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_menu() {
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
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'js/revenue-tracker.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-tracker-js', 'wpRevenueTracker', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_tracker_nonce')
        ));
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'css/revenue-tracker.css', array(), '1.0');
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label>Source: <input type="text" id="source" placeholder="e.g., AdSense, Affiliate, Product Sales"></label>
                <label>Amount: <input type="number" id="amount" step="0.01" placeholder="0.00"></label>
                <button id="save-revenue">Add Revenue</button>
            </div>
            <div id="revenue-chart-container">
                <canvas id="revenue-chart"></canvas>
            </div>
            <div id="revenue-table">
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="revenue-data-body"></tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function save_revenue() {
        check_ajax_referer('wp_revenue_tracker_nonce', 'nonce');
        global $wpdb;
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = current_time('mysql');
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $wpdb->insert(
            $table_name,
            array(
                'source' => $source,
                'amount' => $amount,
                'date' => $date
            )
        );
        wp_die();
    }

    public function get_revenue_data() {
        check_ajax_referer('wp_revenue_tracker_nonce', 'nonce');
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $results = $wpdb->get_results("SELECT source, amount, date FROM $table_name ORDER BY date DESC LIMIT 100");
        wp_send_json($results);
    }
}

new WP_Revenue_Tracker();
