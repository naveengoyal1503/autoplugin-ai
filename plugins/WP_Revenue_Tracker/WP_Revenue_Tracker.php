<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and visualize revenue from ads, affiliate links, digital sales, and donations.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_revenue', array($this, 'save_revenue'));
        add_action('wp_ajax_get_revenue_data', array($this, 'get_revenue_data'));
    }

    public function add_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_wp-revenue-tracker') return;
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'js/tracker.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-tracker-js', 'wpRevenueAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_nonce')
        ));
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'css/tracker.css', array(), '1.0');
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label>Source: <input type="text" id="source" placeholder="e.g., AdSense, Affiliate, Donation"></label>
                <label>Amount: <input type="number" id="amount" step="0.01" placeholder="0.00"></label>
                <label>Date: <input type="date" id="date"></label>
                <button id="save-revenue">Add Revenue</button>
            </div>
            <div id="revenue-chart-container">
                <canvas id="revenue-chart"></canvas>
            </div>
            <div id="revenue-table-container">
                <table id="revenue-table">
                    <thead>
                        <tr><th>Date</th><th>Source</th><th>Amount</th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function save_revenue() {
        check_ajax_referer('wp_revenue_nonce', 'nonce');
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);
        $data = array(
            'source' => $source,
            'amount' => $amount,
            'date' => $date
        );
        $revenue = get_option('wp_revenue_data', array());
        $revenue[] = $data;
        update_option('wp_revenue_data', $revenue);
        wp_die();
    }

    public function get_revenue_data() {
        $revenue = get_option('wp_revenue_data', array());
        wp_send_json($revenue);
    }
}

new WP_Revenue_Tracker();
