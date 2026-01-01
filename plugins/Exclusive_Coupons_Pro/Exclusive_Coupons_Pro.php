/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate, manage, and display exclusive affiliate coupons to boost conversions and earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function init() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_link text NOT NULL,
            discount varchar(50) DEFAULT '',
            expiry date DEFAULT NULL,
            usage_limit int DEFAULT 0,
            uses int DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons',
            'manage_options',
            'exclusive-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($_POST && isset($_POST['submit'])) {
            $data = array(
                'title' => sanitize_text_field($_POST['title']),
                'code' => sanitize_text_field($_POST['code']),
                'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
                'discount' => sanitize_text_field($_POST['discount']),
                'expiry' => sanitize_text_field($_POST['expiry']),
                'usage_limit' => intval($_POST['usage_limit']),
                'active' => isset($_POST['active']) ? 1 : 0
            );
            if ($id) {
                $wpdb->update($table_name, $data, array('id' => $id));
            } else {
                $wpdb->insert($table_name, $data);
            }
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }

        if ($action === 'edit' && $id) {
            $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
        }

        if ($action === 'delete' && $id) {
            $wpdb->delete($table_name, array('id' => $id));
            echo '<div class="notice notice-success"><p>Coupon deleted!</p></div>';
            $action = 'list';
        }

        echo '<div class="wrap"><h1>' . ($action === 'edit' ? 'Edit' : 'Manage') . ' Coupons</h1>';

        if ($action === 'list') {
            $coupons = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Discount</th><th>Active</th><th>Actions</th></tr></thead><tbody>';
            foreach ($coupons as $coupon) {
                echo '<tr><td>' . $coupon->id . '</td><td>' . esc_html($coupon->title) . '</td><td>' . esc_html($coupon->code) . '</td><td>' . esc_html($coupon->discount) . '</td><td>' . ($coupon->active ? 'Yes' : 'No') . '</td><td><a href="?page=exclusive-coupons&action=edit&id=' . $coupon->id . '">Edit</a> | <a href="?page=exclusive-coupons&action=delete&id=' . $coupon->id . '" onclick="return confirm(\'Delete?\')">Delete</a></td></tr>';
            }
            echo '</tbody></table>';
        }

        echo '<h2>' . ($action === 'edit' ? 'Edit' : 'Add New') . ' Coupon</h2><form method="post">';
        echo '<p><label>Title: <input type="text" name="title" value="' . (isset($coupon->title) ? esc_attr($coupon->title) : '') . '" required></label></p>';
        echo '<p><label>Code: <input type="text" name="code" value="' . (isset($coupon->code) ? esc_attr($coupon->code) : '') . '" required></label></p>';
        echo '<p><label>Affiliate Link: <input type="url" name="affiliate_link" style="width:100%;" value="' . (isset($coupon->affiliate_link) ? esc_attr($coupon->affiliate_link) : '') . '" required></label></p>';
        echo '<p><label>Discount: <input type="text" name="discount" value="' . (isset($coupon->discount) ? esc_attr($coupon->discount) : '') . '"></label></p>';
        echo '<p><label>Expiry: <input type="date" name="expiry" value="' . (isset($coupon->expiry) ? esc_attr($coupon->expiry) : '') . '"></label></p>';
        echo '<p><label>Usage Limit: <input type="number" name="usage_limit" value="' . (isset($coupon->usage_limit) ? esc_attr($coupon->usage_limit) : '0') . '"></label></p>';
        echo '<p><label>Active: <input type="checkbox" name="active" ' . (isset($coupon->active) && $coupon->active ? 'checked' : '') . '></label></p>';
        echo '<p><input type="submit" name="submit" class="button-primary" value="Save"></p>';
        echo '</form>';

        if ($action === 'list') {
            echo '<p>Use shortcode <code>[exclusive_coupon id="X"]</code> to display coupons on posts/pages.</p>';
        }
        echo '</div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $id = intval($atts['id']);
        if (!$id) return '';

        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND active = 1", $id));
        if (!$coupon) return '';

        // Check expiry
        if ($coupon->expiry && strtotime($coupon->expiry) < current_time('timestamp')) return '<p>This coupon has expired.</p>';

        // Check usage
        if ($coupon->usage_limit > 0 && $coupon->uses >= $coupon->usage_limit) return '<p>This coupon has reached its usage limit.</p>';

        $link = add_query_arg('coupon_used', $coupon->id, $coupon->affiliate_link);

        ob_start();
        echo '<div class="exclusive-coupon" style="border:2px solid #0073aa; padding:20px; margin:20px 0; background:#f9f9f9;">';
        echo '<h3>' . esc_html($coupon->title) . '</h3>';
        echo '<p><strong>Code:</strong> <code>' . esc_html($coupon->code) . '</code></p>';
        if ($coupon->discount) echo '<p><strong>Discount:</strong> ' . esc_html($coupon->discount) . '</p>';
        echo '<p><a href="' . esc_url($link) . '" class="button" style="background:#0073aa; color:white; padding:10px 20px; text-decoration:none;" target="_blank">Redeem Now & Track</a></p>';
        echo '</div>';
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-coupons', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'exclusive-coupons') !== false) {
            wp_enqueue_style('exclusive-coupons-admin', plugin_dir_url(__FILE__) . 'admin-style.css', array(), '1.0.0');
        }
    }
}

new ExclusiveCouponsPro();

// Track coupon usage
add_action('init', function() {
    if (isset($_GET['coupon_used'])) {
        $id = intval($_GET['coupon_used']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET uses = uses + 1 WHERE id = %d", $id));
    }
});

// Premium notice
add_action('admin_notices', function() {
    if (!function_exists('is_plugin_active')) return;
    $screen = get_current_screen();
    if ($screen->id === 'toplevel_page_exclusive-coupons') {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Premium</strong> for unlimited coupons, analytics dashboard, auto-generation, and API integrations! <a href="https://example.com/premium" target="_blank">Get Premium</a></p></div>';
    }
});