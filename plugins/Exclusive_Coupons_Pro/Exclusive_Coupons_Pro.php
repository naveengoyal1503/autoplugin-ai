/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_ecp_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_ecp_track_click', array($this, 'ajax_track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ecp-frontend', 'ecp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'ecp-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ecp_coupon_data'])) {
            update_option('ecp_coupons', sanitize_text_field(wp_unslash($_POST['ecp_coupon_data'])));
        }
        $coupons = get_option('ecp_coupons', '[]');
        include plugin_dir_path(__FILE__) . 'admin/settings.php';
    }

    public function ajax_save_coupon() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $coupon = array(
            'code' => sanitize_text_field($_POST['code']),
            'affiliate_url' => esc_url_raw($_POST['url']),
            'description' => sanitize_text_field($_POST['desc']),
            'expires' => sanitize_text_field($_POST['expires']),
            'id' => uniqid()
        );
        $coupons = json_decode(get_option('ecp_coupons', '[]'), true);
        $coupons[] = $coupon;
        update_option('ecp_coupons', json_encode($coupons));
        wp_send_json_success($coupon);
    }

    public function ajax_track_click() {
        global $wpdb;
        $coupon_id = sanitize_text_field($_POST['coupon_id']);
        $wpdb->insert(
            $wpdb->prefix . 'ecp_clicks',
            array('coupon_id' => $coupon_id, 'ip' => $_SERVER['REMOTE_ADDR'], 'timestamp' => current_time('mysql')),
            array('%s', '%s', '%s')
        );
        $url = get_transient('ecp_coupon_' . $coupon_id);
        if ($url) {
            wp_send_json_success($url);
        }
    }

    public function shortcode_coupons($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = json_decode(get_option('ecp_coupons', '[]'), true);
        $output = '<div class="ecp-coupons">';
        foreach ($coupons as $coupon) {
            if ($atts['id'] && $coupon['id'] !== $atts['id']) continue;
            $expired = strtotime($coupon['expires']) < time();
            $output .= '<div class="ecp-coupon' . ($expired ? ' expired' : '') . '" data-id="' . esc_attr($coupon['id']) . '">';
            $output .= '<h3>' . esc_html($coupon['code']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            if (!$expired) {
                $output .= '<a href="#" class="ecp-use-coupon">Use Coupon</a>';
                set_transient('ecp_coupon_' . $coupon['id'], $coupon['affiliate_url'], 3600);
            } else {
                $output .= '<span>Expired</span>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecp_clicks';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            coupon_id varchar(50) NOT NULL,
            ip varchar(45) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }
}

// Pro check
function ecp_is_pro() {
    return file_exists(plugin_dir_path(__FILE__) . 'pro/pro.php');
}

new ExclusiveCouponsPro();

// Shortcode
add_shortcode('ecp_coupons', array(new ExclusiveCouponsPro(), 'shortcode_coupons'));

// Free version notice
if (!ecp_is_pro()) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro</strong> for unlimited coupons and analytics!</p></div>';
    });
}

// Minimal assets (inline for single file)
add_action('wp_head', function() {
    echo '<style>.ecp-coupons { display: flex; flex-wrap: wrap; gap: 20px; }.ecp-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }.ecp-coupon a { background: #0073aa; color: white; padding: 10px; text-decoration: none; border-radius: 4px; }.ecp-coupon.expired { opacity: 0.5; }</style>';
    echo '<script>jQuery(document).ready(function($) { $(".ecp-use-coupon").click(function(e) { e.preventDefault(); var id = $(this).closest(".ecp-coupon").data("id"); $.post(ecp_ajax.ajaxurl, {action: "ecp_track_click", coupon_id: id}, function(url) { if(url.success) window.open(url.data, "_blank"); }); }); });</script>';
});

// Admin page template (inline)
function ecp_admin_template() { ob_start(); ?> <div class="wrap"><h1>Exclusive Coupons Pro</h1><form method="post"><table class="form-table"><tr><th>New Coupon</th><td><input type="text" name="code" placeholder="COUPON123" required><br><input type="url" name="url" placeholder="https://affiliate.com/?coupon=COUPON123" required><br><input type="text" name="desc" placeholder="20% off on all products"><br><input type="datetime-local" name="expires" required></td></tr></table><input type="hidden" name="ecp_coupon_data" value="1"><p><input type="submit" class="button-primary" value="Add Coupon"></p></form><h2>Coupons</h2><ul><?php $coupons = json_decode(get_option('ecp_coupons', '[]'), true); foreach($coupons as $c) { echo '<li>' . esc_html($c['code']) . ' - ' . esc_html($c['description']) . ' <small>Expires: ' . esc_html($c['expires']) . '</small> [ecp_coupons id="' . esc_attr($c['id']) . '"]</li>'; } ?></ul></div><?php return ob_get_clean(); }
add_action('admin_init', function() { if(isset($_POST['ecp_coupon_data'])) { global $ExclusiveCouponsPro; $ExclusiveCouponsPro->ajax_save_coupon(); } });

// Override settings_page to use inline template
add_action('load-settings_page_ecp-settings', function() { $page = new ExclusiveCouponsPro(); ob_start(); echo ecp_admin_template(); $page->settings_page_content = ob_get_clean(); include 'admin/settings.php'; });