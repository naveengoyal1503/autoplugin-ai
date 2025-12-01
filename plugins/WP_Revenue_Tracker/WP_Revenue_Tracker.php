<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and visualize revenue from ads, affiliate links, digital products, and memberships.
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
            'dashicons-chart-bar',
            6
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook != 'toplevel_page_wp-revenue-tracker') return;
        wp_enqueue_script('jquery');
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label>Revenue Type: 
                    <select id="revenue-type">
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="products">Digital Products</option>
                        <option value="memberships">Memberships</option>
                    </select>
                </label>
                <label>Amount: <input type="number" id="revenue-amount" step="0.01" /></label>
                <button id="save-revenue">Save Revenue</button>
            </div>
            <canvas id="revenue-chart" width="400" height="200"></canvas>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#save-revenue').on('click', function() {
                    $.post(ajaxurl, {
                        action: 'save_revenue',
                        type: $('#revenue-type').val(),
                        amount: $('#revenue-amount').val()
                    }, function(response) {
                        if (response.success) {
                            loadChart();
                        }
                    });
                });

                function loadChart() {
                    $.post(ajaxurl, { action: 'get_revenue_data' }, function(response) {
                        if (response.success) {
                            var ctx = document.getElementById('revenue-chart').getContext('2d');
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: Object.keys(response.data),
                                    datasets: [{
                                        label: 'Revenue ($)',
                                        data: Object.values(response.data),
                                        backgroundColor: '#4CAF50'
                                    }]
                                }
                            });
                        }
                    });
                }
                loadChart();
            });
        </script>
        <?php
    }

    public function save_revenue() {
        $type = sanitize_text_field($_POST['type']);
        $amount = floatval($_POST['amount']);
        $data = get_option('wp_revenue_tracker_data', array());
        if (!isset($data[$type])) $data[$type] = 0;
        $data[$type] += $amount;
        update_option('wp_revenue_tracker_data', $data);
        wp_send_json_success();
    }

    public function get_revenue_data() {
        $data = get_option('wp_revenue_tracker_data', array());
        wp_send_json_success($data);
    }
}

new WP_Revenue_Tracker();
