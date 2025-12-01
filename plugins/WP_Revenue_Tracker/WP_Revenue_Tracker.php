<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and analyze your WordPress site's revenue streams.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_save_revenue_data', array($this, 'save_revenue_data'));
        add_action('wp_ajax_get_revenue_data', array($this, 'get_revenue_data'));
        add_action('wp_ajax_nopriv_get_revenue_data', array($this, 'get_revenue_data'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_admin_page'),
            'dashicons-chart-bar',
            6
        );
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-app">
                <h2>Revenue Overview</h2>
                <div id="revenue-chart"></div>
                <form id="revenue-form">
                    <input type="hidden" name="action" value="save_revenue_data">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('save_revenue_nonce'); ?>">
                    <label>Revenue Source: <input type="text" name="source" required></label>
                    <label>Amount: <input type="number" name="amount" step="0.01" required></label>
                    <label>Date: <input type="date" name="date" required></label>
                    <button type="submit">Add Revenue</button>
                </form>
                <div id="revenue-list"></div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                function loadRevenueData() {
                    $.post(ajaxurl, {action: 'get_revenue_data'}, function(response) {
                        $('#revenue-list').html('<ul>' + response.map(item => '<li>' + item.source + ': $' + item.amount + ' (' + item.date + ')</li>').join('') + '</ul>');
                        // Simple chart (replace with real charting library for production)
                        $('#revenue-chart').html('<p>Chart placeholder: Use a charting library like Chart.js for visualization.</p>');
                    });
                }
                $('#revenue-form').on('submit', function(e) {
                    e.preventDefault();
                    $.post(ajaxurl, $(this).serialize(), function() {
                        loadRevenueData();
                        $('#revenue-form').reset();
                    });
                });
                loadRevenueData();
            });
        </script>
        <?php
    }

    public function save_revenue_data() {
        check_ajax_referer('save_revenue_nonce', 'nonce');
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);
        $revenue_data = get_option('wp_revenue_tracker_data', array());
        $revenue_data[] = array('source' => $source, 'amount' => $amount, 'date' => $date);
        update_option('wp_revenue_tracker_data', $revenue_data);
        wp_die();
    }

    public function get_revenue_data() {
        $revenue_data = get_option('wp_revenue_tracker_data', array());
        wp_die(json_encode($revenue_data));
    }
}

new WP_Revenue_Tracker();
