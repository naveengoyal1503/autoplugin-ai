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
        if ($hook !== 'toplevel_page_wp-revenue-tracker') return;
        wp_enqueue_script('jquery');
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label>Source: <input type="text" id="source" placeholder="e.g., AdSense, Affiliate, Product"></label><br>
                <label>Amount: <input type="number" id="amount" placeholder="0.00"></label><br>
                <label>Date: <input type="date" id="date" value="<?php echo date('Y-m-d'); ?>"></label><br>
                <button onclick="saveRevenue()">Add Revenue</button>
            </div>
            <canvas id="revenueChart" width="400" height="200"></canvas>
        </div>
        <script>
            function saveRevenue() {
                const source = document.getElementById('source').value;
                const amount = document.getElementById('amount').value;
                const date = document.getElementById('date').value;
                jQuery.post(ajaxurl, {
                    action: 'save_revenue',
                    source: source,
                    amount: amount,
                    date: date
                }, function() {
                    loadRevenueData();
                });
            }
            function loadRevenueData() {
                jQuery.post(ajaxurl, {action: 'get_revenue_data'}, function(data) {
                    const ctx = document.getElementById('revenueChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.dates,
                            datasets: [{
                                label: 'Revenue ($)',
                                data: data.amounts,
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {responsive: true}
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
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $wpdb->insert($table, array(
            'source' => sanitize_text_field($_POST['source']),
            'amount' => floatval($_POST['amount']),
            'date' => sanitize_text_field($_POST['date'])
        ));
        wp_die();
    }

    public function get_revenue_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $results = $wpdb->get_results("SELECT date, SUM(amount) as total FROM $table GROUP BY date ORDER BY date", ARRAY_A);
        $dates = array();
        $amounts = array();
        foreach ($results as $row) {
            $dates[] = $row['date'];
            $amounts[] = $row['total'];
        }
        wp_send_json(array('dates' => $dates, 'amounts' => $amounts));
    }

    public function install() {
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source varchar(100) NOT NULL,
            amount decimal(10,2) NOT NULL,
            date date NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

register_activation_hook(__FILE__, array(new WP_Revenue_Tracker, 'install'));
new WP_Revenue_Tracker();
?>