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

define('WP_REVENUE_TRACKER_VERSION', '1.0');
define('WP_REVENUE_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_REVENUE_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'wp_revenue_tracker_activate');
register_deactivation_hook(__FILE__, 'wp_revenue_tracker_deactivate');

function wp_revenue_tracker_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_tracker';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        source varchar(50) NOT NULL,
        amount decimal(10,2) NOT NULL,
        date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function wp_revenue_tracker_deactivate() {
    // Optional: Cleanup or scheduling tasks
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

    if (isset($_POST['add_revenue'])) {
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);
        $wpdb->insert($table_name, array(
            'source' => $source,
            'amount' => $amount,
            'date' => $date
        ));
    }

    $revenues = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC");
    $total = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
    ?>
    <div class="wrap">
        <h1>WP Revenue Tracker</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="source">Source</label></th>
                    <td>
                        <select name="source" id="source">
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
            </table>
            <p class="submit">
                <input type="submit" name="add_revenue" class="button-primary" value="Add Revenue" />
            </p>
        </form>
        <h2>Revenue Overview</h2>
        <p><strong>Total Revenue: $<?php echo number_format($total, 2); ?></strong></p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Source</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revenues as $rev): ?>
                <tr>
                    <td><?php echo $rev->id; ?></td>
                    <td><?php echo ucfirst($rev->source); ?></td>
                    <td>$<?php echo number_format($rev->amount, 2); ?></td>
                    <td><?php echo $rev->date; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Shortcode to display total revenue on frontend
add_shortcode('wp_revenue_total', 'wp_revenue_total_shortcode');
function wp_revenue_total_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_tracker';
    $total = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
    return '<p>Total Revenue: $' . number_format($total, 2) . '</p>';
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'wp_revenue_tracker_admin_enqueue');
function wp_revenue_tracker_admin_enqueue($hook) {
    if ('toplevel_page_wp-revenue-tracker' !== $hook) {
        return;
    }
    wp_enqueue_style('wp-revenue-tracker-admin', WP_REVENUE_TRACKER_PLUGIN_URL . 'admin.css');
}

// Optional: Add premium features notice
add_action('admin_notices', 'wp_revenue_tracker_premium_notice');
function wp_revenue_tracker_premium_notice() {
    if (isset($_GET['page']) && $_GET['page'] === 'wp-revenue-tracker') {
        echo '<div class="notice notice-info"><p>Upgrade to Premium for advanced analytics, integrations, and export features.</p></div>';
    }
}
?>