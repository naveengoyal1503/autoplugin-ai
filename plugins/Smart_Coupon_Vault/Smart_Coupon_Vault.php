/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Smart Coupon Vault
 * Plugin URI: https://example.com/smart-coupon-vault
 * Description: Automatically generates, manages, and displays personalized coupon codes and affiliate deals to boost conversions and revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('scv_coupon_display', array($this, 'coupon_display_shortcode'));
        add_action('wp_ajax_scv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_scv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scv_coupons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            affiliate_url varchar(500) DEFAULT '',
            discount varchar(20) DEFAULT '',
            brand varchar(100) DEFAULT '',
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            expires datetime DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scv-script', plugin_dir_url(__FILE__) . 'scv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('scv-script', 'scv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Smart Coupon Vault', 'Coupon Vault', 'manage_options', 'scv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['scv_add_coupon'])) {
            $this->add_coupon($_POST);
        }
        echo '<div class="wrap"><h1>Smart Coupon Vault</h1><form method="post">';
        echo '<table class="form-table"><tr><th>Code</th><td><input type="text" name="code" required></td></tr>';
        echo '<tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width:400px;"></td></tr>';
        echo '<tr><th>Discount</th><td><input type="text" name="discount" placeholder="20% off"></td></tr>';
        echo '<tr><th>Brand</th><td><input type="text" name="brand"></td></tr>';
        echo '<tr><th>Max Uses</th><td><input type="number" name="max_uses" value="0"></td></tr>';
        echo '<tr><th>Expires</th><td><input type="datetime-local" name="expires"></td></tr></table>';
        echo '<p><input type="submit" name="scv_add_coupon" class="button-primary" value="Add Coupon"></p></form>';

        // List coupons
        global $wpdb;
        $coupons = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "scv_coupons ORDER BY created DESC");
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Code</th><th>Brand</th><th>Discount</th><th>Uses</th><th>Actions</th></tr></thead><tbody>';
        foreach ($coupons as $coupon) {
            echo '<tr><td>' . $coupon->id . '</td><td>' . esc_html($coupon->code) . '</td><td>' . esc_html($coupon->brand) . '</td>';
            echo '<td>' . esc_html($coupon->discount) . '</td><td>' . $coupon->uses . '/' . $coupon->max_uses . '</td>';
            echo '<td><a href="?page=scv-settings&delete=' . $coupon->id . '" onclick="return confirm(\'Delete?\')">Delete</a></td></tr>';
        }
        echo '</tbody></table></div>';

        if (isset($_GET['delete'])) {
            $wpdb->delete($wpdb->prefix . 'scv_coupons', array('id' => intval($_GET['delete'])));
            wp_redirect(admin_url('options-general.php?page=scv-settings'));
            exit;
        }
    }

    private function add_coupon($data) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'scv_coupons', array(
            'code' => sanitize_text_field($data['code']),
            'affiliate_url' => esc_url_raw($data['affiliate_url']),
            'discount' => sanitize_text_field($data['discount']),
            'brand' => sanitize_text_field($data['brand']),
            'max_uses' => intval($data['max_uses']),
            'expires' => !empty($data['expires']) ? $data['expires'] : null
        ));
    }

    public function coupon_display_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        ob_start();
        echo '<div class="scv-coupons">';
        $this->display_coupons($atts['limit']);
        echo '</div>';
        return ob_get_clean();
    }

    private function display_coupons($limit = 5) {
        global $wpdb;
        $coupons = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "scv_coupons WHERE active = 1 AND (max_uses = 0 OR uses < max_uses) AND (expires IS NULL OR expires > %s) ORDER BY RAND() LIMIT %d", current_time('mysql'), $limit));
        foreach ($coupons as $coupon) {
            $style = $coupon->uses >= $coupon->max_uses ? 'style="opacity:0.5;"' : '';
            echo '<div class="scv-coupon" ' . $style . '>';
            echo '<h3>' . esc_html($coupon->brand) . ' - ' . esc_html($coupon->discount) . '</h3>';
            echo '<p>Code: <strong>' . esc_html($coupon->code) . '</strong></p>';
            if ($coupon->affiliate_url) {
                echo '<a href="' . esc_url($coupon->affiliate_url) . '" target="_blank" class="button">Get Deal</a> ';
            }
            echo '<button class="scv-copy-btn" data-code="' . esc_attr($coupon->code) . '">Copy Code</button>';
            echo '</div>';
        }
    }

    public function ajax_generate_coupon() {
        global $wpdb;
        $code = wp_generate_password(8, false);
        $wpdb->insert($wpdb->prefix . 'scv_coupons', array('code' => $code));
        wp_send_json_success(array('code' => $code));
    }
}

new SmartCouponVault();

// Premium upsell notice
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Coupon Vault Pro</strong> for analytics, unlimited coupons, and affiliate tracking! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
});

// Basic CSS
add_action('wp_head', function() {
    echo '<style>.scv-coupon {border:1px solid #ddd; padding:15px; margin:10px 0; background:#f9f9f9;}.scv-copy-btn {background:#0073aa; color:white; border:none; padding:5px 10px; cursor:pointer;}</style>';
});

// JS for copy functionality
add_action('wp_footer', function() {
    ?><script>jQuery(document).ready(function($) {
        $('.scv-copy-btn').click(function() {
            var code = $(this).data('code');
            navigator.clipboard.writeText(code).then(function() {
                $(this).text('Copied!');
            }.bind(this));
        });
    });</script><?php
});