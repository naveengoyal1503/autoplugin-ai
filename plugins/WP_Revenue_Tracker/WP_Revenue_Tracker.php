<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and visualize revenue from ads, affiliate links, digital products, and sponsored content.
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
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <h2>Add Revenue Entry</h2>
                <form id="revenue-entry-form">
                    <label>Source: <select name="source">
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="digital_product">Digital Product</option>
                        <option value="sponsored">Sponsored Content</option>
                    </select></label>
                    <label>Amount: <input type="number" name="amount" step="0.01" required></label>
                    <label>Date: <input type="date" name="date" required></label>
                    <button type="submit">Add Entry</button>
                </form>
            </div>
            <div id="revenue-chart-container" style="width:80%; margin-top:20px;">
                <canvas id="revenue-chart"></canvas>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#revenue-entry-form').on('submit', function(e) {
                    e.preventDefault();
                    $.post(ajaxurl, {
                        action: 'save_revenue',
                        source: $(this).find('[name="source"]').val(),
                        amount: $(this).find('[name="amount"]').val(),
                        date: $(this).find('[name="date"]').val()
                    }, function(response) {
                        if (response.success) {
                            alert('Revenue entry saved!');
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
                                    labels: response.data.dates,
                                    datasets: [{
                                        label: 'Revenue',
                                        data: response.data.amounts,
                                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                        borderColor: 'rgba(54, 162, 235, 1)',
                                        borderWidth: 1
                                    }]
                                },
                                options: { responsive: true }
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
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);

        $entry = array('source' => $source, 'amount' => $amount, 'date' => $date);
        $entries = get_option('wp_revenue_tracker_entries', array());
        $entries[] = $entry;
        update_option('wp_revenue_tracker_entries', $entries);
        wp_send_json_success();
    }

    public function get_revenue_data() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $entries = get_option('wp_revenue_tracker_entries', array());
        $dates = array();
        $amounts = array();
        foreach ($entries as $entry) {
            $dates[] = $entry['date'];
            $amounts[] = $entry['amount'];
        }
        wp_send_json_success(array('dates' => $dates, 'amounts' => $amounts));
    }
}

new WP_Revenue_Tracker();
