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
        add_action('wp_ajax_get_revenue', array($this, 'get_revenue'));
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
                        loadChart();
                    });
                });

                function loadChart() {
                    $.post(ajaxurl, {action: 'get_revenue'}, function(data) {
                        var ctx = document.getElementById('revenue-chart').getContext('2d');
                        if (window.revenueChart) window.revenueChart.destroy();
                        window.revenueChart = new Chart(ctx, {
                            type: 'bar',
                            data: data,
                            options: {responsive: true}
                        });
                    });
                }
                loadChart();
            });
        </script>
        <?php
    }

    public function save_revenue() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $type = sanitize_text_field($_POST['type']);
        $amount = floatval($_POST['amount']);
        $date = date('Y-m-d');
        $revenue = get_option('wp_revenue_tracker_data', array());
        if (!isset($revenue[$date])) $revenue[$date] = array('ads' => 0, 'affiliate' => 0, 'products' => 0);
        $revenue[$date][$type] += $amount;
        update_option('wp_revenue_tracker_data', $revenue);
        wp_die();
    }

    public function get_revenue() {
        $revenue = get_option('wp_revenue_tracker_data', array());
        $labels = array();
        $ads = array();
        $affiliate = array();
        $products = array();
        foreach ($revenue as $date => $data) {
            $labels[] = $date;
            $ads[] = $data['ads'];
            $affiliate[] = $data['affiliate'];
            $products[] = $data['products'];
        }
        wp_send_json(array(
            'labels' => $labels,
            'datasets' => array(
                array('label' => 'Ads', 'data' => $ads, 'backgroundColor' => '#4CAF50'),
                array('label' => 'Affiliate', 'data' => $affiliate, 'backgroundColor' => '#2196F3'),
                array('label' => 'Products', 'data' => $products, 'backgroundColor' => '#FF9800')
            )
        ));
    }
}

new WP_Revenue_Tracker();
