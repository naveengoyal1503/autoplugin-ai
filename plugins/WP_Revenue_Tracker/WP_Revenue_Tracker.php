/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Plugin URI: https://example.com/wp-revenue-tracker
 * Description: Track and visualize revenue from ads, affiliate links, digital sales, and memberships.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
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
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery', 'chart-js'), '1.0', true);
        wp_localize_script('wp-revenue-tracker-js', 'wp_revenue_tracker', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_tracker_nonce')
        ));
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), '1.0');
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-form">
                <h2>Add Revenue</h2>
                <form id="add-revenue-form">
                    <select id="revenue-type">
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="digital_sales">Digital Sales</option>
                        <option value="memberships">Memberships</option>
                    </select>
                    <input type="number" id="revenue-amount" placeholder="Amount">
                    <button type="submit">Add</button>
                </form>
            </div>
            <div id="revenue-chart-container">
                <canvas id="revenue-chart"></canvas>
            </div>
        </div>
        <?php
    }

    public function save_revenue() {
        check_ajax_referer('wp_revenue_tracker_nonce', 'nonce');
        $type = sanitize_text_field($_POST['type']);
        $amount = floatval($_POST['amount']);
        $date = current_time('mysql');

        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $wpdb->insert($table, array(
            'type' => $type,
            'amount' => $amount,
            'date' => $date
        ));

        wp_die();
    }

    public function get_revenue_data() {
        check_ajax_referer('wp_revenue_tracker_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $data = $wpdb->get_results("SELECT type, SUM(amount) as total FROM $table GROUP BY type");

        wp_send_json($data);
    }
}

function wp_revenue_tracker_install() {
    global $wpdb;
    $table = $wpdb->prefix . 'revenue_tracker';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        type varchar(50) NOT NULL,
        amount decimal(10,2) NOT NULL,
        date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'wp_revenue_tracker_install');

new WP_Revenue_Tracker();
