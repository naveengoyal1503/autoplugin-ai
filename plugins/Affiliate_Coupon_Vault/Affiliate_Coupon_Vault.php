/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
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
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            $this->create_table();
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugins_url('style.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugins_url('script.js', __FILE__), array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function admin_enqueue_scripts($hook) {
        if ('settings_page' !== get_current_screen()->id) return;
        wp_enqueue_script('jquery');
    }

    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url varchar(500) NOT NULL,
            discount varchar(50) DEFAULT '',
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            expires datetime DEFAULT NULL,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
        flush_rewrite_rules();
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_coupon($_POST);
        }
        $coupons = $this->get_coupons();
        include 'admin/settings.php';
    }

    private function save_coupon($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        $wpdb->insert(
            $table_name,
            array(
                'name' => sanitize_text_field($data['name']),
                'code' => sanitize_text_field($data['code']),
                'affiliate_url' => esc_url_raw($data['affiliate_url']),
                'discount' => sanitize_text_field($data['discount']),
                'max_uses' => intval($data['max_uses']),
                'expires' => !empty($data['expires']) ? $data['expires'] : null
            )
        );
    }

    private function get_coupons() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY created DESC");
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts, 'affiliate_coupon');
        $coupon = $this->get_coupon($atts['id']);
        if (!$coupon) return '';

        ob_start();
        include 'public/coupon-display.php';
        return ob_get_clean();
    }

    private function get_coupon($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_pro_upsell() {
    if (!function_exists('acv_pro_is_active')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro</strong> unlocks unlimited coupons, analytics, and more! <a href="https://example.com/pro" target="_blank">Upgrade now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_pro_upsell');