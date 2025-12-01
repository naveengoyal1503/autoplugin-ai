<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and visualize revenue from ads, affiliate links, memberships, and product sales.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
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
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
    }

    public function render_dashboard() {
        // Mock data for demo
        $revenue_data = array(
            'ads' => 1200,
            'affiliate' => 800,
            'memberships' => 500,
            'products' => 1000
        );
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <canvas id="revenueChart" width="400" height="200"></canvas>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var ctx = document.getElementById('revenueChart').getContext('2d');
                    var chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Ads', 'Affiliate', 'Memberships', 'Products'],
                            datasets: [{
                                label: 'Revenue ($)',
                                data: [
                                    <?php echo $revenue_data['ads']; ?>,
                                    <?php echo $revenue_data['affiliate']; ?>,
                                    <?php echo $revenue_data['memberships']; ?>,
                                    <?php echo $revenue_data['products']; ?>
                                ],
                                backgroundColor: [
                                    '#FF6384',
                                    '#36A2EB',
                                    '#FFCE56',
                                    '#4BC0C0'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
            </script>
        </div>
        <?php
    }
}

new WP_Revenue_Tracker();
?>