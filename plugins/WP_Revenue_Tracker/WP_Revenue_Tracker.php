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
        'dashicons-chart-bar'
    );
}
add_action('admin_menu', 'wp_revenue_tracker_menu');

// Dashboard page
function wp_revenue_tracker_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
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
        echo '<div class="notice notice-success"><p>Revenue recorded successfully!</p></div>';
    }
    
    // Display form and chart
    $revenue_data = get_option('wp_revenue_tracker_data', array());
    
    // Calculate totals
    $total_revenue = 0;
    $revenue_by_type = array();
    foreach ($revenue_data as $entry) {
        $total_revenue += $entry['amount'];
        if (!isset($revenue_by_type[$entry['type']])) {
            $revenue_by_type[$entry['type']] = 0;
        }
        $revenue_by_type[$entry['type']] += $entry['amount'];
    }
    
    // Sort by date
    usort($revenue_data, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Display dashboard
    ?>
    <div class="wrap">
        <h1>WP Revenue Tracker</h1>
        <p>Track and visualize your WordPress site's revenue streams.</p>
        
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="revenue_type">Revenue Type</label></th>
                    <td>
                        <select name="revenue_type" id="revenue_type" required>
                            <option value="ads">Ads</option>
                            <option value="affiliate">Affiliate</option>
                            <option value="digital_products">Digital Products</option>
                            <option value="memberships">Memberships</option>
                            <option value="other">Other</option>
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
            </table>
            <p class="submit">
                <input type="submit" name="submit_revenue" class="button-primary" value="Record Revenue" />
            </p>
        </form>
        
        <hr />
        
        <h2>Revenue Summary</h2>
        <p><strong>Total Revenue:</strong> $<?php echo number_format($total_revenue, 2); ?></p>
        <h3>By Type</h3>
        <ul>
            <?php foreach ($revenue_by_type as $type => $amount): ?>
                <li><?php echo ucfirst($type); ?>: $<?php echo number_format($amount, 2); ?></li>
            <?php endforeach; ?>
        </ul>
        
        <h2>Recent Revenue Entries</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($revenue_data, 0, 10) as $entry): ?>
                <tr>
                    <td><?php echo ucfirst($entry['type']); ?></td>
                    <td>$<?php echo number_format($entry['amount'], 2); ?></td>
                    <td><?php echo $entry['date']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Enqueue scripts and styles
function wp_revenue_tracker_enqueue() {
    wp_enqueue_style('wp-revenue-tracker', plugins_url('style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'wp_revenue_tracker_enqueue');

// Create style.css in plugin directory
function wp_revenue_tracker_create_style() {
    $style = "table.form-table th, table.form-table td { padding: 10px; }";
    file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $style);
}
register_activation_hook(__FILE__, 'wp_revenue_tracker_create_style');

// Add shortcode for displaying revenue summary on frontend (premium feature placeholder)
function wp_revenue_tracker_shortcode($atts) {
    // In premium version: display summary chart or stats
    return '<p>Revenue summary available in premium version.</p>';
}
add_shortcode('revenue_summary', 'wp_revenue_tracker_shortcode');

// Add settings link
function wp_revenue_tracker_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wp-revenue-tracker">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wp_revenue_tracker_settings_link');
?>