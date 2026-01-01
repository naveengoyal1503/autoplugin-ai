/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking to boost your commissions.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features
        }
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            affiliate_link text NOT NULL,
            code varchar(50) DEFAULT '' NOT NULL,
            discount varchar(50) DEFAULT '' NOT NULL,
            expiry date DEFAULT NULL,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $atts['id']));

        if (!$coupon) {
            return '<p>No coupon found.</p>';
        }

        // Track click
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $atts['id']));

        $expiry = $coupon->expiry ? 'Expires: ' . date('M j, Y', strtotime($coupon->expiry)) : 'No expiry';

        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9;">
            <h3><?php echo esc_html($coupon->title); ?></h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($coupon->code); ?></code></p>
            <p><strong>Discount:</strong> <?php echo esc_html($coupon->discount); ?></p>
            <p><?php echo esc_html($expiry); ?></p>
            <p><strong>Clicks:</strong> <?php echo intval($coupon->clicks); ?></p>
            <a href="<?php echo esc_url($coupon->affiliate_link . (strpos($coupon->affiliate_link, '?') ? '&' : '?') . 'ref=' . get_bloginfo('url')); ?>" target="_blank" class="button" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none;">Get Deal & Apply Code</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');

        $title = sanitize_text_field($_POST['title']);
        $link = esc_url_raw($_POST['link']);
        $discount = sanitize_text_field($_POST['discount']);
        $expiry = !empty($_POST['expiry']) ? sanitize_text_field($_POST['expiry']) : null;
        $code = substr(md5(uniqid()), 0, 10);

        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        $wpdb->insert(
            $table_name,
            array(
                'title' => $title,
                'affiliate_link' => $link,
                'code' => $code,
                'discount' => $discount,
                'expiry' => $expiry
            )
        );

        $id = $wpdb->insert_id;
        wp_send_json_success(array('id' => $id, 'code' => $code));
    }
}

// Admin menu
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', 'acv_admin_page');
    });

    function acv_admin_page() {
        echo '<div class="wrap"><h1>Affiliate Coupon Vault</h1>';
        echo '<div id="acv-form">
            <form id="acv-coupon-form">
                <p><label>Title: <input type="text" name="title" required style="width:300px;"></label></p>
                <p><label>Affiliate Link: <input type="url" name="link" required style="width:300px;"></label></p>
                <p><label>Discount: <input type="text" name="discount" placeholder="e.g. 20% OFF" style="width:300px;"></label></p>
                <p><label>Expiry (YYYY-MM-DD): <input type="date" name="expiry"></label></p>
                <p><button type="submit" class="button-primary">Generate Coupon</button></p>
            </form>
            <div id="acv-shortcode"></div>
        </div>';
        echo '</div>';
    }
}

AffiliateCouponVault::get_instance();

// Dummy JS file content - in real plugin, save as acv-script.js
/*
jQuery(document).ready(function($) {
    $('#acv-coupon-form').on('submit', function(e) {
        e.preventDefault();
        $.post(acv_ajax.ajax_url, {
            action: 'acv_generate_coupon',
            nonce: acv_ajax.nonce,
            title: $('input[name="title"]').val(),
            link: $('input[name="link"]').val(),
            discount: $('input[name="discount"]').val(),
            expiry: $('input[name="expiry"]').val()
        }, function(res) {
            if (res.success) {
                $('#acv-shortcode').html('<p>Shortcode: <code>[affiliate_coupon id="' + res.data.id + '"] </code><br>Code: <strong>' + res.data.code + '</strong></p>');
            }
        });
    });
});
*/
?>