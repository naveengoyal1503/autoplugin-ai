/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Plugin URI: https://example.com/wp-revenue-tracker
 * Description: Track and visualize revenue from ads, affiliate links, digital sales, and donations.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

define('WP_REVENUE_TRACKER_VERSION', '1.0');
define('WP_REVENUE_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_revenue_entry', array($this, 'save_revenue_entry'));
        add_action('wp_ajax_get_revenue_data', array($this, 'get_revenue_data'));
        add_action('init', array($this, 'init_plugin'));
    }

    public function init_plugin() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            notes text,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            6
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook != 'toplevel_page_wp-revenue-tracker') return;
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), WP_REVENUE_TRACKER_VERSION, true);
        wp_localize_script('wp-revenue-tracker-js', 'wpRevenueTracker', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp-revenue-tracker-nonce')
        ));
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), WP_REVENUE_TRACKER_VERSION);
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <h2>Add Revenue Entry</h2>
                <form id="add-revenue-form">
                    <select name="source" required>
                        <option value="">Select Source</option>
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="sales">Sales</option>
                        <option value="donations">Donations</option>
                    </select>
                    <input type="number" name="amount" placeholder="Amount" step="0.01" required />
                    <input type="text" name="notes" placeholder="Notes (optional)" />
                    <button type="submit">Add Entry</button>
                </form>
            </div>
            <div id="revenue-chart-container">
                <canvas id="revenue-chart"></canvas>
            </div>
            <div id="revenue-table-container">
                <table id="revenue-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Source</th>
                            <th>Amount</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function save_revenue_entry() {
        check_ajax_referer('wp-revenue-tracker-nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $notes = sanitize_textarea_field($_POST['notes']);

        $wpdb->insert($table_name, array(
            'source' => $source,
            'amount' => $amount,
            'notes' => $notes
        ));

        wp_die();
    }

    public function get_revenue_data() {
        check_ajax_referer('wp-revenue-tracker-nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC", ARRAY_A);
        wp_send_json($results);
    }
}

new WP_Revenue_Tracker();

// Create assets directory and files if not exist
if (!file_exists(WP_REVENUE_TRACKER_PLUGIN_DIR . 'assets')) {
    mkdir(WP_REVENUE_TRACKER_PLUGIN_DIR . 'assets');
    mkdir(WP_REVENUE_TRACKER_PLUGIN_DIR . 'assets/css');
    mkdir(WP_REVENUE_TRACKER_PLUGIN_DIR . 'assets/js');
}

// Write default CSS
$css = "#revenue-form { margin-bottom: 20px; }
#revenue-chart-container { margin-bottom: 20px; }
#revenue-table { width: 100%; border-collapse: collapse; }
#revenue-table th, #revenue-table td { border: 1px solid #ddd; padding: 8px; }
#revenue-table th { background-color: #f2f2f2; }";
file_put_contents(WP_REVENUE_TRACKER_PLUGIN_DIR . 'assets/css/style.css', $css);

// Write default JS
$js = "jQuery(document).ready(function($) {
    function loadRevenueData() {
        $.post(wpRevenueTracker.ajax_url, {
            action: 'get_revenue_data',
            nonce: wpRevenueTracker.nonce
        }, function(response) {
            let tbody = $('#revenue-table tbody');
            tbody.empty();
            let labels = [];
            let data = [];
            let sources = { 'ads': 0, 'affiliate': 0, 'sales': 0, 'donations': 0 };
            response.forEach(function(row) {
                tbody.append('<tr><td>' + row.date + '</td><td>' + row.source + '</td><td>' + row.amount + '</td><td>' + row.notes + '</td></tr>');
                labels.push(row.date);
                data.push(parseFloat(row.amount));
                sources[row.source] += parseFloat(row.amount);
            });
            // Chart
            let ctx = $('#revenue-chart');
            if (window.revenueChart) window.revenueChart.destroy();
            window.revenueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        });
    }
    $('#add-revenue-form').on('submit', function(e) {
        e.preventDefault();
        $.post(wpRevenueTracker.ajax_url, {
            action: 'save_revenue_entry',
            nonce: wpRevenueTracker.nonce,
            source: $(this).find('select[name=source]').val(),
            amount: $(this).find('input[name=amount]').val(),
            notes: $(this).find('input[name=notes]').val()
        }, function() {
            loadRevenueData();
            $(this).reset();
        });
    });
    loadRevenueData();
});";
file_put_contents(WP_REVENUE_TRACKER_PLUGIN_DIR . 'assets/js/script.js', $js);
?>