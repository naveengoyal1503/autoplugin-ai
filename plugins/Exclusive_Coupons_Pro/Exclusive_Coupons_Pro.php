/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons to boost conversions and revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            description text NOT NULL,
            affiliate_link varchar(500) NOT NULL,
            discount varchar(20) NOT NULL,
            brand varchar(100) NOT NULL,
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            expiry date DEFAULT NULL,
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
        add_menu_page('Exclusive Coupons', 'Coupons', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['add_coupon'])) {
            $this->add_coupon($_POST);
        }
        if (isset($_GET['delete'])) {
            $this->delete_coupon($_GET['delete']);
        }
        $coupons = $this->get_coupons();
        include 'admin-view.php';
    }

    private function add_coupon($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $code = sanitize_text_field($data['code']);
        $wpdb->insert(
            $table_name,
            array(
                'code' => $code,
                'description' => sanitize_textarea_field($data['description']),
                'affiliate_link' => esc_url_raw($data['affiliate_link']),
                'discount' => sanitize_text_field($data['discount']),
                'brand' => sanitize_text_field($data['brand']),
                'max_uses' => intval($data['max_uses']),
                'expiry' => $data['expiry']
            )
        );
    }

    private function delete_coupon($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $wpdb->delete($table_name, array('id' => intval($id)), array('%d'));
    }

    private function get_coupons() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        return $wpdb->get_results("SELECT * FROM $table_name WHERE is_active = 1 ORDER BY created_at DESC");
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        if (empty($atts['code'])) return '';
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE code = %s AND is_active = 1", $atts['code']));
        if (!$coupon) return '<p>Coupon not found or expired.</p>';
        if ($coupon->max_uses > 0 && $coupon->uses >= $coupon->max_uses) return '<p>Coupon uses exhausted.</p>';
        if ($coupon->expiry && $coupon->expiry < date('Y-m-d')) return '<p>Coupon expired.</p>';
        $this->increment_uses($coupon->id);
        ob_start();
        ?>
        <div class="exclusive-coupon" style="border: 2px dashed #0073aa; padding: 20px; background: #f9f9f9; margin: 20px 0;">
            <h3><?php echo esc_html($coupon->brand); ?> Exclusive Deal!</h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($coupon->code); ?></code></p>
            <p><strong>Discount:</strong> <?php echo esc_html($coupon->discount); ?></p>
            <p><?php echo esc_html($coupon->description); ?></p>
            <a href="<?php echo esc_url($coupon->affiliate_link); ?>" target="_blank" class="button button-primary" style="padding: 10px 20px;">Redeem Now & Shop</a>
        </div>
        <?php
        return ob_get_clean();
    }

    private function increment_uses($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET uses = uses + 1 WHERE id = %d", $id));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-coupons', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook != 'toplevel_page_exclusive-coupons') return;
        wp_enqueue_style('exclusive-coupons-admin', plugin_dir_url(__FILE__) . 'admin-style.css', array(), '1.0.0');
    }
}

new ExclusiveCouponsPro();

// Freemium notice
function exclusive_coupons_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Upgrade to premium for unlimited coupons, analytics, auto-expiry, and brand API integrations. <a href="https://example.com/premium" target="_blank">Get Pro</a></p></div>';
}
add_action('admin_notices', 'exclusive_coupons_pro_notice');