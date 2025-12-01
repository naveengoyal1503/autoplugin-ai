<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and visualize revenue from ads, affiliate links, memberships, and digital sales.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_revenue_data', array($this, 'save_revenue_data'));
        add_action('wp_ajax_get_revenue_chart', array($this, 'get_revenue_chart'));
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
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <form id="revenue-form">
                <table class="form-table">
                    <tr>
                        <th><label>Date</label></th>
                        <td><input type="date" name="date" required></td>
                    </tr>
                    <tr>
                        <th><label>Source</label></th>
                        <td>
                            <select name="source" required>
                                <option value="ads">Ads</option>
                                <option value="affiliate">Affiliate</option>
                                <option value="memberships">Memberships</option>
                                <option value="digital_sales">Digital Sales</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Amount ($)</label></th>
                        <td><input type="number" name="amount" step="0.01" required></td>
                    </tr>
                </table>
                <button type="submit" class="button button-primary">Add Revenue</button>
            </form>
            <hr>
            <h2>Revenue Chart</h2>
            <canvas id="revenue-chart" width="400" height="200"></canvas>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#revenue-form').on('submit', function(e) {
                    e.preventDefault();
                    $.post(ajaxurl, {
                        action: 'save_revenue_data',
                        data: $(this).serialize()
                    }, function() {
                        alert('Revenue data saved!');
                        loadChart();
                    });
                });
                function loadChart() {
                    $.post(ajaxurl, { action: 'get_revenue_chart' }, function(res) {
                        var ctx = document.getElementById('revenue-chart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: res,
                            options: { responsive: true }
                        });
                    });
                }
                loadChart();
            });
        </script>
        <?php
    }

    public function save_revenue_data() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        parse_str($_POST['data'], $data);
        $entry = array(
            'date' => sanitize_text_field($data['date']),
            'source' => sanitize_text_field($data['source']),
            'amount' => floatval($data['amount'])
        );
        $entries = get_option('wp_revenue_tracker_entries', array());
        $entries[] = $entry;
        update_option('wp_revenue_tracker_entries', $entries);
        wp_die();
    }

    public function get_revenue_chart() {
        $entries = get_option('wp_revenue_tracker_entries', array());
        $labels = array();
        $datasets = array(
            'ads' => array(),
            'affiliate' => array(),
            'memberships' => array(),
            'digital_sales' => array()
        );
        foreach ($entries as $entry) {
            $labels[] = $entry['date'];
            foreach ($datasets as $source => $values) {
                $datasets[$source][] = $entry['source'] === $source ? $entry['amount'] : 0;
            }
        }
        $response = array(
            'labels' => array_unique($labels),
            'datasets' => array(
                array('label' => 'Ads', 'data' => $datasets['ads'], 'backgroundColor' => '#4CAF50'),
                array('label' => 'Affiliate', 'data' => $datasets['affiliate'], 'backgroundColor' => '#2196F3'),
                array('label' => 'Memberships', 'data' => $datasets['memberships'], 'backgroundColor' => '#FF9800'),
                array('label' => 'Digital Sales', 'data' => $datasets['digital_sales'], 'backgroundColor' => '#F44336')
            )
        );
        wp_send_json($response);
    }
}

new WP_Revenue_Tracker();
