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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
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
        if ($hook !== 'toplevel_page_wp-revenue-tracker') return;
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.1', true);
        wp_enqueue_script('wp-revenue-tracker-js', plugin_dir_url(__FILE__) . 'js/revenue-tracker.js', array('chart-js'), '1.0', true);
        wp_enqueue_style('wp-revenue-tracker-css', plugin_dir_url(__FILE__) . 'css/revenue-tracker.css', array(), '1.0');
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-chart-container">
                <canvas id="revenue-chart"></canvas>
            </div>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th><label for="revenue_source">Revenue Source</label></th>
                        <td>
                            <select name="revenue_source" id="revenue_source">
                                <option value="ads">Ads</option>
                                <option value="affiliate">Affiliate</option>
                                <option value="products">Products</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="revenue_amount">Amount ($)</label></th>
                        <td><input type="number" name="revenue_amount" id="revenue_amount" step="0.01" required /></td>
                    </tr>
                    <tr>
                        <th><label for="revenue_date">Date</label></th>
                        <td><input type="date" name="revenue_date" id="revenue_date" required /></td>
                    </tr>
                </table>
                <?php submit_button('Add Revenue'); ?>
            </form>
            <div id="revenue-list">
                <?php $this->render_revenue_list(); ?>
            </div>
        </div>
        <?php
    }

    public function render_revenue_list() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $revenues = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC");
        if ($revenues) {
            echo '<ul class="revenue-list">';
            foreach ($revenues as $revenue) {
                echo '<li>' . esc_html($revenue->source) . ': $' . esc_html($revenue->amount) . ' on ' . esc_html($revenue->date) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No revenue data yet.</p>';
        }
    }

    public function save_revenue() {
        if (!isset($_POST['revenue_source']) || !isset($_POST['revenue_amount']) || !isset($_POST['revenue_date'])) return;

        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $wpdb->insert(
            $table_name,
            array(
                'source' => sanitize_text_field($_POST['revenue_source']),
                'amount' => floatval($_POST['revenue_amount']),
                'date' => sanitize_text_field($_POST['revenue_date'])
            ),
            array('%s', '%f', '%s')
        );
    }

    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            date date NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function init() {
        $this->create_table();
        if ($_POST && isset($_POST['revenue_source'])) {
            $this->save_revenue();
        }
    }
}

$revenue_tracker = new WP_Revenue_Tracker();
add_action('init', array($revenue_tracker, 'init'));
?>