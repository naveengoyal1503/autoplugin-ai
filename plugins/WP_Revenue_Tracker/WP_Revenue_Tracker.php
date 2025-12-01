/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Plugin URI: https://example.com/wp-revenue-tracker
 * Description: Track and optimize your WordPress site's revenue streams with real-time analytics and actionable insights.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

// Exit if accessed directly
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

// Revenue Tracker page
function wp_revenue_tracker_page() {
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
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $revenue_records = get_option('wp_revenue_tracker_records', array());
        $revenue_records[] = $revenue_data;
        update_option('wp_revenue_tracker_records', $revenue_records);

        echo '<div class="notice notice-success"><p>Revenue record added successfully!</p></div>';
    }

    // Display form
    $revenue_records = get_option('wp_revenue_tracker_records', array());
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
                            <option value="services">Services</option>
                            <option value="donations">Donations</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="amount">Amount ($)</label></th>
                    <td><input type="number" name="amount" id="amount" step="0.01" required /></td>
                </tr>
                <tr>
                    <th><label for="notes">Notes</label></th>
                    <td><textarea name="notes" id="notes"></textarea></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="wp_revenue_tracker_submit" class="button-primary" value="Add Record" />
            </p>
        </form>

        <h2>Revenue Records</h2>
        <table class="widefat fixed">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Source</th>
                    <th>Amount</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revenue_records as $record): ?>
                <tr>
                    <td><?php echo esc_html($record['date']); ?></td>
                    <td><?php echo esc_html($record['source']); ?></td>
                    <td>$<?php echo esc_html(number_format($record['amount'], 2)); ?></td>
                    <td><?php echo esc_html($record['notes']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Add shortcode to display total revenue
function wp_revenue_tracker_shortcode($atts) {
    $atts = shortcode_atts(array(
        'source' => '',
    ), $atts, 'wp_revenue_tracker');

    $revenue_records = get_option('wp_revenue_tracker_records', array());
    $total = 0;

    foreach ($revenue_records as $record) {
        if (empty($atts['source']) || $record['source'] === $atts['source']) {
            $total += $record['amount'];
        }
    }

    return '<p>Total Revenue: $' . number_format($total, 2) . '</p>';
}
add_shortcode('wp_revenue_tracker', 'wp_revenue_tracker_shortcode');

// Add widget
class WP_Revenue_Tracker_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'wp_revenue_tracker_widget',
            'Revenue Tracker Widget',
            array('description' => 'Display total revenue in sidebar')
        );
    }

    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $source = $instance['source'];

        $revenue_records = get_option('wp_revenue_tracker_records', array());
        $total = 0;

        foreach ($revenue_records as $record) {
            if (empty($source) || $record['source'] === $source) {
                $total += $record['amount'];
            }
        }

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        echo '<p>Total Revenue: $' . number_format($total, 2) . '</p>';
        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Revenue';
        $source = !empty($instance['source']) ? $instance['source'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('source'); ?>">Source:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('source'); ?>" name="<?php echo $this->get_field_name('source'); ?>">
                <option value="" <?php selected($source, ''); ?>>All</option>
                <option value="ads" <?php selected($source, 'ads'); ?>>Ads</option>
                <option value="affiliate" <?php selected($source, 'affiliate'); ?>>Affiliate</option>
                <option value="products" <?php selected($source, 'products'); ?>>Products</option>
                <option value="services" <?php selected($source, 'services'); ?>>Services</option>
                <option value="donations" <?php selected($source, 'donations'); ?>>Donations</option>
            </select>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['source'] = (!empty($new_instance['source'])) ? strip_tags($new_instance['source']) : '';
        return $instance;
    }
}
function wp_revenue_tracker_register_widget() {
    register_widget('WP_Revenue_Tracker_Widget');
}
add_action('widgets_init', 'wp_revenue_tracker_register_widget');
