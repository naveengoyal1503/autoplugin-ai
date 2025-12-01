/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Plugin URI: https://example.com/wp-revenue-tracker
 * Description: Track and optimize your WordPress site's monetization efforts with detailed analytics, conversion tracking, and revenue insights.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_head', array($this, 'track_page_views'));
        add_action('wp_ajax_track_conversion', array($this, 'track_conversion'));
        add_action('wp_ajax_nopriv_track_conversion', array($this, 'track_conversion'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_admin_page'),
            'dashicons-chart-bar',
            6
        );
    }

    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC LIMIT 100");
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Page</th>
                        <th>Revenue</th>
                        <th>Conversions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row->date); ?></td>
                        <td><?php echo esc_html($row->page); ?></td>
                        <td>$<?php echo esc_html(number_format($row->revenue, 2)); ?></td>
                        <td><?php echo esc_html($row->conversions); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function track_page_views() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $page = $_SERVER['REQUEST_URI'];
        $date = current_time('mysql', 1);
        $wpdb->insert(
            $table_name,
            array(
                'date' => $date,
                'page' => $page,
                'revenue' => 0,
                'conversions' => 0
            ),
            array('%s', '%s', '%f', '%d')
        );
    }

    public function track_conversion() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenue_tracker';
        $page = sanitize_text_field($_POST['page']);
        $revenue = floatval($_POST['revenue']);
        $wpdb->update(
            $table_name,
            array('revenue' => $revenue, 'conversions' => 1),
            array('page' => $page),
            array('%f', '%d'),
            array('%s')
        );
        wp_die();
    }
}

register_activation_hook(__FILE__, function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'revenue_tracker';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        page varchar(255) NOT NULL,
        revenue decimal(10,2) DEFAULT '0.00' NOT NULL,
        conversions int(11) DEFAULT '0' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

new WP_Revenue_Tracker();
?>