<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and visualize revenue from ads, affiliate links, and digital product sales.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_revenue_data', array($this, 'save_revenue_data'));
        add_action('wp_ajax_get_revenue_data', array($this, 'get_revenue_data'));
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
        if ($hook !== 'toplevel_page_wp-revenue-tracker') {
            return;
        }
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'js/revenue-tracker.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-tracker-js', 'wp_revenue_tracker_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'css/revenue-tracker.css');
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label>Date: <input type="date" id="revenue-date"></label>
                <label>Source: 
                    <select id="revenue-source">
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="products">Digital Products</option>
                    </select>
                </label>
                <label>Amount: <input type="number" id="revenue-amount" step="0.01"></label>
                <button onclick="saveRevenue()">Add Revenue</button>
            </div>
            <canvas id="revenue-chart" width="400" height="200"></canvas>
        </div>
        <?php
    }

    public function save_revenue_data() {
        $date = sanitize_text_field($_POST['date']);
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $data = get_option('wp_revenue_tracker_data', array());
        $data[] = array('date' => $date, 'source' => $source, 'amount' => $amount);
        update_option('wp_revenue_tracker_data', $data);
        wp_die();
    }

    public function get_revenue_data() {
        $data = get_option('wp_revenue_tracker_data', array());
        wp_send_json($data);
    }
}

new WP_Revenue_Tracker();
?>