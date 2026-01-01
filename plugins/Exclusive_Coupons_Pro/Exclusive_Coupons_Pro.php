/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate, manage, and display exclusive affiliate coupons with personalized promo codes to boost conversions and monetize your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function init() {
        $this->create_table();
        register_post_type('exclusive_coupon', array(
            'labels' => array('name' => 'Exclusive Coupons', 'singular_name' => 'Coupon'),
            'public' => true,
            'show_ui' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-cart',
        ));
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) DEFAULT '' NOT NULL,
            affiliate_link text DEFAULT '' NOT NULL,
            discount varchar(20) DEFAULT '' NOT NULL,
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            expires datetime DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=exclusive_coupon', 'Manage Coupons', 'Manage Coupons', 'manage_options', 'manage-coupons', array($this, 'manage_page'));
    }

    public function manage_page() {
        if (isset($_POST['add_coupon'])) {
            $this->add_coupon($_POST);
        }
        echo '<div class="wrap"><h1>Manage Exclusive Coupons</h1><form method="post">';
        echo '<table class="form-table"><tr><th>Code</th><td><input type="text" name="code" required /></td></tr>';
        echo '<tr><th>Affiliate Link</th><td><input type="url" name="affiliate_link" style="width:400px;" required /></td></tr>';
        echo '<tr><th>Discount</th><td><input type="text" name="discount" placeholder="e.g., 20% OFF" /></td></tr>';
        echo '<tr><th>Max Uses</th><td><input type="number" name="max_uses" value="0" /></td></tr>';
        echo '<tr><th>Expires</th><td><input type="datetime-local" name="expires" /></td></tr>';
        echo '<tr><td colspan="2"><input type="submit" name="add_coupon" class="button-primary" value="Add Coupon" /></td></tr></table></form>';
        $this->list_coupons();
        echo '</div>';
    }

    private function add_coupon($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $expires = !empty($data['expires']) ? $data['expires'] : '0000-00-00 00:00:00';
        $wpdb->insert($table_name, array(
            'code' => sanitize_text_field($data['code']),
            'affiliate_link' => esc_url_raw($data['affiliate_link']),
            'discount' => sanitize_text_field($data['discount']),
            'max_uses' => intval($data['max_uses']),
            'expires' => $expires,
        ));
    }

    private function list_coupons() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $coupons = $wpdb->get_results("SELECT * FROM $table_name");
        if ($coupons) {
            echo '<h2>Active Coupons</h2><table class="wp-list-table widefat fixed striped"><thead><tr><th>Code</th><th>Discount</th><th>Uses</th><th>Max Uses</th><th>Expires</th></tr></thead><tbody>';
            foreach ($coupons as $coupon) {
                echo '<tr><td>' . esc_html($coupon->code) . '</td><td>' . esc_html($coupon->discount) . '</td><td>' . $coupon->uses . '</td><td>' . $coupon->max_uses . '</td><td>' . $coupon->expires . '</td></tr>';
            }
            echo '</tbody></table>';
        }
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        return $this->render_coupon($atts['code']);
    }

    private function render_coupon($code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE code = %s", $code));
        if (!$coupon) return '<p>Coupon not found.</p>';
        if ($coupon->max_uses > 0 && $coupon->uses >= $coupon->max_uses) return '<p>Coupon uses exhausted.</p>';
        if ($coupon->expires !== '0000-00-00 00:00:00' && current_time('mysql') > $coupon->expires) return '<p>Coupon expired.</p>';
        ob_start();
        ?>
        <div class="exclusive-coupon" style="border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; text-align: center;">
            <h3>🎉 Exclusive Deal: <strong><?php echo esc_html($coupon->discount); ?></strong></h3>
            <p>Use code: <strong><?php echo esc_html($coupon->code); ?></strong></p>
            <a href="<?php echo esc_url($coupon->affiliate_link); ?}" class="button" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;" target="_blank">Get Deal Now! (Affiliate Link)</a>
        </div>
        <?php
        $wpdb->update($table_name, array('uses' => $coupon->uses + 1), array('id' => $coupon->id));
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-coupons', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'exclusive_coupon') !== false) {
            wp_enqueue_style('exclusive-coupons-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
        }
    }
}

new ExclusiveCouponsPro();

// Premium upsell notice
function exclusive_coupons_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Unlock unlimited coupons, analytics, and custom branding. <a href="https://example.com/premium" target="_blank">Upgrade Now ($49/year)</a></p></div>';
}
add_action('admin_notices', 'exclusive_coupons_pro_notice');