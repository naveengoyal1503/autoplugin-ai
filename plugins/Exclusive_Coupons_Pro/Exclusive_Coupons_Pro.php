/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays personalized, trackable coupon codes for affiliate partners, boosting conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
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
        add_shortcode('ecp_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_ecp_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_ecp_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecp_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(20) NOT NULL,
            affiliate_url varchar(500) NOT NULL,
            description text,
            usage_count int DEFAULT 0,
            max_uses int DEFAULT 0,
            expires datetime DEFAULT NULL,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ecp-script', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ecp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'ECP Coupons', 'manage_options', 'ecp-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ecp_add_coupon'])) {
            $this->add_coupon(
                sanitize_text_field($_POST['code']),
                esc_url_raw($_POST['affiliate_url']),
                sanitize_textarea_field($_POST['description']),
                intval($_POST['max_uses']),
                $_POST['expires']
            );
        }
        echo '<div class="wrap"><h1>Manage Coupons</h1><form method="post">';
        echo '<table class="form-table"><tr><th>Code</th><td><input type="text" name="code" required /></td></tr>';
        echo '<tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width:400px;" required /></td></tr>';
        echo '<tr><th>Description</th><td><textarea name="description" rows="3" cols="50"></textarea></td></tr>';
        echo '<tr><th>Max Uses</th><td><input type="number" name="max_uses" /></td></tr>';
        echo '<tr><th>Expires</th><td><input type="datetime-local" name="expires" /></td></tr>';
        echo '<tr><td colspan="2"><input type="submit" name="ecp_add_coupon" class="button-primary" value="Add Coupon" /></td></tr></table></form>';
        $this->list_coupons();
        echo '</div>';
    }

    private function add_coupon($code, $url, $desc, $max_uses, $expires) {
        global $wpdb;
        $table = $wpdb->prefix . 'ecp_coupons';
        $data = array(
            'code' => $code,
            'affiliate_url' => $url,
            'description' => $desc,
            'max_uses' => $max_uses,
            'expires' => $expires ?: null
        );
        $wpdb->insert($table, $data);
    }

    private function list_coupons() {
        global $wpdb;
        $table = $wpdb->prefix . 'ecp_coupons';
        $coupons = $wpdb->get_results("SELECT * FROM $table ORDER BY created DESC");
        if ($coupons) {
            echo '<h2>Existing Coupons</h2><table class="wp-list-table widefat fixed striped"><thead><tr><th>Code</th><th>URL</th><th>Uses</th><th>Max Uses</th><th>Expires</th></tr></thead><tbody>';
            foreach ($coupons as $coupon) {
                echo '<tr><td>' . esc_html($coupon->code) . '</td><td>' . esc_html($coupon->affiliate_url) . '</td><td>' . $coupon->usage_count . '</td><td>' . $coupon->max_uses . '</td><td>' . ($coupon->expires ? date('Y-m-d H:i', strtotime($coupon->expires)) : 'Never') . '</td></tr>';
            }
            echo '</tbody></table>';
        }
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        if (!$atts['id']) return '';
        global $wpdb;
        $table = $wpdb->prefix . 'ecp_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $atts['id']));
        if (!$coupon || ($coupon->max_uses > 0 && $coupon->usage_count >= $coupon->max_uses) || ($coupon->expires && strtotime($coupon->expires) < current_time('timestamp'))) {
            return '<p class="ecp-expired">Coupon expired or invalid.</p>';
        }
        ob_start();
        echo '<div class="ecp-coupon" data-id="' . $coupon->id . '">';
        echo '<h3>Exclusive Deal: <strong>' . esc_html($coupon->code) . '</strong></h3>';
        echo '<p>' . esc_html($coupon->description) . '</p>';
        echo '<a href="#" class="button ecp-use-coupon">Use Coupon</a>';
        echo '</div>';
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ecp_nonce', 'nonce');
        if (!current_user_can('read')) wp_die();
        global $wpdb;
        $id = intval($_POST['id']);
        $table = $wpdb->prefix . 'ecp_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($coupon && ($coupon->max_uses == 0 || $coupon->usage_count < $coupon->max_uses) && (!$coupon->expires || strtotime($coupon->expires) > current_time('timestamp'))) {
            $wpdb->query($wpdb->prepare("UPDATE $table SET usage_count = usage_count + 1 WHERE id = %d", $id));
            wp_redirect(add_query_arg('ecp_code', $coupon->code, $coupon->affiliate_url));
            exit;
        }
        wp_send_json_error('Invalid coupon');
    }
}

ExclusiveCouponsPro::get_instance();

// Premium notice
function ecp_premium_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro Premium</strong> for unlimited coupons, advanced analytics, and auto-generation! <a href="https://example.com/premium" target="_blank">Get it now ($49/year)</a></p></div>';
}
add_action('admin_notices', 'ecp_premium_notice');

// JS file content (base64 encoded for single file)
$js = "jQuery(document).ready(function($){ $('.ecp-use-coupon').click(function(e){ e.preventDefault(); var id = $(this).closest('.ecp-coupon').data('id'); $.post(ecp_ajax.ajax_url, {action: 'ecp_generate_coupon', id: id, nonce: ecp_ajax.nonce}, function(){ window.open('', '_blank'); }); }); });
file_put_contents(plugin_dir_path(__FILE__) . 'ecp-script.js', base64_decode('...')); // Simplified, use actual JS
?>