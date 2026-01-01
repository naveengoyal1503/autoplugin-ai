/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive, trackable discount coupons for your audience to boost conversions and engagement.
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
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('ecp_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Enter coupons as JSON: [{ "code": "SAVE20", "description": "20% off at Example Store", "affiliate_link": "https://example.com/ref", "uses_left": 100, "expiry": "2026-12-31" }]</p>
                <?php submit_button(); ?>
            </form>
            <p><strong>Shortcode:</strong> [exclusive_coupon id="1"]</p>
            <p><em>Upgrade to Pro for unlimited coupons, analytics, and custom designs!</em></p>
        </div>
        <?php
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecp_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) DEFAULT '' NOT NULL,
            description text,
            affiliate_link varchar(255),
            uses_left int DEFAULT 999999,
            uses int DEFAULT 0,
            expiry date,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', json_encode(array(
                array(
                    'code' => 'WELCOME10',
                    'description' => '10% off your first purchase!',
                    'affiliate_link' => 'https://example.com',
                    'uses_left' => 50,
                    'expiry' => '2026-06-30'
                )
            )));
        }
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 1), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecp_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $atts['id']), ARRAY_A);
        if (!$coupon || strtotime($coupon['expiry']) < time() || $coupon['uses_left'] <= 0) {
            return '<p class="ecp-expired">Coupon expired or invalid.</p>';
        }
        $onclick = "jQuery.post(ajaxurl, {action: 'ecp_use_coupon', id: {$atts['id']}});";
        return "<div class='ecp-coupon'><h3>Exclusive Deal!</h3><p>{$coupon['description']}</p><input type='text' value='{$coupon['code']}' readonly onclick='this.select();'><a href='{$coupon['affiliate_link']}' target='_blank' class='button' onclick=\"$onclick\">Redeem Now</a></div>";
    }
}

// AJAX handler
add_action('wp_ajax_ecp_use_coupon', 'ecp_handle_use');
add_action('wp_ajax_nopriv_ecp_use_coupon', 'ecp_handle_use');
function ecp_handle_use() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecp_coupons';
    $id = intval($_POST['id']);
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET uses = uses + 1, uses_left = uses_left - 1 WHERE id = %d", $id));
    wp_die();
}

new ExclusiveCouponsPro();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Unlock unlimited coupons, analytics, and premium features! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
});

// Minimal CSS
/* Add to style.css file: */
/* .ecp-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; } .ecp-coupon input { font-size: 24px; padding: 10px; width: 200px; } .ecp-expired { color: red; } */