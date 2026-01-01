/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create, manage, and display exclusive affiliate coupons with tracking to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
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
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-frontend', plugin_dir_url(__FILE__) . 'acv.css', array(), '1.0.0');
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_pro', sanitize_text_field($_POST['acv_pro']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $pro = get_option('acv_pro', '');
        echo '<div class="wrap"><h1>Affiliate Coupon Vault Settings</h1><form method="post"><table class="form-table"><tr><th>Pro License Key</th><td><input type="text" name="acv_pro" value="' . esc_attr($pro) . '" /></td></tr></table><p><input type="submit" name="submit" class="button-primary" value="Save" /></p></form><p><strong>Pro Features:</strong> Unlimited coupons, analytics, custom designs. <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'acv_coupons';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url text NOT NULL,
            description text,
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            expiry date,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function ajax_save_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        global $wpdb;
        $table = $wpdb->prefix . 'acv_coupons';
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'code' => sanitize_text_field($_POST['code']),
            'affiliate_url' => esc_url_raw($_POST['url']),
            'description' => sanitize_textarea_field($_POST['desc']),
            'max_uses' => intval($_POST['max_uses']),
            'expiry' => sanitize_text_field($_POST['expiry'])
        );
        $wpdb->insert($table, $data);
        wp_send_json_success('Coupon saved!');
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 10), $atts, 'acv_coupons');
        global $wpdb;
        $table = $wpdb->prefix . 'acv_coupons';
        $coupons = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE (max_uses = 0 OR uses < max_uses) AND (expiry IS NULL OR expiry >= CURDATE()) ORDER BY created DESC LIMIT %d", $atts['limit']));
        ob_start();
        echo '<div class="acv-vault">';
        foreach ($coupons as $coupon) {
            $uses_left = $coupon->max_uses ? ($coupon->max_uses - $coupon->uses) : 'Unlimited';
            echo '<div class="acv-coupon"><h3>' . esc_html($coupon->title) . '</h3><p>' . esc_html($coupon->description) . '</p><strong>Code: ' . esc_html($coupon->code) . '</strong><br>Uses left: ' . $uses_left . '<br><a href="' . esc_url($coupon->affiliate_url) . '" target="_blank" class="button">Shop Now & Save</a></div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}

AffiliateCouponVault::get_instance();

// Pro check
function acv_is_pro() {
    $pro = get_option('acv_pro', '');
    return !empty($pro) && hash('sha256', $pro) === 'prohash123'; // Demo pro validation
}

// Frontend styles
/* Add to acv.css */
.acv-vault { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.acv-coupon { border: 2px solid #0073aa; padding: 20px; border-radius: 8px; background: #f9f9f9; }
.acv-coupon h3 { color: #0073aa; }

// Frontend JS
/* Add to acv.js */
jQuery(document).ready(function($) {
    $('.acv-use-coupon').click(function(e) {
        e.preventDefault();
        // Track click (pro feature)
        if (acv_is_pro()) {
            $.post(acv_ajax.ajax_url, {action: 'track_click', coupon_id: $(this).data('id'), nonce: acv_ajax.nonce});
        }
        window.open($(this).attr('href'), '_blank');
    });
});

?>