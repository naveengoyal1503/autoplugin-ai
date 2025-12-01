<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and visualize revenue from affiliate links, ads, digital products, and sponsored content.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_revenue', array($this, 'save_revenue'));
        add_action('wp_ajax_get_revenue_data', array($this, 'get_revenue_data'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_admin_page'),
            'dashicons-chart-bar'
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_wp-revenue-tracker') {
            return;
        }
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery', 'chart-js'), '1.0', true);
        wp_localize_script('wp-revenue-tracker-js', 'wp_revenue_tracker_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_tracker_nonce')
        ));
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label>Source: <input type="text" id="source" placeholder="e.g., Affiliate, AdSense, Sponsored" /></label><br>
                <label>Amount: <input type="number" id="amount" step="0.01" /></label><br>
                <label>Date: <input type="date" id="date" /></label><br>
                <button id="save-revenue">Save Revenue</button>
            </div>
            <div id="revenue-chart-container" style="width: 80%; margin-top: 20px;">
                <canvas id="revenue-chart"></canvas>
            </div>
        </div>
        <?php
    }

    public function save_revenue() {
        check_ajax_referer('wp_revenue_tracker_nonce', 'nonce');

        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);

        $revenue_data = get_option('wp_revenue_tracker_data', array());
        $revenue_data[] = array(
            'source' => $source,
            'amount' => $amount,
            'date' => $date
        );
        update_option('wp_revenue_tracker_data', $revenue_data);

        wp_die();
    }

    public function get_revenue_data() {
        check_ajax_referer('wp_revenue_tracker_nonce', 'nonce');

        $revenue_data = get_option('wp_revenue_tracker_data', array());
        wp_send_json($revenue_data);
    }
}

new WP_Revenue_Tracker();
