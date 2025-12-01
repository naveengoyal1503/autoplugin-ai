/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Plugin URI: https://example.com/wp-revenue-tracker
 * Description: Track and visualize revenue from ads, affiliate links, and digital product sales.
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
    if (isset($_POST['submit_revenue'])) {
        $revenue_type = sanitize_text_field($_POST['revenue_type']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);

        $revenue_data = get_option('wp_revenue_tracker_data', array());
        $revenue_data[] = array(
            'type' => $revenue_type,
            'amount' => $amount,
            'date' => $date
        );
        update_option('wp_revenue_tracker_data', $revenue_data);
    }

    // Get revenue data
    $revenue_data = get_option('wp_revenue_tracker_data', array());

    // Display dashboard
    ?>
    <div class="wrap">
        <h1>WP Revenue Tracker</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="revenue_type">Revenue Type</label></th>
                    <td>
                        <select name="revenue_type" id="revenue_type">
                            <option value="ads">Ads</option>
                            <option value="affiliate">Affiliate</option>
                            <option value="digital_product">Digital Product</option>
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
                <input type="submit" name="submit_revenue" class="button-primary" value="Add Revenue" />
            </p>
        </form>

        <h2>Revenue Overview</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Revenue Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revenue_data as $entry): ?>
                <tr>
                    <td><?php echo esc_html($entry['type']); ?></td>
                    <td>$<?php echo esc_html(number_format($entry['amount'], 2)); ?></td>
                    <td><?php echo esc_html($entry['date']); ?></td>
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
        'type' => 'all'
    ), $atts, 'revenue_summary');

    $revenue_data = get_option('wp_revenue_tracker_data', array());
    $total = 0;

    foreach ($revenue_data as $entry) {
        if ($atts['type'] === 'all' || $entry['type'] === $atts['type']) {
            $total += $entry['amount'];
        }
    }

    return '<p>Total Revenue (' . ucfirst($atts['type']) . '): $' . number_format($total, 2) . '</p>';
}
add_shortcode('revenue_summary', 'wp_revenue_tracker_shortcode');

// Enqueue scripts and styles
function wp_revenue_tracker_enqueue() {
    wp_enqueue_style('wp-revenue-tracker-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('admin_enqueue_scripts', 'wp_revenue_tracker_enqueue');
?>