<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and visualize revenue from ads, affiliate links, digital sales, and memberships.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/

if (!defined('ABSPATH')) exit;

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_revenue_data', array($this, 'save_revenue_data'));
        add_action('wp_ajax_get_revenue_data', array($this, 'get_revenue_data'));
    }

    public function add_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_dashboard'),
            'dashicons-chart-bar'
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_wp-revenue-tracker') return;
        wp_enqueue_script('jquery');
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-tracker-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label>Revenue Type: 
                    <select id="revenue_type">
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="digital_sales">Digital Sales</option>
                        <option value="memberships">Memberships</option>
                    </select>
                </label>
                <label>Amount: <input type="number" id="revenue_amount" step="0.01"></label>
                <label>Date: <input type="date" id="revenue_date"></label>
                <button id="save_revenue">Save Revenue</button>
            </div>
            <canvas id="revenue-chart" width="400" height="200"></canvas>
        </div>
        <?php
    }

    public function save_revenue_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $data = array(
            'type' => sanitize_text_field($_POST['type']),
            'amount' => floatval($_POST['amount']),
            'date' => sanitize_text_field($_POST['date'])
        );
        $wpdb->insert($table, $data);
        wp_die();
    }

    public function get_revenue_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY date ASC");
        wp_send_json($results);
    }

    public function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            date date NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

register_activation_hook(__FILE__, array(new WP_Revenue_Tracker, 'create_table'));
new WP_Revenue_Tracker();
?>