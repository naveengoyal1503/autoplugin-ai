/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track and visualize revenue from ads, affiliate links, and digital product sales.
 * Version: 1.0
 * Author: WP Dev Team
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
            'dashicons-chart-bar'
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_wp-revenue-tracker') {
            return;
        }
        wp_enqueue_script('jquery');
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <h2>Add Revenue</h2>
                <form id="revenue-form">
                    <label>Source: <select id="source">
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="digital">Digital Products</option>
                    </select></label>
                    <label>Amount: <input type="number" id="amount" step="0.01" /></label>
                    <button type="button" onclick="saveRevenue()">Save</button>
                </form>
            </div>
            <div id="revenue-chart" style="width: 80%; margin-top: 20px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        <script>
            function saveRevenue() {
                var source = jQuery('#source').val();
                var amount = jQuery('#amount').val();
                jQuery.post(ajaxurl, {
                    action: 'save_revenue',
                    source: source,
                    amount: amount
                }, function(response) {
                    alert('Revenue saved!');
                    loadRevenueData();
                });
            }

            function loadRevenueData() {
                jQuery.post(ajaxurl, {
                    action: 'get_revenue_data'
                }, function(response) {
                    var ctx = document.getElementById('revenueChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: response.labels,
                            datasets: [{
                                label: 'Revenue',
                                data: response.data,
                                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
                            }]
                        }
                    });
                });
            }

            jQuery(document).ready(function() {
                loadRevenueData();
            });
        </script>
        <?php
    }

    public function save_revenue() {
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = current_time('mysql');

        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $wpdb->insert($table, array(
            'source' => $source,
            'amount' => $amount,
            'date' => $date
        ));

        wp_die();
    }

    public function get_revenue_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $results = $wpdb->get_results("SELECT source, SUM(amount) as total FROM $table GROUP BY source");

        $labels = array();
        $data = array();
        foreach ($results as $row) {
            $labels[] = ucfirst($row->source);
            $data[] = $row->total;
        }

        wp_send_json(array('labels' => $labels, 'data' => $data));
    }

    public function install() {
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

$revenue_tracker = new WP_Revenue_Tracker();
register_activation_hook(__FILE__, array($revenue_tracker, 'install'));
?>