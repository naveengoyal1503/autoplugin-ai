/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create and manage affiliate coupons with tracking to boost your earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_acv_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_acv_delete_coupon', array($this, 'ajax_delete_coupon'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url varchar(500) NOT NULL,
            description text,
            expiry_date datetime DEFAULT NULL,
            clicks int DEFAULT 0,
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

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-styles', plugin_dir_url(__FILE__) . 'acv.css', array(), '1.0.0');
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        $coupons = $this->get_coupons();
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    private function get_coupons($id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        if ($id) {
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
        }
        return $wpdb->get_results("SELECT * FROM $table_name WHERE is_active = 1 ORDER BY created_at DESC");
    }

    public function ajax_save_coupon() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'code' => sanitize_text_field($_POST['code']),
            'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
            'description' => sanitize_textarea_field($_POST['description']),
            'expiry_date' => !empty($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $wpdb->update($table_name, $data, array('id' => intval($_POST['id'])));
        } else {
            $wpdb->insert($table_name, $data);
        }
        wp_send_json_success();
    }

    public function ajax_delete_coupon() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $wpdb->delete($table_name, array('id' => intval($_POST['id'])));
        wp_send_json_success();
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 10), $atts, 'acv_coupons');
        $coupons = $this->get_coupons();
        if (empty($coupons)) {
            return '<p>No coupons available.</p>';
        }
        ob_start();
        echo '<div class="acv-coupons-grid">';
        foreach ($coupons as $coupon) {
            echo '<div class="acv-coupon-card">';
            echo '<h3>' . esc_html($coupon->title) . '</h3>';
            echo '<p><strong>Code:</strong> ' . esc_html($coupon->code) . '</p>';
            if ($coupon->description) {
                echo '<p>' . esc_html($coupon->description) . '</p>';
            }
            if ($coupon->expiry_date) {
                echo '<p><em>Expires: ' . date('Y-m-d', strtotime($coupon->expiry_date)) . '</em></p>';
            }
            echo '<a href="' . esc_url(add_query_arg('acv_coupon', $coupon->id, $coupon->affiliate_url)) . '" class="acv-button" target="_blank">Get Deal (' . $coupon->clicks . ' clicks)</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}

// Track clicks
add_action('init', function() {
    if (isset($_GET['acv_coupon'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $coupon_id = intval($_GET['acv_coupon']);
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $coupon_id));
    }
});

// Embed JS and CSS
add_action('wp_head', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.acv-button').on('click', function() {
        var url = $(this).attr('href');
        gtag('event', 'coupon_click', {'event_category': 'affiliate', 'event_label': url});
    });
});
</script>
<style>
.acv-coupons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.acv-coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; }
.acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.acv-button:hover { background: #005a87; }
</style>
<?php });

AffiliateCouponVault::get_instance();