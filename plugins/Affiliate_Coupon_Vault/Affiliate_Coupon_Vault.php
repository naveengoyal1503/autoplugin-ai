/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create dynamic coupon sections with affiliate tracking to monetize your site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_save_coupon', array($this, 'save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'save_coupon'));
    }

    public function init() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url text NOT NULL,
            description text,
            expiry_date datetime DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            usage_count int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('affiliate-coupon-vault', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            $this->save_coupon_ajax();
        }
        $coupons = $this->get_coupons();
        include 'admin-page.php';
    }

    private function get_coupons() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        return $wpdb->get_results("SELECT * FROM $table_name WHERE active = 1 ORDER BY created_at DESC");
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'limit' => 5
        ), $atts);

        $coupons = $this->get_coupons();
        if ($atts['id']) {
            $coupons = array_filter($coupons, function($c) use ($atts) { return $c->id == $atts['id']; });
        } else {
            $coupons = array_slice($coupons, 0, $atts['limit']);
        }

        ob_start();
        echo '<div class="affiliate-coupon-vault">';
        foreach ($coupons as $coupon) {
            $expiry = $coupon->expiry_date ? 'Expires: ' . date('M j, Y', strtotime($coupon->expiry_date)) : 'No expiry';
            echo '<div class="coupon-item">';
            echo '<h3>' . esc_html($coupon->title) . '</h3>';
            echo '<p><strong>Code:</strong> ' . esc_html($coupon->code) . '</p>';
            echo '<p>' . esc_html($coupon->description) . '</p>';
            echo '<p>' . esc_html($expiry) . ' | Used: ' . $coupon->usage_count . ' times</p>';
            echo '<a href="' . esc_url($coupon->affiliate_url) . '" target="_blank" class="coupon-btn" data-coupon-id="' . $coupon->id . '">Get Deal <span class="code">' . esc_html($coupon->code) . '</span></a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function save_coupon() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'code' => sanitize_text_field($_POST['code']),
            'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
            'description' => sanitize_textarea_field($_POST['description']),
            'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null
        );
        $wpdb->insert($table_name, $data);
        wp_send_json_success('Coupon saved!');
    }

    public function save_coupon_ajax() {
        // Fallback for non-AJAX
        $_POST = $_REQUEST;
        $this->save_coupon();
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_pro_upsell() {
    if (!get_option('acv_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, analytics & more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_pro_upsell');

// Minimal CSS
/*
.affiliate-coupon-vault-style {
    .affiliate-coupon-vault { max-width: 800px; margin: 20px auto; }
    .coupon-item { background: #f9f9f9; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .coupon-btn { display: inline-block; background: #ff6b35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .coupon-btn:hover { background: #e55a2b; }
    .code { background: #333; color: #fff; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
}
*/
// Save as style.css in plugin folder

// Minimal JS
/*
$(document).ready(function() {
    $('.coupon-btn').click(function() {
        var id = $(this).data('coupon-id');
        $.post(ajax_object.ajax_url, {action: 'track_coupon_click', id: id}, function() {});
    });
});
*/
// Save as script.js in plugin folder

// Create admin-page.php manually or add inline form here for single file
?>