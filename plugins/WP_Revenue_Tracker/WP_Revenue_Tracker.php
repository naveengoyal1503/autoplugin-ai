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

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_track_revenue', array($this, 'track_revenue'));
        add_action('wp_ajax_nopriv_track_revenue', array($this, 'track_revenue'));
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
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'assets/js/tracker.js', array('jquery'), WP_REVENUE_TRACKER_VERSION, true);
        wp_localize_script('wp-revenue-tracker-js', 'wp_revenue_tracker', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_tracker_nonce')
        ));
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'assets/css/tracker.css', array(), WP_REVENUE_TRACKER_VERSION);
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-chart-container">
                <canvas id="revenue-chart"></canvas>
            </div>
            <div class="revenue-form">
                <h2>Add Revenue Entry</h2>
                <form id="revenue-form">
                    <label for="source">Source:</label>
                    <select name="source" id="source">
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="digital_product">Digital Product</option>
                    </select>
                    <label for="amount">Amount ($):</label>
                    <input type="number" name="amount" id="amount" step="0.01" required>
                    <label for="date">Date:</label>
                    <input type="date" name="date" id="date" required>
                    <button type="submit">Add Revenue</button>
                </form>
            </div>
        </div>
        <?php
    }

    public function track_revenue() {
        check_ajax_referer('wp_revenue_tracker_nonce', 'nonce');

        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);

        $entry = array(
            'source' => $source,
            'amount' => $amount,
            'date' => $date
        );

        $entries = get_option('wp_revenue_tracker_entries', array());
        $entries[] = $entry;
        update_option('wp_revenue_tracker_entries', $entries);

        wp_send_json_success($entries);
    }
}

new WP_Revenue_Tracker();
?>