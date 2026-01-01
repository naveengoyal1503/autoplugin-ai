/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create, manage, and display exclusive affiliate coupons with affiliate links and expiration tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
        flush_rewrite_rules();
    }

    public function activate() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            affiliate_url varchar(500) NOT NULL,
            coupon_code varchar(100) DEFAULT '',
            expiry_date datetime DEFAULT NULL,
            image_url varchar(500) DEFAULT '',
            active tinyint(1) DEFAULT 1,
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'acv') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_scripts('wp-color-picker');
        }
    }

    public function admin_menu() {
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-coupons', array($this, 'admin_page'), 'dashicons-cart');
        add_submenu_page('acv-coupons', 'Add New Coupon', 'Add New', 'manage_options', 'acv-add', array($this, 'add_page'));
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $coupons = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        include plugin_dir_path(__FILE__) . 'admin-list.php';
    }

    public function add_page() {
        if (isset($_POST['submit'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'acv_coupons';
            $wpdb->insert(
                $table_name,
                array(
                    'title' => sanitize_text_field($_POST['title']),
                    'description' => sanitize_textarea_field($_POST['description']),
                    'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                    'coupon_code' => sanitize_text_field($_POST['coupon_code']),
                    'expiry_date' => $_POST['expiry_date'],
                    'image_url' => esc_url_raw($_POST['image_url']),
                    'active' => isset($_POST['active']) ? 1 : 0
                )
            );
            echo '<div class="notice notice-success"><p>Coupon added successfully!</p></div>';
        }
        include plugin_dir_path(__FILE__) . 'admin-add.php';
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'category' => ''
        ), $atts);

        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $where = 'WHERE active = 1';
        if (!empty($atts['category'])) {
            $where .= " AND title LIKE '%" . $wpdb->esc_like($atts['category']) . "%'";
        }
        $coupons = $wpdb->get_results("SELECT * FROM $table_name $where AND (expiry_date > NOW() OR expiry_date IS NULL) ORDER BY created_at DESC LIMIT " . intval($atts['limit']));

        ob_start();
        echo '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            $url = $coupon->affiliate_url . (strpos($coupon->affiliate_url, '?') === false ? '?' : '&') . 'ref=' . get_bloginfo('url') . '&coupon=' . $coupon->coupon_code;
            echo '<div class="acv-coupon">';
            if ($coupon->image_url) echo '<img src="' . esc_url($coupon->image_url) . '" alt="' . esc_attr($coupon->title) . '" style="max-width:100%;">';
            echo '<h3>' . esc_html($coupon->title) . '</h3>';
            echo '<p>' . esc_html($coupon->description) . '</p>';
            if ($coupon->coupon_code) echo '<div class="coupon-code">Code: <strong>' . esc_html($coupon->coupon_code) . '</strong></div>';
            echo '<a href="' . esc_url($url) . '" class="acv-button" target="_blank">Get Deal (' . $coupon->clicks . ' clicks)</a>';
            if ($coupon->expiry_date) echo '<small>Expires: ' . date('M j, Y', strtotime($coupon->expiry_date)) . '</small>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}

new AffiliateCouponVault();

// Track clicks
function acv_track_click() {
    if (isset($_GET['acv_click'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $id = intval($_GET['acv_click']);
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $id));
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT affiliate_url FROM $table_name WHERE id = %d", $id));
        if ($coupon) {
            wp_redirect($coupon->affiliate_url);
            exit;
        }
    }
}
add_action('init', 'acv_track_click');

// Minimal CSS
/* Add to style.css or inline */
/*
.acv-coupons { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #fff; }
.acv-button { background: #0073aa; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
.acv-button:hover { background: #005a87; }
.coupon-code { background: #f9f9f9; padding: 10px; margin: 10px 0; border-left: 4px solid #0073aa; }
*/

// Note: Create empty style.css, admin-list.php, admin-add.php in plugin folder for full functionality
// admin-list.php: Simple table listing coupons with edit/delete links
// admin-add.php: Form with fields for title, desc, url, code, expiry, image, active checkbox