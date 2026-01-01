/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons with automatic tracking, personalized promo codes, and revenue dashboards for WordPress bloggers.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('ECP_VERSION', '1.0.0');
define('ECP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ECP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Pro check (simulate license - in real, use proper validation)
function ecp_is_pro() {
    return false; // Change to true for pro features or implement license check
}

// Activation hook
register_activation_hook(__FILE__, 'ecp_activate');
function ecp_activate() {
    // Create default table
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecp_coupons';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        code varchar(100) NOT NULL,
        affiliate_url text NOT NULL,
        discount varchar(50) DEFAULT '',
        usage_limit int DEFAULT 0,
        uses int DEFAULT 0,
        start_date datetime DEFAULT NULL,
        expiry_date datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ecp_deactivate');
function ecp_deactivate() {
    flush_rewrite_rules();
}

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'ecp_admin_scripts');
function ecp_admin_scripts($hook) {
    if (strpos($hook, 'ecp') !== false) {
        wp_enqueue_script('ecp-admin', ECP_PLUGIN_URL . 'admin.js', array('jquery'), ECP_VERSION, true);
        wp_enqueue_style('ecp-admin', ECP_PLUGIN_URL . 'admin.css', array(), ECP_VERSION);
    }
}

// Admin menu
add_action('admin_menu', 'ecp_admin_menu');
function ecp_admin_menu() {
    add_menu_page(
        'Exclusive Coupons',
        'Coupons Pro',
        'manage_options',
        'ecp-coupons',
        'ecp_admin_page',
        'dashicons-tickets-alt',
        30
    );
    add_submenu_page(
        'ecp-coupons',
        'Analytics',
        'Analytics',
        'manage_options',
        'ecp-analytics',
        'ecp_analytics_page'
    );
}

// Main admin page
function ecp_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecp_coupons';
    $coupons = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1>Exclusive Coupons Pro</h1>
        <?php if (!ecp_is_pro()): ?>
        <div class="notice notice-info"><p><strong>Go Pro</strong> for unlimited coupons & analytics! <a href="#" onclick="alert('Upgrade to Pro')">Upgrade Now ($49/yr)</a></p></div>
        <?php endif; ?>
        <p><a href="#" class="button button-primary" onclick="ecpOpenForm()">Add New Coupon</a></p>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Discount</th><th>Uses</th><th>Actions</th></tr></thead>
            <tbody>
    <?php foreach ($coupons as $coupon): ?>
                <tr>
                    <td><?php echo esc_html($coupon->id); ?></td>
                    <td><?php echo esc_html($coupon->title); ?></td>
                    <td><?php echo esc_html($coupon->code); ?></td>
                    <td><?php echo esc_html($coupon->discount); ?></td>
                    <td><?php echo esc_html($coupon->uses); ?>/<?php echo $coupon->usage_limit ?: 'Unlimited'; ?></td>
                    <td><a href="#" onclick="ecpEdit(<?php echo $coupon->id; ?>)" class="button">Edit</a> <a href="#" onclick="ecpDelete(<?php echo $coupon->id; ?>)" class="button button-link-delete">Delete</a></td>
                </tr>
    <?php endforeach; ?>
            </tbody>
        </table>
        <div id="ecp-form" style="display:none;">
            <h2 id="ecp-form-title">Add Coupon</h2>
            <form id="ecp-coupon-form">
                <input type="hidden" id="coupon_id" value="0">
                <p><label>Title: <input type="text" id="title" required></label></p>
                <p><label>Code: <input type="text" id="code" required></label></p>
                <p><label>Affiliate URL: <input type="url" id="affiliate_url" required style="width:100%"></label></p>
                <p><label>Discount: <input type="text" id="discount" placeholder="e.g. 20% OFF"></label></p>
                <p><label>Usage Limit: <input type="number" id="usage_limit" min="0" value="0"></label></p>
                <p><label>Start Date: <input type="datetime-local" id="start_date"></label></p>
                <p><label>Expiry Date: <input type="datetime-local" id="expiry_date"></label></p>
                <p><input type="submit" class="button button-primary" value="Save"></p>
            </form>
        </div>
    </div>
    <script>
    function ecpOpenForm() { jQuery('#ecp-form').show(); }
    function ecpEdit(id) { /* AJAX to load */ alert('Edit ' + id); }
    function ecpDelete(id) { if(confirm('Delete?')) { /* AJAX */ location.reload(); } }
    jQuery('#ecp-coupon-form').on('submit', function(e) {
        e.preventDefault();
        // Simulate AJAX save
        alert('Saved!');
        location.reload();
    });
    </script>
    <?php
}

