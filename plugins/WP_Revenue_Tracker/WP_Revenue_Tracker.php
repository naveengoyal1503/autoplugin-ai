/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track and visualize revenue from ads, affiliate links, digital products, and sponsored content.
 * Version: 1.0
 * Author: Your Name
 */

define('WP_REVENUE_TRACKER_VERSION', '1.0');
define('WP_REVENUE_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_REVENUE_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'wp_revenue_tracker_activate');
register_deactivation_hook(__FILE__, 'wp_revenue_tracker_deactivate');

function wp_revenue_tracker_activate() {
    // Create table for revenue data
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_tracker';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
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
        'dashicons-chart-bar'
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
        $notes = sanitize_textarea_field($_POST['notes']);
        $wpdb->insert($table_name, array(
            'source' => $source,
            'amount' => $amount,
            'notes' => $notes
        ));
    }

    // Get revenue data
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
                            <option value="sponsored_content">Sponsored Content</option>
                            <option value="other">Other</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="amount">Amount</label></th>
                    <td><input type="number" step="0.01" name="amount" id="amount" required /></td>
                </tr>
                <tr>
                    <th><label for="notes">Notes</label></th>
                    <td><textarea name="notes" id="notes"></textarea></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="add_revenue" class="button-primary" value="Add Revenue" />
            </p>
        </form>
        <h2>Revenue History</h2>
        <table class="widefat fixed">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Source</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revenue_data as $row): ?>
                <tr>
                    <td><?php echo $row->id; ?></td>
                    <td><?php echo ucfirst($row->source); ?></td>
                    <td>$<?php echo number_format($row->amount, 2); ?></td>
                    <td><?php echo $row->date; ?></td>
                    <td><?php echo $row->notes; ?></td>
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
    $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
    return '<p>Total Revenue: $' . number_format($total_revenue, 2) . '</p>';
}

// Enqueue admin styles
add_action('admin_enqueue_scripts', 'wp_revenue_tracker_admin_styles');
function wp_revenue_tracker_admin_styles($hook) {
    if ('toplevel_page_wp-revenue-tracker' !== $hook) {
        return;
    }
    wp_enqueue_style('wp-revenue-tracker-admin', WP_REVENUE_TRACKER_PLUGIN_URL . 'admin.css');
}

// Create admin.css file if not exists
if (!file_exists(WP_REVENUE_TRACKER_PLUGIN_DIR . 'admin.css')) {
    file_put_contents(WP_REVENUE_TRACKER_PLUGIN_DIR . 'admin.css', "
.wrap h1 { margin-bottom: 20px; }
.form-table th { width: 120px; }
.widefat th, .widefat td { padding: 10px; }
");
}
?>