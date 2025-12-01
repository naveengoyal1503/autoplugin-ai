/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Plugin URI: https://example.com/wp-revenue-tracker
 * Description: Track and visualize revenue from ads, affiliate links, digital products, and memberships.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

define('WP_REVENUE_TRACKER_VERSION', '1.0');
define('WP_REVENUE_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_REVENUE_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'wp_revenue_tracker_activate');
register_deactivation_hook(__FILE__, 'wp_revenue_tracker_deactivate');

function wp_revenue_tracker_activate() {
    // Create table for storing revenue data
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_tracker';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        source varchar(50) NOT NULL,
        amount decimal(10,2) NOT NULL,
        date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        description text,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function wp_revenue_tracker_deactivate() {
    // Optional: Clean up on deactivation
}

// Add admin menu
add_action('admin_menu', 'wp_revenue_tracker_menu');

function wp_revenue_tracker_menu() {
    add_menu_page(
        'Revenue Tracker',
        'Revenue Tracker',
        'manage_options',
        'wp-revenue-tracker',
        'wp_revenue_tracker_dashboard',
        'dashicons-chart-bar',
        6
    );
}

// Dashboard page
function wp_revenue_tracker_dashboard() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_tracker';

    // Handle form submission
    if (isset($_POST['add_revenue'])) {
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $description = sanitize_textarea_field($_POST['description']);
        $date = sanitize_text_field($_POST['date']);

        $wpdb->insert(
            $table_name,
            array(
                'source' => $source,
                'amount' => $amount,
                'date' => $date,
                'description' => $description
            )
        );
    }

    // Fetch revenue data
    $revenue_data = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC");
    $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");

    // Display dashboard
    ?>
    <div class="wrap">
        <h1>WP Revenue Tracker</h1>
        <p>Total Revenue: $<?php echo number_format($total_revenue, 2); ?></p>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="source">Source</label></th>
                    <td>
                        <select name="source" id="source" required>
                            <option value="ads">Ads</option>
                            <option value="affiliate">Affiliate</option>
                            <option value="digital_products">Digital Products</option>
                            <option value="memberships">Memberships</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="amount">Amount</label></th>
                    <td><input type="number" step="0.01" name="amount" id="amount" required /></td>
                </tr>
                <tr>
                    <th><label for="date">Date</label></th>
                    <td><input type="date" name="date" id="date" required /></td>
                </tr>
                <tr>
                    <th><label for="description">Description</label></th>
                    <td><textarea name="description" id="description"></textarea></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="add_revenue" class="button-primary" value="Add Revenue" />
            </p>
        </form>
        <h2>Revenue History</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Source</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revenue_data as $row): ?>
                <tr>
                    <td><?php echo $row->id; ?></td>
                    <td><?php echo ucfirst($row->source); ?></td>
                    <td>$<?php echo number_format($row->amount, 2); ?></td>
                    <td><?php echo $row->date; ?></td>
                    <td><?php echo $row->description; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Enqueue admin styles and scripts
add_action('admin_enqueue_scripts', 'wp_revenue_tracker_enqueue');

function wp_revenue_tracker_enqueue($hook) {
    if ('toplevel_page_wp-revenue-tracker' !== $hook) {
        return;
    }
    wp_enqueue_style('wp-revenue-tracker-admin', WP_REVENUE_TRACKER_PLUGIN_URL . 'admin.css');
}

// Add shortcode for displaying total revenue on frontend
add_shortcode('wp_revenue_total', 'wp_revenue_total_shortcode');

function wp_revenue_total_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_tracker';
    $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
    return '<p>Total Revenue: $' . number_format($total_revenue, 2) . '</p>';
}

// Add widget for displaying revenue on dashboard
add_action('wp_dashboard_setup', 'wp_revenue_tracker_dashboard_widget');

function wp_revenue_tracker_dashboard_widget() {
    wp_add_dashboard_widget(
        'wp_revenue_tracker_widget',
        'Revenue Tracker',
        'wp_revenue_tracker_widget_display'
    );
}

function wp_revenue_tracker_widget_display() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_tracker';
    $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
    echo '<p><strong>Total Revenue:</strong> $' . number_format($total_revenue, 2) . '</p>';
}

// Add REST API endpoint for revenue data (for future premium features)
add_action('rest_api_init', function () {
    register_rest_route('wp-revenue-tracker/v1', '/revenue', array(
        'methods' => 'GET',
        'callback' => 'wp_revenue_tracker_api_get_revenue',
        'permission_callback' => '__return_true',
    ));
});

function wp_revenue_tracker_api_get_revenue($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_tracker';
    $revenue_data = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC");
    return rest_ensure_response($revenue_data);
}

// Add premium features notice (for freemium model)
add_action('admin_notices', 'wp_revenue_tracker_premium_notice');

function wp_revenue_tracker_premium_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>WP Revenue Tracker Premium</strong> for advanced analytics, export, and integration features!</p></div>';
    }
}
?>