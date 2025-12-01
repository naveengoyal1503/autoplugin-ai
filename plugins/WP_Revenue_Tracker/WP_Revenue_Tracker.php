/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track and visualize revenue from ads, affiliate links, digital sales, and memberships.
 * Version: 1.0
 * Author: WP Dev Team
 */

define('WP_REVENUE_TRACKER_VERSION', '1.0');

class WPRevenueTracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_revenue_data', array($this, 'save_revenue_data'));
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
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'assets/js/revenue-tracker.js', array('jquery'), WP_REVENUE_TRACKER_VERSION, true);
        wp_localize_script('wp-revenue-tracker-js', 'wpRevenueTracker', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp-revenue-tracker-nonce')
        ));
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'assets/css/revenue-tracker.css', array(), WP_REVENUE_TRACKER_VERSION);
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <label>Revenue Type: <select id="revenue-type">
                    <option value="ads">Ads</option>
                    <option value="affiliate">Affiliate</option>
                    <option value="digital-sales">Digital Sales</option>
                    <option value="memberships">Memberships</option>
                </select></label>
                <label>Amount: <input type="number" id="revenue-amount" step="0.01" /></label>
                <label>Date: <input type="date" id="revenue-date" /></label>
                <button id="save-revenue">Save Revenue</button>
            </div>
            <canvas id="revenue-chart" width="400" height="200"></canvas>
        </div>
        <?php
    }

    public function save_revenue_data() {
        check_ajax_referer('wp-revenue-tracker-nonce', 'nonce');
        $type = sanitize_text_field($_POST['type']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);

        $data = get_option('wp_revenue_tracker_data', array());
        $data[] = array('type' => $type, 'amount' => $amount, 'date' => $date);
        update_option('wp_revenue_tracker_data', $data);

        wp_die();
    }

    public function get_revenue_data() {
        check_ajax_referer('wp-revenue-tracker-nonce', 'nonce');
        $data = get_option('wp_revenue_tracker_data', array());
        wp_send_json($data);
    }
}

new WPRevenueTracker();
?>