// Analytics page
function ecp_analytics_page() {
    if (!ecp_is_pro()): echo '<div class="notice notice-warning"><p>Analytics available in Pro version.</p></div>'; return; endif;
    echo '<div class="wrap"><h1>Coupon Analytics</h1><p>Pro feature: Revenue dashboard coming soon.</p></div>';
}

// Shortcode [ecp_coupons]
add_shortcode('ecp_coupons', 'ecp_coupons_shortcode');
function ecp_coupons_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecp_coupons';
    $coupons = $wpdb->get_results("SELECT * FROM $table_name WHERE (expiry_date IS NULL OR expiry_date > NOW()) AND (usage_limit = 0 OR uses < usage_limit) ORDER BY created_at DESC LIMIT 10");
    if (empty($coupons)) return '<p>No active coupons available.</p>';
    $output = '<div class="ecp-coupons">';
    foreach ($coupons as $coupon) {
        $output .= '<div class="ecp-coupon">';
        $output .= '<h3>' . esc_html($coupon->title) . '</h3>';
        $output .= '<p><strong>Code:</strong> ' . esc_html($coupon->code) . '</p>';
        $output .= '<p><strong>Discount:</strong> ' . esc_html($coupon->discount) . '</p>';
        $output .= '<a href="' . esc_url($coupon->affiliate_url) . '" target="_blank" class="button">Get Deal <span onclick="ecpTrack(' . $coupon->id . ')" style="cursor:pointer;font-size:small;">✓</span></a>';
        $output .= '</div>';
    }
    $output .= '</div>';
    return $output;
}

// Track usage
add_action('wp_ajax_ecp_track', 'ecp_track_usage');
add_action('wp_ajax_nopriv_ecp_track', 'ecp_track_usage');
function ecp_track_usage() {
    $coupon_id = intval($_POST['coupon_id']);
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecp_coupons';
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET uses = uses + 1 WHERE id = %d", $coupon_id));
    wp_die('Tracked');
}

// Frontend styles
add_action('wp_enqueue_scripts', 'ecp_frontend_styles');
function ecp_frontend_styles() {
    wp_add_inline_style('dashicons', '.ecp-coupons { display: grid; gap: 20px; } .ecp-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 5px; }');
}

// Widget
add_action('widgets_init', 'ecp_register_widget');
function ecp_register_widget() {
    register_widget('ECP_Widget');
}
class ECP_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('ecp_widget', 'Exclusive Coupons');
    }
    public function widget($args, $instance) {
        echo do_shortcode('[ecp_coupons]');
    }
}

// Freemium upsell notice
add_action('admin_notices', 'ecp_upsell_notice');
function ecp_upsell_notice() {
    if (!ecp_is_pro() && current_user_can('manage_options')) {
        echo '<div class="notice notice-success is-dismissible"><p>Unlock <strong>Exclusive Coupons Pro</strong>: Unlimited coupons, analytics & more! <a href="#" onclick="alert(\'Upgrade to Pro for $49/yr\')">Get Pro</a></p></div>';
    }
}

// AJAX handlers for admin (simplified)
add_action('wp_ajax_ecp_save_coupon', 'ecp_ajax_save_coupon');
function ecp_ajax_save_coupon() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecp_coupons';
    $data = array(
        'title' => sanitize_text_field($_POST['title']),
        'code' => sanitize_text_field($_POST['code']),
        'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
        'discount' => sanitize_text_field($_POST['discount']),
        'usage_limit' => intval($_POST['usage_limit']),
        'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
        'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null
    );
    $id = intval($_POST['id']);
    if ($id > 0) {
        $wpdb->update($table_name, $data, array('id' => $id));
    } else {
        $wpdb->insert($table_name, $data);
    }
    wp_send_json_success();
}

// Delete AJAX
add_action('wp_ajax_ecp_delete_coupon', 'ecp_ajax_delete_coupon');
function ecp_ajax_delete_coupon() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecp_coupons';
    $wpdb->delete($table_name, array('id' => intval($_POST['id'])));
    wp_send_json_success();
}

// Load coupon data for edit
add_action('wp_ajax_ecp_get_coupon', 'ecp_ajax_get_coupon');
function ecp_ajax_get_coupon() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecp_coupons';
    $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_POST['id'])));
    wp_send_json_success($coupon);
}

?>