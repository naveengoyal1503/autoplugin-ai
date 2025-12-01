/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Plugin URI: https://example.com/wp-revenue-tracker
 * Description: Track and visualize revenue from ads, affiliate links, and product sales.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

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
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle form submission
    if (isset($_POST['wp_revenue_tracker_submit'])) {
        $revenue_data = array(
            'date' => sanitize_text_field($_POST['date']),
            'source' => sanitize_text_field($_POST['source']),
            'amount' => floatval($_POST['amount']),
        );
        $existing_data = get_option('wp_revenue_tracker_data', array());
        $existing_data[] = $revenue_data;
        update_option('wp_revenue_tracker_data', $existing_data);
        echo '<div class="notice notice-success"><p>Revenue data saved!</p></div>';
    }

    // Display form
    $data = get_option('wp_revenue_tracker_data', array());
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
                            <option value="products">Products</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="amount">Amount ($)</label></th>
                    <td><input type="number" step="0.01" name="amount" id="amount" required /></td>
                </tr>
            </table>
            <?php submit_button('Add Revenue', 'primary', 'wp_revenue_tracker_submit'); ?>
        </form>

        <h2>Revenue History</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Source</th>
                    <th>Amount ($)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $entry): ?>
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

// Shortcode to display revenue summary
function wp_revenue_tracker_shortcode($atts) {
    $atts = shortcode_atts(array(
        'source' => '',
    ), $atts, 'wp_revenue_tracker');

    $data = get_option('wp_revenue_tracker_data', array());
    $total = 0;
    foreach ($data as $entry) {
        if (empty($atts['source']) || $entry['source'] === $atts['source']) {
            $total += $entry['amount'];
        }
    }
    return '<p>Total Revenue: $' . number_format($total, 2) . '</p>';
}
add_shortcode('wp_revenue_tracker', 'wp_revenue_tracker_shortcode');

// Enqueue scripts and styles
function wp_revenue_tracker_enqueue() {
    wp_enqueue_style('wp-revenue-tracker', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('admin_enqueue_scripts', 'wp_revenue_tracker_enqueue');
?>