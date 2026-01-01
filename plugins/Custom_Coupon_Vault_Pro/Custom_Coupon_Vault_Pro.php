/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Vault_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Vault Pro
 * Plugin URI: https://example.com/coupon-vault
 * Description: Generate, manage, and display exclusive custom coupons to boost affiliate earnings and user engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: custom-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class CustomCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('coupon_vault', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coupon_vault';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            discount varchar(50) DEFAULT '',
            affiliate_link text DEFAULT '',
            expiry_date datetime DEFAULT NULL,
            usage_limit int DEFAULT 0,
            used_count int DEFAULT 0,
            brand varchar(255) DEFAULT '',
            description text,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function admin_menu() {
        add_menu_page(
            'Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'coupon-vault',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coupon_vault';
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($_POST && isset($_POST['submit'])) {
            $data = array(
                'title' => sanitize_text_field($_POST['title']),
                'code' => strtoupper(sanitize_text_field($_POST['code'])),
                'discount' => sanitize_text_field($_POST['discount']),
                'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
                'expiry_date' => $_POST['expiry_date'],
                'usage_limit' => intval($_POST['usage_limit']),
                'brand' => sanitize_text_field($_POST['brand']),
                'description' => sanitize_textarea_field($_POST['description']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            );
            if ($id) {
                $wpdb->update($table_name, $data, array('id' => $id));
            } else {
                $wpdb->insert($table_name, $data);
            }
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }

        if (isset($_GET['delete'])) {
            $wpdb->delete($table_name, array('id' => $id));
            echo '<div class="notice notice-success"><p>Coupon deleted!</p></div>';
        }

        $coupons = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        $coupon = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;

        echo '<div class="wrap">';
        echo '<h1>' . ($action == 'edit' ? 'Edit' : 'Add New') . ' Coupon</h1>';
        echo '<form method="post">';
        echo '<table class="form-table">';
        echo '<tr><th>Title</th><td><input type="text" name="title" value="' . esc_attr($coupon ? $coupon->title : '') . '" class="regular-text" required></td></tr>';
        echo '<tr><th>Code</th><td><input type="text" name="code" value="' . esc_attr($coupon ? $coupon->code : '') . '" class="regular-text" required></td></tr>';
        echo '<tr><th>Discount</th><td><input type="text" name="discount" value="' . esc_attr($coupon ? $coupon->discount : '') . '" placeholder="e.g. 20% OFF"></td></tr>';
        echo '<tr><th>Affiliate Link</th><td><input type="url" name="affiliate_link" value="' . esc_url($coupon ? $coupon->affiliate_link : '') . '" class="regular-text"></td></tr>';
        echo '<tr><th>Expiry Date</th><td><input type="datetime-local" name="expiry_date" value="' . ($coupon && $coupon->expiry_date ? date('Y-m-d\TH:i', strtotime($coupon->expiry_date)) : '') . '"></td></tr>';
        echo '<tr><th>Usage Limit</th><td><input type="number" name="usage_limit" value="' . esc_attr($coupon ? $coupon->usage_limit : '') . '" min="0"></td></tr>';
        echo '<tr><th>Brand</th><td><input type="text" name="brand" value="' . esc_attr($coupon ? $coupon->brand : '') . '" class="regular-text"></td></tr>';
        echo '<tr><th>Description</th><td><textarea name="description" rows="5" class="large-text">' . esc_textarea($coupon ? $coupon->description : '') . '</textarea></td></tr>';
        echo '<tr><th>Active</th><td><input type="checkbox" name="is_active" ' . ($coupon && $coupon->is_active ? 'checked' : '') . ' value="1"></td></tr>';
        echo '</table>';
        echo '<p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Coupon"></p>';
        echo '</form>';

        if ($action != 'edit') {
            echo '<h2>Active Coupons</h2>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Brand</th><th>Expiry</th><th>Used</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            foreach ($coupons as $c) {
                $expired = $c->expiry_date && strtotime($c->expiry_date) < current_time('timestamp');
                echo '<tr' . ($expired ? ' class="expired"' : '') . '>';
                echo '<td>' . $c->id . '</td>';
                echo '<td>' . esc_html($c->title) . '</td>';
                echo '<td>' . esc_html($c->code) . '</td>';
                echo '<td>' . esc_html($c->brand) . '</td>';
                echo '<td>' . ($c->expiry_date ? date('Y-m-d H:i', strtotime($c->expiry_date)) : 'No expiry') . '</td>';
                echo '<td>' . $c->used_count . '/' . $c->usage_limit . '</td>';
                echo '<td><a href="?page=coupon-vault&action=edit&id=' . $c->id . '">Edit</a> | <a href="?page=coupon-vault&delete=1&id=' . $c->id . '" onclick="return confirm(\'Delete?\')">Delete</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    public function coupon_shortcode($atts) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coupon_vault';
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE is_active = 1 AND (expiry_date > %s OR expiry_date IS NULL) ORDER BY created_at DESC LIMIT %d",
            current_time('mysql'),
            intval($atts['limit'])
        ));
        ob_start();
        echo '<div class="coupon-vault">';
        foreach ($coupons as $coupon) {
            $used_out = $coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit;
            $expired = $coupon->expiry_date && strtotime($coupon->expiry_date) < current_time('timestamp');
            if ($used_out || $expired) continue;
            echo '<div class="coupon-item">';
            echo '<h3>' . esc_html($coupon->title) . '</h3>';
            echo '<div class="coupon-code">' . esc_html($coupon->code) . '</div>';
            if ($coupon->discount) echo '<div class="discount">' . esc_html($coupon->discount) . '</div>';
            if ($coupon->brand) echo '<div class="brand">' . esc_html($coupon->brand) . '</div>';
            if ($coupon->description) echo '<p>' . esc_html($coupon->description) . '</p>';
            if ($coupon->affiliate_link) {
                echo '<a href="' . esc_url($coupon->affiliate_link) . '" class="coupon-link" target="_blank">Shop Now & Save</a>';
            }
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook != 'toplevel_page_coupon-vault') return;
        wp_enqueue_style('coupon-vault-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }
}

new CustomCouponVault();

/* Pro Notice */
function coupon_vault_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Custom Coupon Vault Pro:</strong> Unlock unlimited coupons, usage tracking, analytics, and more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'coupon_vault_pro_notice');

/* Inline CSS for simplicity */
function coupon_vault_inline_styles() {
    echo '<style>
    .coupon-vault { display: grid; gap: 20px; }
    .coupon-item { border: 2px dashed #007cba; padding: 20px; border-radius: 10px; background: #f9f9f9; }
    .coupon-code { font-size: 2em; font-weight: bold; color: #007cba; background: white; padding: 10px; display: inline-block; margin: 10px 0; }
    .discount { color: #d63638; font-size: 1.2em; }
    .brand { font-weight: bold; margin-bottom: 10px; }
    .coupon-link { display: inline-block; background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    .coupon-link:hover { background: #005a87; }
    .expired { opacity: 0.5; }
    </style>';
}
add_action('wp_head', 'coupon_vault_inline_styles');
add_action('admin_head', 'coupon_vault_inline_styles');