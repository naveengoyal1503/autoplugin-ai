/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLinkOptimizer.php
*/
<?php
/**
 * Plugin Name: AffiliateLinkOptimizer
 * Plugin URI: https://example.com/affiliatelinkoptimizer
 * Description: Automatically optimize and track affiliate links for higher conversions and revenue.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

// Prevent direct access
define('ABSPATH') or die('No script kiddies please!');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'alo_activate');
register_deactivation_hook(__FILE__, 'alo_deactivate');

function alo_activate() {
    // Create database table for link tracking
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_links';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        url text NOT NULL,
        clicks int(11) DEFAULT '0',
        conversions int(11) DEFAULT '0',
        created_at datetime DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function alo_deactivate() {
    // Optional: Clean up on deactivation
}

// Add admin menu
add_action('admin_menu', 'alo_admin_menu');
function alo_admin_menu() {
    add_menu_page(
        'Affiliate Link Optimizer',
        'Affiliate Links',
        'manage_options',
        'affiliate-link-optimizer',
        'alo_admin_page',
        'dashicons-chart-bar'
    );
}

// Admin page
function alo_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_links';
    $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1>Affiliate Link Optimizer</h1>
        <p>Track and optimize your affiliate links for better conversions.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>URL</th>
                    <th>Clicks</th>
                    <th>Conversions</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link): ?>
                <tr>
                    <td><?php echo $link->id; ?></td>
                    <td><a href="<?php echo esc_url($link->url); ?>" target="_blank">View</a></td>
                    <td><?php echo $link->clicks; ?></td>
                    <td><?php echo $link->conversions; ?></td>
                    <td><?php echo $link->created_at; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Shortcode to display affiliate link
add_shortcode('affiliate_link', 'alo_shortcode');
function alo_shortcode($atts) {
    $atts = shortcode_atts(array(
        'url' => '',
        'text' => 'Click here'
    ), $atts, 'affiliate_link');

    if (empty($atts['url'])) return '';

    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_links';
    $wpdb->insert(
        $table_name,
        array(
            'url' => $atts['url'],
            'created_at' => current_time('mysql')
        )
    );
    $link_id = $wpdb->insert_id;

    $link = home_url("/?alo_id=$link_id");
    return '<a href="' . esc_url($link) . '" target="_blank">' . esc_html($atts['text']) . '</a>';
}

// Handle link redirection and tracking
add_action('init', 'alo_track_click');
function alo_track_click() {
    if (isset($_GET['alo_id'])) {
        $link_id = intval($_GET['alo_id']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_links';
        $link = $wpdb->get_row($wpdb->prepare("SELECT url FROM $table_name WHERE id = %d", $link_id));
        if ($link) {
            $wpdb->update(
                $table_name,
                array('clicks' => $wpdb->get_var($wpdb->prepare("SELECT clicks FROM $table_name WHERE id = %d", $link_id)) + 1),
                array('id' => $link_id)
            );
            wp_redirect($link->url);
            exit;
        }
    }
}

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'alo_plugin_action_links');
function alo_plugin_action_links($links) {
    $settings_link = '<a href="admin.php?page=affiliate-link-optimizer">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
?>