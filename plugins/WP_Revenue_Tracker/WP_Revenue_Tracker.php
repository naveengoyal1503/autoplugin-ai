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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
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
        if ($hook !== 'toplevel_page_wp-revenue-tracker') return;
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'assets/js/tracker.js', array('chart-js'), '1.0', true);
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'assets/css/tracker.css', array(), '1.0');
    }

    public function render_dashboard() {
        // Sample data for demo
        $revenue_data = array(
            'ads' => 1200,
            'affiliate' => 800,
            'products' => 2000
        );
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <canvas id="revenueChart" width="400" height="200"></canvas>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var ctx = document.getElementById('revenueChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Ads', 'Affiliate', 'Products'],
                            datasets: [{
                                label: 'Revenue ($)',
                                data: [<?php echo $revenue_data['ads']; ?>, <?php echo $revenue_data['affiliate']; ?>, <?php echo $revenue_data['products']; ?>],
                                backgroundColor: [
                                    'rgba(54, 162, 235, 0.2)',
                                    'rgba(255, 206, 86, 0.2)',
                                    'rgba(75, 192, 192, 0.2)'
                                ],
                                borderColor: [
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 206, 86, 1)',
                                    'rgba(75, 192, 192, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: { y: { beginAtZero: true } }
                        }
                    });
                });
            </script>
            <div class="revenue-form">
                <h2>Add Revenue Entry</h2>
                <form method="post">
                    <select name="revenue_type">
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="products">Products</option>
                    </select>
                    <input type="number" name="amount" placeholder="Amount" required />
                    <input type="submit" name="submit_revenue" value="Add" />
                </form>
            </div>
        </div>
        <?php
        if (isset($_POST['submit_revenue'])) {
            // In a real plugin, you would save to the database
            // For demo, just show a message
            echo '<div class="notice notice-success"><p>Revenue entry added!</p></div>';
        }
    }
}

new WP_Revenue_Tracker();
?>