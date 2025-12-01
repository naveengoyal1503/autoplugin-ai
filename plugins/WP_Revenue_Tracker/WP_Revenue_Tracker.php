/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track and visualize revenue from ads, affiliate links, and digital product sales.
 * Version: 1.0
 * Author: Your Name
 */

define('WP_REVENUE_TRACKER_VERSION', '1.0');
define('WP_REVENUE_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('wp_ajax_save_revenue_data', array($this, 'save_revenue_data'));
        add_action('wp_ajax_nopriv_save_revenue_data', array($this, 'save_revenue_data'));
        add_action('wp_ajax_get_revenue_chart', array($this, 'get_revenue_chart'));
        add_action('wp_ajax_nopriv_get_revenue_chart', array($this, 'get_revenue_chart'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_admin_page'),
            'dashicons-chart-bar'
        );
    }

    public function settings_init() {
        register_setting('wp_revenue_tracker', 'wp_revenue_tracker_options');

        add_settings_section(
            'wp_revenue_tracker_section',
            'Revenue Sources',
            null,
            'wp_revenue_tracker'
        );

        add_settings_field(
            'ads_revenue',
            'Ads Revenue',
            array($this, 'ads_revenue_render'),
            'wp_revenue_tracker',
            'wp_revenue_tracker_section'
        );

        add_settings_field(
            'affiliate_revenue',
            'Affiliate Revenue',
            array($this, 'affiliate_revenue_render'),
            'wp_revenue_tracker',
            'wp_revenue_tracker_section'
        );

        add_settings_field(
            'product_revenue',
            'Product Revenue',
            array($this, 'product_revenue_render'),
            'wp_revenue_tracker',
            'wp_revenue_tracker_section'
        );
    }

    public function ads_revenue_render() {
        $options = get_option('wp_revenue_tracker_options');
        ?>
        <input type='number' name='wp_revenue_tracker_options[ads_revenue]' value='<?php echo $options['ads_revenue']; ?>'>
        <?php
    }

    public function affiliate_revenue_render() {
        $options = get_option('wp_revenue_tracker_options');
        ?>
        <input type='number' name='wp_revenue_tracker_options[affiliate_revenue]' value='<?php echo $options['affiliate_revenue']; ?>'>
        <?php
    }

    public function product_revenue_render() {
        $options = get_option('wp_revenue_tracker_options');
        ?>
        <input type='number' name='wp_revenue_tracker_options[product_revenue]' value='<?php echo $options['product_revenue']; ?>'>
        <?php
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('wp_revenue_tracker');
                do_settings_sections('wp_revenue_tracker');
                submit_button();
                ?>
            </form>
            <div id="revenue-chart">
                <canvas id="revenueChart"></canvas>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                jQuery(document).ready(function($) {
                    $.post(ajaxurl, {action: 'get_revenue_chart'}, function(response) {
                        var ctx = document.getElementById('revenueChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: response,
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
                });
            </script>
        </div>
        <?php
    }

    public function save_revenue_data() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $options = get_option('wp_revenue_tracker_options', array());
        $options['ads_revenue'] = isset($_POST['ads_revenue']) ? floatval($_POST['ads_revenue']) : 0;
        $options['affiliate_revenue'] = isset($_POST['affiliate_revenue']) ? floatval($_POST['affiliate_revenue']) : 0;
        $options['product_revenue'] = isset($_POST['product_revenue']) ? floatval($_POST['product_revenue']) : 0;
        update_option('wp_revenue_tracker_options', $options);
        wp_die('Revenue data saved');
    }

    public function get_revenue_chart() {
        $options = get_option('wp_revenue_tracker_options', array());
        $labels = ['Ads', 'Affiliate', 'Product'];
        $data = [
            isset($options['ads_revenue']) ? $options['ads_revenue'] : 0,
            isset($options['affiliate_revenue']) ? $options['affiliate_revenue'] : 0,
            isset($options['product_revenue']) ? $options['product_revenue'] : 0
        ];
        wp_send_json(array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => 'Revenue ($)',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)'
                    ],
                    'borderColor' => [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    'borderWidth' => 1
                )
            )
        ));
    }
}

new WP_Revenue_Tracker();
?>