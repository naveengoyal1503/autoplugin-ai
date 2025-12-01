/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track and optimize your WordPress site's revenue streams.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
function wp_revenue_tracker_menu() {
    add_menu_page(
        'Revenue Tracker',
        'Revenue Tracker',
        'manage_options',
        'wp-revenue-tracker',
        'wp_revenue_tracker_page',
        'dashicons-chart-bar',
        6
    );
}
add_action('admin_menu', 'wp_revenue_tracker_menu');

// Display admin page
function wp_revenue_tracker_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Handle form submission
    if (isset($_POST['submit_revenue'])) {
        $revenue_data = array(
            'date' => sanitize_text_field($_POST['date']),
            'source' => sanitize_text_field($_POST['source']),
            'amount' => floatval($_POST['amount'])
        );
        add_revenue_entry($revenue_data);
    }

    // Display revenue entries
    $entries = get_revenue_entries();
    ?>
    <div class="wrap">
        <h1>WP Revenue Tracker</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="date">Date</label></th>
                    <td><input type="date" name="date" id="date" required /></td>
                </tr>
                <tr>
                    <th><label for="source">Source</label></th>
                    <td>
                        <select name="source" id="source" required>
                            <option value="ads">Ads</option>
                            <option value="affiliate">Affiliate</option>
                            <option value="digital_sales">Digital Sales</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="amount">Amount ($)</label></th>
                    <td><input type="number" name="amount" id="amount" step="0.01" required /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_revenue" class="button-primary" value="Add Revenue" />
            </p>
        </form>

        <h2>Revenue Entries</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Source</th>
                    <th>Amount ($)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><?php echo esc_html($entry['date']); ?></td>
                    <td><?php echo esc_html($entry['source']); ?></td>
                    <td><?php echo esc_html($entry['amount']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Save revenue entry
function add_revenue_entry($data) {
    $entries = get_option('wp_revenue_tracker_entries', array());
    $entries[] = $data;
    update_option('wp_revenue_tracker_entries', $entries);
}

// Get revenue entries
function get_revenue_entries() {
    return get_option('wp_revenue_tracker_entries', array());
}

// Initialize plugin
function wp_revenue_tracker_init() {
    if (!get_option('wp_revenue_tracker_entries')) {
        update_option('wp_revenue_tracker_entries', array());
    }
}
add_action('init', 'wp_revenue_tracker_init');
