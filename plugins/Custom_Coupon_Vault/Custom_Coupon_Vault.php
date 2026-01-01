/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Vault
 * Plugin URI: https://example.com/custom-coupon-vault
 * Description: Generate, manage, and display exclusive custom coupons and affiliate deals to boost conversions and monetize your WordPress site effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class CustomCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('coupon-vault-css', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('coupon-vault-js', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_coupon($_POST);
        }
        $coupons = $this->get_coupons();
        include 'admin-page.php';
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coupon_vault';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url text,
            discount varchar(50),
            expiry date,
            active tinyint(1) DEFAULT 1,
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function get_coupons($active = true) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coupon_vault';
        $where = $active ? 'WHERE active = 1' : '';
        return $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY id DESC");
    }

    private function save_coupon($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coupon_vault';
        $wpdb->insert($table_name, array(
            'title' => sanitize_text_field($data['title']),
            'code' => sanitize_text_field($data['code']),
            'affiliate_url' => esc_url_raw($data['affiliate_url']),
            'discount' => sanitize_text_field($data['discount']),
            'expiry' => sanitize_text_field($data['expiry']),
            'max_uses' => intval($data['max_uses']),
        ));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupon = $this->get_coupon_by_id($atts['id']);
        if (!$coupon || !$coupon->active) return '';
        ob_start();
        ?>
        <div class="coupon-vault-item" data-id="<?php echo $coupon->id; ?>">
            <h3><?php echo esc_html($coupon->title); ?></h3>
            <div class="coupon-code"><?php echo esc_html($coupon->code); ?></div>
            <?php if ($coupon->discount): ?>
            <div class="coupon-discount"><?php echo esc_html($coupon->discount); ?> OFF</div>
            <?php endif; ?>
            <?php if ($coupon->affiliate_url): ?>
            <a href="<?php echo esc_url($coupon->affiliate_url); ?>" class="coupon-btn" target="_blank">Get Deal</a>
            <?php endif; ?>
            <div class="coupon-expiry">Expires: <?php echo $coupon->expiry; ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_coupon_by_id($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coupon_vault';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }

    public function activate() {
        $this->create_table();
    }
}

new CustomCouponVault();

// Pro upsell notice
function coupon_vault_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Custom Coupon Vault Pro:</strong> Unlock unlimited coupons, analytics, and auto-expiration for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'coupon_vault_pro_notice');

// Minimal CSS
/* Add to style.css file */
/*
.coupon-vault-item { border: 2px dashed #007cba; padding: 20px; margin: 10px 0; background: #f9f9f9; }
.coupon-code { font-size: 24px; font-weight: bold; color: #007cba; }
.coupon-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
*/

// Minimal JS
/* Add to script.js file */
/*
jQuery(document).ready(function($) {
    $('.coupon-code').click(function() {
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val($(this).text()).select();
        document.execCommand('copy');
        $temp.remove();
        alert('Coupon code copied!');
    });
});
*/
?>