/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Maximize revenue by rotating affiliate links, coupons, and sponsored content based on user engagement.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

// Prevent direct access
define('ABSPATH') or die('No script kiddies please!');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'wp_revenue_booster_activate');
register_deactivation_hook(__FILE__, 'wp_revenue_booster_deactivate');

function wp_revenue_booster_activate() {
    // Create custom table for storing link rotation data
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_booster';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        type varchar(20) NOT NULL,
        url text NOT NULL,
        title varchar(255),
        clicks int(11) DEFAULT 0,
        impressions int(11) DEFAULT 0,
        conversion_rate float DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function wp_revenue_booster_deactivate() {
    // Optional: Clean up on deactivation
}

// Add admin menu
add_action('admin_menu', 'wp_revenue_booster_menu');

function wp_revenue_booster_menu() {
    add_menu_page(
        'WP Revenue Booster',
        'Revenue Booster',
        'manage_options',
        'wp-revenue-booster',
        'wp_revenue_booster_admin_page',
        'dashicons-chart-bar'
    );
}

function wp_revenue_booster_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_booster';

    if (isset($_POST['add_link'])) {
        $type = sanitize_text_field($_POST['type']);
        $url = esc_url($_POST['url']);
        $title = sanitize_text_field($_POST['title']);
        $wpdb->insert($table_name, array('type' => $type, 'url' => $url, 'title' => $title));
    }

    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $wpdb->delete($table_name, array('id' => $id));
    }

    $links = $wpdb->get_results("SELECT * FROM $table_name");
    ?>
    <div class="wrap">
        <h1>WP Revenue Booster</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="type">Type</label></th>
                    <td>
                        <select name="type" id="type">
                            <option value="affiliate">Affiliate Link</option>
                            <option value="coupon">Coupon</option>
                            <option value="sponsored">Sponsored Content</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="title">Title</label></th>
                    <td><input type="text" name="title" id="title" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="url">URL</label></th>
                    <td><input type="url" name="url" id="url" class="regular-text" /></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="add_link" class="button-primary" value="Add Link" /></p>
        </form>
        <h2>Existing Links</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>URL</th>
                    <th>Clicks</th>
                    <th>Impressions</th>
                    <th>Conversion Rate</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link): ?>
                <tr>
                    <td><?php echo $link->id; ?></td>
                    <td><?php echo $link->type; ?></td>
                    <td><?php echo $link->title; ?></td>
                    <td><a href="<?php echo $link->url; ?>" target="_blank">Link</a></td>
                    <td><?php echo $link->clicks; ?></td>
                    <td><?php echo $link->impressions; ?></td>
                    <td><?php echo $link->conversion_rate; ?>%</td>
                    <td><a href="?page=wp-revenue-booster&delete=<?php echo $link->id; ?>" onclick="return confirm('Are you sure?');">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Shortcode to display a rotating link
add_shortcode('revenue_booster', 'wp_revenue_booster_shortcode');

function wp_revenue_booster_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_booster';
    $atts = shortcode_atts(array('type' => 'affiliate'), $atts);

    $links = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE type = %s ORDER BY conversion_rate DESC", $atts['type']));
    if (!$links) return '';

    // Select the best-performing link
    $link = $links;

    // Increment impressions
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET impressions = impressions + 1 WHERE id = %d", $link->id));

    return '<a href="' . $link->url . '" target="_blank" onclick="jQuery.post(ajaxurl, {action: \"revenue_booster_click\", id: ' . $link->id . '});">' . $link->title . '</a>';
}

// AJAX handler for clicks
add_action('wp_ajax_revenue_booster_click', 'wp_revenue_booster_click');
add_action('wp_ajax_nopriv_revenue_booster_click', 'wp_revenue_booster_click');

function wp_revenue_booster_click() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_booster';
    $id = intval($_POST['id']);
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $id));
    $clicks = $wpdb->get_var($wpdb->prepare("SELECT clicks FROM $table_name WHERE id = %d", $id));
    $impressions = $wpdb->get_var($wpdb->prepare("SELECT impressions FROM $table_name WHERE id = %d", $id));
    $conversion_rate = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET conversion_rate = %f WHERE id = %d", $conversion_rate, $id));
    wp_die();
}

// Enqueue AJAX script
add_action('wp_enqueue_scripts', 'wp_revenue_booster_enqueue_scripts');

function wp_revenue_booster_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'revenue_booster_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
}
?>