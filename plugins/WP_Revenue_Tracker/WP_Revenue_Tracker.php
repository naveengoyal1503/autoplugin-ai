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
            'dashicons-chart-bar'
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_wp-revenue-tracker') return;
        wp_enqueue_script('jquery');
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label>Revenue Type: 
                    <select id="revenue-type">
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="products">Products</option>
                    </select>
                </label>
                <label>Amount: <input type="number" id="revenue-amount" step="0.01" /></label>
                <button onclick="saveRevenue()">Add Revenue</button>
            </div>
            <canvas id="revenue-chart" width="400" height="200"></canvas>
        </div>
        <script>
            function saveRevenue() {
                const type = document.getElementById('revenue-type').value;
                const amount = document.getElementById('revenue-amount').value;
                jQuery.post(ajaxurl, {
                    action: 'save_revenue',
                    type: type,
                    amount: amount
                }, function() {
                    loadRevenueChart();
                });
            }
            function loadRevenueChart() {
                jQuery.post(ajaxurl, {
                    action: 'get_revenue_data'
                }, function(data) {
                    const ctx = document.getElementById('revenue-chart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Revenue ($)',
                                data: data.amounts,
                                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
                            }]
                        }
                    });
                });
            }
            jQuery(document).ready(function() {
                loadRevenueChart();
            });
        </script>
        <?php
    }

    public function save_revenue() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $type = sanitize_text_field($_POST['type']);
        $amount = floatval($_POST['amount']);
        $data = get_option('wp_revenue_data', array());
        $data[] = array('type' => $type, 'amount' => $amount, 'date' => current_time('mysql'));
        update_option('wp_revenue_data', $data);
        wp_die();
    }

    public function get_revenue_data() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $data = get_option('wp_revenue_data', array());
        $labels = array();
        $amounts = array();
        foreach ($data as $entry) {
            $labels[] = $entry['type'] . ' (' . date('M d', strtotime($entry['date'])) . ')';
            $amounts[] = $entry['amount'];
        }
        wp_send_json(array('labels' => $labels, 'amounts' => $amounts));
    }
}

new WP_Revenue_Tracker();
