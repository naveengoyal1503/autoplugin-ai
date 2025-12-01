<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and visualize revenue from ads, affiliate links, and digital sales.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/

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
            'dashicons-chart-bar',
            6
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_wp-revenue-tracker') return;
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-tracker-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), '1.0');
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label>Source: <input type="text" id="source" placeholder="e.g., AdSense, Affiliate, WooCommerce"></label><br>
                <label>Amount: <input type="number" id="amount" step="0.01" placeholder="0.00"></label><br>
                <label>Date: <input type="date" id="date"></label><br>
                <button id="save-revenue">Save Revenue</button>
            </div>
            <canvas id="revenue-chart" width="400" height="200"></canvas>
        </div>
        <?php
    }

    public function save_revenue_data() {
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);
        $data = array('source' => $source, 'amount' => $amount, 'date' => $date);
        $existing = get_option('wp_revenue_tracker_data', array());
        $existing[] = $data;
        update_option('wp_revenue_tracker_data', $existing);
        wp_die('success');
    }

    public function get_revenue_data() {
        $data = get_option('wp_revenue_tracker_data', array());
        wp_die(json_encode($data));
    }
}

new WP_Revenue_Tracker();
?>