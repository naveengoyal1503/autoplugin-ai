/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track and visualize revenue from ads, affiliate links, digital sales, and donations.
 * Version: 1.0
 * Author: Your Name
 */

define('WP_REVENUE_TRACKER_VERSION', '1.0');

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
            'dashicons-chart-bar',
            6
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_wp-revenue-tracker') {
            return;
        }
        wp_enqueue_script('jquery');
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label>Source: <input type="text" id="source" placeholder="e.g., AdSense, Affiliate, Sales"></label><br>
                <label>Amount: <input type="number" id="amount" step="0.01" placeholder="0.00"></label><br>
                <label>Date: <input type="date" id="date"></label><br>
                <button id="save-revenue">Save Revenue</button>
            </div>
            <div id="revenue-chart-container" style="width: 80%; margin: 20px auto;">
                <canvas id="revenue-chart"></canvas>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#save-revenue').on('click', function() {
                    $.post(ajaxurl, {
                        action: 'save_revenue',
                        source: $('#source').val(),
                        amount: $('#amount').val(),
                        date: $('#date').val(),
                        nonce: '<?php echo wp_create_nonce('save_revenue_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('Revenue saved!');
                            loadRevenueChart();
                        } else {
                            alert('Error saving revenue.');
                        }
                    });
                });

                function loadRevenueChart() {
                    $.post(ajaxurl, {
                        action: 'get_revenue_data',
                        nonce: '<?php echo wp_create_nonce('get_revenue_data_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            var ctx = document.getElementById('revenue-chart').getContext('2d');
                            if (window.revenueChart) window.revenueChart.destroy();
                            window.revenueChart = new Chart(ctx, {
                                type: 'bar',
                                data: response.data,
                                options: {
                                    responsive: true,
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });
                        }
                    });
                }

                loadRevenueChart();
            });
        </script>
        <?php
    }

    public function save_revenue() {
        check_ajax_referer('save_revenue_nonce', 'nonce');
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
        wp_send_json_success();
    }

    public function get_revenue_data() {
        check_ajax_referer('get_revenue_data_nonce', 'nonce');
        $revenue_data = get_option('wp_revenue_tracker_data', array());
        $labels = array();
        $amounts = array();
        foreach ($revenue_data as $entry) {
            $labels[] = $entry['source'] . ' (' . $entry['date'] . ')';
            $amounts[] = $entry['amount'];
        }
        wp_send_json_success(array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => 'Revenue',
                    'data' => $amounts,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                )
            )
        ));
    }
}

new WP_Revenue_Tracker();
