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
        add_action('wp_ajax_save_revenue_data', array($this, 'save_revenue_data'));
        add_action('wp_ajax_get_revenue_data', array($this, 'get_revenue_data'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_admin_page'),
            'dashicons-chart-bar',
            6
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_wp-revenue-tracker') {
            return;
        }
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), WP_REVENUE_TRACKER_VERSION, true);
        wp_localize_script('wp-revenue-tracker-js', 'wpRevenueTracker', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_tracker_nonce')
        ));
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'assets/css/admin.css', array(), WP_REVENUE_TRACKER_VERSION);
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-chart-container">
                <canvas id="revenue-chart"></canvas>
            </div>
            <form id="revenue-form">
                <input type="hidden" name="action" value="save_revenue_data">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_revenue_tracker_nonce'); ?>">
                <table class="form-table">
                    <tr>
                        <th><label for="revenue-date">Date</label></th>
                        <td><input type="date" id="revenue-date" name="date" required></td>
                    </tr>
                    <tr>
                        <th><label for="revenue-amount">Amount ($)</label></th>
                        <td><input type="number" id="revenue-amount" name="amount" step="0.01" required></td>
                    </tr>
                    <tr>
                        <th><label for="revenue-source">Source</label></th>
                        <td>
                            <select id="revenue-source" name="source" required>
                                <option value="ads">Ads</option>
                                <option value="affiliate">Affiliate</option>
                                <option value="digital_product">Digital Product</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" class="button-primary" value="Add Revenue">
                </p>
            </form>
            <div id="revenue-list"></div>
        </div>
        <?php
    }

    public function save_revenue_data() {
        check_ajax_referer('wp_revenue_tracker_nonce', 'nonce');

        $date = sanitize_text_field($_POST['date']);
        $amount = floatval($_POST['amount']);
        $source = sanitize_text_field($_POST['source']);

        $revenue_data = get_option('wp_revenue_tracker_data', array());
        $revenue_data[] = array(
            'date' => $date,
            'amount' => $amount,
            'source' => $source
        );
        update_option('wp_revenue_tracker_data', $revenue_data);

        wp_die();
    }

    public function get_revenue_data() {
        check_ajax_referer('wp_revenue_tracker_nonce', 'nonce');

        $revenue_data = get_option('wp_revenue_tracker_data', array());
        wp_send_json($revenue_data);
    }
}

new WP_Revenue_Tracker();
