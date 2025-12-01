<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and visualize revenue from ads, affiliate links, digital product sales, and memberships.
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
        if ($hook !== 'toplevel_page_wp-revenue-tracker') return;
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'js/tracker.js', array('chart-js'), '1.0', true);
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'css/tracker.css', array(), '1.0');
    }

    public function render_dashboard() {
        $revenue_data = $this->get_revenue_data();
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-chart-container">
                <canvas id="revenue-chart"></canvas>
            </div>
            <div class="revenue-table">
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Revenue</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revenue_data as $entry): ?>
                        <tr>
                            <td><?php echo esc_html($entry['source']); ?></td>
                            <td>$<?php echo esc_html($entry['revenue']); ?></td>
                            <td><?php echo esc_html($entry['date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script>
            window.revenueData = <?php echo json_encode($revenue_data); ?>;
        </script>
        <?php
    }

    private function get_revenue_data() {
        // Simulated data for demo
        return array(
            array('source' => 'AdSense', 'revenue' => 150, 'date' => '2025-11-01'),
            array('source' => 'Affiliate', 'revenue' => 200, 'date' => '2025-11-02'),
            array('source' => 'Digital Sales', 'revenue' => 300, 'date' => '2025-11-03'),
            array('source' => 'Membership', 'revenue' => 100, 'date' => '2025-11-04'),
        );
    }
}

new WP_Revenue_Tracker();
