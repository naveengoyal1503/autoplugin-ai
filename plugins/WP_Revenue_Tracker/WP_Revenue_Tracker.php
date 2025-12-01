/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track and visualize revenue from ads, affiliate links, digital products, and memberships.
 * Version: 1.0
 * Author: Your Company
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_revenue_data', array($this, 'save_revenue_data'));
        add_action('wp_ajax_get_revenue_data', array($this, 'get_revenue_data'));
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
        if ($hook !== 'toplevel_page_wp-revenue-tracker') {
            return;
        }
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'assets/js/tracker.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-tracker-js', 'wpRevenueAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_nonce')
        ));
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'assets/css/tracker.css', array(), '1.0');
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <h2>Add Revenue Entry</h2>
                <form id="revenue-entry-form">
                    <label>Source: <select name="source">
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="digital_product">Digital Product</option>
                        <option value="membership">Membership</option>
                    </select></label>
                    <label>Amount: <input type="number" name="amount" step="0.01" required></label>
                    <label>Date: <input type="date" name="date" required></label>
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
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function save_revenue_data() {
        check_ajax_referer('wp_revenue_nonce', 'nonce');
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);

        $entry = array(
            'source' => $source,
            'amount' => $amount,
            'date' => $date
        );

        $entries = get_option('wp_revenue_entries', array());
        $entries[] = $entry;
        update_option('wp_revenue_entries', $entries);

        wp_send_json_success();
    }

    public function get_revenue_data() {
        check_ajax_referer('wp_revenue_nonce', 'nonce');
        $entries = get_option('wp_revenue_entries', array());
        wp_send_json_success($entries);
    }
}

new WP_Revenue_Tracker();
?>