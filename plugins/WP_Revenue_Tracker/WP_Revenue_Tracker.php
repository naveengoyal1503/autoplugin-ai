/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Plugin URI: https://example.com/wp-revenue-tracker
 * Description: Track and visualize your WordPress site's revenue streams.
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
        'wp_revenue_tracker_page',
        'dashicons-chart-bar',
        6
    );
}
add_action('admin_menu', 'wp_revenue_tracker_menu');

// Main plugin page
function wp_revenue_tracker_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings
    if (isset($_POST['wp_revenue_tracker_save'])) {
        update_option('wp_revenue_tracker_ads', sanitize_text_field($_POST['ads']));
        update_option('wp_revenue_tracker_affiliates', sanitize_text_field($_POST['affiliates']));
        update_option('wp_revenue_tracker_memberships', sanitize_text_field($_POST['memberships']));
        update_option('wp_revenue_tracker_courses', sanitize_text_field($_POST['courses']));
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }

    // Get saved values
    $ads = get_option('wp_revenue_tracker_ads', '0');
    $affiliates = get_option('wp_revenue_tracker_affiliates', '0');
    $memberships = get_option('wp_revenue_tracker_memberships', '0');
    $courses = get_option('wp_revenue_tracker_courses', '0');

    // Display form
    echo '<div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="ads">Ad Revenue ($)</label></th>
                        <td><input type="number" step="0.01" name="ads" id="ads" value="' . esc_attr($ads) . '" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="affiliates">Affiliate Revenue ($)</label></th>
                        <td><input type="number" step="0.01" name="affiliates" id="affiliates" value="' . esc_attr($affiliates) . '" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="memberships">Membership Revenue ($)</label></th>
                        <td><input type="number" step="0.01" name="memberships" id="memberships" value="' . esc_attr($memberships) . '" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="courses">Course Revenue ($)</label></th>
                        <td><input type="number" step="0.01" name="courses" id="courses" value="' . esc_attr($courses) . '" class="regular-text" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="wp_revenue_tracker_save" id="wp_revenue_tracker_save" class="button button-primary" value="Save Changes" />
                </p>
            </form>
            <h2>Revenue Summary</h2>
            <p>Total Revenue: $' . number_format((float)$ads + (float)$affiliates + (float)$memberships + (float)$courses, 2) . '</p>
            <p><strong>Ad Revenue:</strong> $' . number_format((float)$ads, 2) . '</p>
            <p><strong>Affiliate Revenue:</strong> $' . number_format((float)$affiliates, 2) . '</p>
            <p><strong>Membership Revenue:</strong> $' . number_format((float)$memberships, 2) . '</p>
            <p><strong>Course Revenue:</strong> $' . number_format((float)$courses, 2) . '</p>
        </div>';
}

// Add shortcode for displaying revenue summary on front-end
function wp_revenue_tracker_shortcode($atts) {
    $ads = get_option('wp_revenue_tracker_ads', '0');
    $affiliates = get_option('wp_revenue_tracker_affiliates', '0');
    $memberships = get_option('wp_revenue_tracker_memberships', '0');
    $courses = get_option('wp_revenue_tracker_courses', '0');

    $total = (float)$ads + (float)$affiliates + (float)$memberships + (float)$courses;

    return '<div class="wp-revenue-tracker-summary">
                <h3>Revenue Summary</h3>
                <p>Total: $' . number_format($total, 2) . '</p>
                <p>Ad Revenue: $' . number_format((float)$ads, 2) . '</p>
                <p>Affiliate Revenue: $' . number_format((float)$affiliates, 2) . '</p>
                <p>Membership Revenue: $' . number_format((float)$memberships, 2) . '</p>
                <p>Course Revenue: $' . number_format((float)$courses, 2) . '</p>
            </div>';
}
add_shortcode('wp_revenue_tracker', 'wp_revenue_tracker_shortcode');

// Enqueue admin styles
function wp_revenue_tracker_admin_styles() {
    wp_enqueue_style('wp-revenue-tracker-admin', plugin_dir_url(__FILE__) . 'admin.css');
}
add_action('admin_enqueue_scripts', 'wp_revenue_tracker_admin_styles');

// Create admin CSS file
function wp_revenue_tracker_create_admin_css() {
    $css = ".wp-revenue-tracker-summary { background: #f7f7f7; padding: 15px; border-radius: 5px; }";
    file_put_contents(plugin_dir_path(__FILE__) . 'admin.css', $css);
}
register_activation_hook(__FILE__, 'wp_revenue_tracker_create_admin_css');

// Add widget for dashboard
function wp_revenue_tracker_dashboard_widget() {
    wp_add_dashboard_widget(
        'wp_revenue_tracker_dashboard_widget',
        'Revenue Tracker',
        'wp_revenue_tracker_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'wp_revenue_tracker_dashboard_widget');

function wp_revenue_tracker_dashboard_widget_content() {
    $ads = get_option('wp_revenue_tracker_ads', '0');
    $affiliates = get_option('wp_revenue_tracker_affiliates', '0');
    $memberships = get_option('wp_revenue_tracker_memberships', '0');
    $courses = get_option('wp_revenue_tracker_courses', '0');
    $total = (float)$ads + (float)$affiliates + (float)$memberships + (float)$courses;
    echo '<p>Total Revenue: $' . number_format($total, 2) . '</p>';
}
?>