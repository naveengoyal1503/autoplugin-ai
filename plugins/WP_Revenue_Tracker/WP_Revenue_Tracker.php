/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track and visualize revenue from ads, affiliate links, digital products, and memberships.
 * Version: 1.0
 * Author: WP Dev Team
 */

// Prevent direct access
define('ABSPATH') or die('No script kiddies please!');

// Register admin menu
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
add_action('admin_menu', 'wp_revenue_tracker_menu');

// Dashboard page
function wp_revenue_tracker_dashboard() {
    // Check user capability
    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }

    // Handle form submission
    if (isset($_POST['submit_revenue'])) {
        $revenue_type = sanitize_text_field($_POST['revenue_type']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);
        $notes = sanitize_textarea_field($_POST['notes']);

        $revenue_data = get_option('wp_revenue_tracker_data', array());
        $revenue_data[] = array(
            'type' => $revenue_type,
            'amount' => $amount,
            'date' => $date,
            'notes' => $notes
        );
        update_option('wp_revenue_tracker_data', $revenue_data);
        echo '<div class="notice notice-success"><p>Revenue entry added.</p></div>';
    }

    // Display form
    $revenue_data = get_option('wp_revenue_tracker_data', array());
    $total_revenue = array_sum(array_column($revenue_data, 'amount'));
    ?>
    <div class="wrap">
        <h1>WP Revenue Tracker</h1>
        <p>Total Revenue: $<?php echo number_format($total_revenue, 2); ?></p>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="revenue_type">Revenue Type</label></th>
                    <td>
                        <select name="revenue_type" id="revenue_type" required>
                            <option value="ads">Ads</option>
                            <option value="affiliate">Affiliate</option>
                            <option value="digital_product">Digital Product</option>
                            <option value="membership">Membership</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="amount">Amount ($)</label></th>
                    <td><input type="number" step="0.01" name="amount" id="amount" required /></td>
                </tr>
                <tr>
                    <th><label for="date">Date</label></th>
                    <td><input type="date" name="date" id="date" required /></td>
                </tr>
                <tr>
                    <th><label for="notes">Notes</label></th>
                    <td><textarea name="notes" id="notes"></textarea></td>
                </tr>
            </table>
            <?php submit_button('Add Revenue', 'primary', 'submit_revenue'); ?>
        </form>

        <h2>Revenue History</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revenue_data as $entry): ?>
                <tr>
                    <td><?php echo esc_html($entry['type']); ?></td>
                    <td>$<?php echo number_format($entry['amount'], 2); ?></td>
                    <td><?php echo esc_html($entry['date']); ?></td>
                    <td><?php echo esc_html($entry['notes']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Initialize plugin
function wp_revenue_tracker_init() {
    if (!get_option('wp_revenue_tracker_data')) {
        update_option('wp_revenue_tracker_data', array());
    }
}
add_action('init', 'wp_revenue_tracker_init');
?>