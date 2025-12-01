/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track and optimize your WordPress site's revenue streams with real-time analytics and actionable insights.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_admin_page'),
            'dashicons-chart-bar'
        );
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'wp_revenue_tracker_widget',
            'Revenue Overview',
            array($this, 'render_dashboard_widget')
        );
    }

    public function register_settings() {
        register_setting('wp_revenue_tracker', 'wp_revenue_tracker_settings');
    }

    public function render_admin_page() {
        $settings = get_option('wp_revenue_tracker_settings', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_tracker'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="affiliate_revenue">Affiliate Revenue</label></th>
                        <td><input type="number" name="wp_revenue_tracker_settings[affiliate_revenue]" id="affiliate_revenue" value="<?php echo esc_attr($settings['affiliate_revenue'] ?? 0); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="ads_revenue">Ads Revenue</label></th>
                        <td><input type="number" name="wp_revenue_tracker_settings[ads_revenue]" id="ads_revenue" value="<?php echo esc_attr($settings['ads_revenue'] ?? 0); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="product_revenue">Product Revenue</label></th>
                        <td><input type="number" name="wp_revenue_tracker_settings[product_revenue]" id="product_revenue" value="<?php echo esc_attr($settings['product_revenue'] ?? 0); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <div class="revenue-chart">
                <h2>Revenue Chart</h2>
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('revenueChart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Affiliate', 'Ads', 'Product'],
                        datasets: [{
                            label: 'Revenue ($)',
                            data: [
                                <?php echo esc_js($settings['affiliate_revenue'] ?? 0); ?>,
                                <?php echo esc_js($settings['ads_revenue'] ?? 0); ?>,
                                <?php echo esc_js($settings['product_revenue'] ?? 0); ?>
                            ],
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 206, 86, 0.2)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
        </script>
        <?php
    }

    public function render_dashboard_widget() {
        $settings = get_option('wp_revenue_tracker_settings', array());
        $total = ($settings['affiliate_revenue'] ?? 0) + ($settings['ads_revenue'] ?? 0) + ($settings['product_revenue'] ?? 0);
        echo '<p><strong>Total Revenue: $' . number_format($total, 2) . '</strong></p>';
    }
}

new WP_Revenue_Tracker();
