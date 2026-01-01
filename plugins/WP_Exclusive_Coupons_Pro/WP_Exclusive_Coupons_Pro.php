/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Generate exclusive affiliate coupons, track usage, and boost conversions with custom promo codes.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) exit;

class WPExclusiveCoupons {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('exclusive_coupon', [$this, 'coupon_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init() {
        $this->create_table();
        load_plugin_textdomain('wp-exclusive-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-exclusive-coupons', plugin_dir_url(__FILE__) . 'style.css', [], '1.0.0');
        wp_enqueue_script('wp-exclusive-coupons', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], '1.0.0', true);
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'exclusive_coupons';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url text NOT NULL,
            discount varchar(50) DEFAULT '',
            brand varchar(255) DEFAULT '',
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            expiry date DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons', 'manage_options', 'wp-exclusive-coupons', [$this, 'admin_page']);
    }

    public function admin_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'exclusive_coupons';

        if (isset($_POST['add_coupon']) && check_admin_referer('add_coupon_nonce')) {
            $wpdb->insert($table, [
                'title' => sanitize_text_field($_POST['title']),
                'code' => sanitize_text_field($_POST['code']),
                'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                'discount' => sanitize_text_field($_POST['discount']),
                'brand' => sanitize_text_field($_POST['brand']),
                'max_uses' => intval($_POST['max_uses']),
                'expiry' => sanitize_text_field($_POST['expiry'])
            ]);
        }

        $coupons = $wpdb->get_results("SELECT * FROM $table ORDER BY created DESC");
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons</h1>
            <form method="post">
                <?php wp_nonce_field('add_coupon_nonce'); ?>
                <table class="form-table">
                    <tr><th>Title</th><td><input type="text" name="title" required /></td></tr>
                    <tr><th>Promo Code</th><td><input type="text" name="code" required /></td></tr>
                    <tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width: 400px;" required /></td></tr>
                    <tr><th>Discount</th><td><input type="text" name="discount" placeholder="e.g., 20% OFF" /></td></tr>
                    <tr><th>Brand</th><td><input type="text" name="brand" /></td></tr>
                    <tr><th>Max Uses</th><td><input type="number" name="max_uses" /></td></tr>
                    <tr><th>Expiry Date</th><td><input type="date" name="expiry" /></td></tr>
                </table>
                <p><input type="submit" name="add_coupon" class="button-primary" value="Add Coupon" /></p>
            </form>
            <h2>Active Coupons</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Uses</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><?php echo $coupon->id; ?></td>
                        <td><?php echo esc_html($coupon->title); ?></td>
                        <td><?php echo esc_html($coupon->code); ?></td>
                        <td><?php echo $coupon->uses; ?>/<?php echo $coupon->max_uses ?: 'Unlimited'; ?></td>
                        <td><a href="[exclusive_coupon id='<?php echo $coupon->id; ?>' ]">Shortcode</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <style>
            .wrap { max-width: 800px; }
        </style>
        <?php
    }

    public function coupon_shortcode($atts) {
        global $wpdb;
        $atts = shortcode_atts(['id' => 0], $atts);
        $table = $wpdb->prefix . 'exclusive_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND active = 1", $atts['id']));

        if (!$coupon) return 'Coupon not found or inactive.';

        $today = current_time('Y-m-d');
        if ($coupon->expiry && $today > $coupon->expiry) {
            return '<div class="coupon-expired">Coupon expired!</div>';
        }

        if ($coupon->max_uses && $coupon->uses >= $coupon->max_uses) {
            return '<div class="coupon-maxed">Coupon uses exhausted!</div>';
        }

        $use_url = add_query_arg(['coupon_used' => $coupon->id], $coupon->affiliate_url);

        ob_start();
        ?>
        <div class="exclusive-coupon" data-id="<?php echo $coupon->id; ?>">
            <h3><?php echo esc_html($coupon->title); ?></h3>
            <p><strong>Code:</strong> <span class="coupon-code"><?php echo esc_html($coupon->code); ?></span></p>
            <?php if ($coupon->discount): ?><p><strong>Discount:</strong> <?php echo esc_html($coupon->discount); ?></p><?php endif; ?>
            <?php if ($coupon->brand): ?><p><strong>Brand:</strong> <?php echo esc_html($coupon->brand); ?></p><?php endif; ?>
            <a href="<?php echo esc_url($use_url); ?>" class="button coupon-btn" target="_blank">Redeem Now (Affiliate Link)</a>
            <p class="coupon-uses">Used: <?php echo $coupon->uses; ?>/<?php echo $coupon->max_uses ?: 'Unlimited'; ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}

new WPExclusiveCoupons();

// AJAX to track uses
add_action('wp_ajax_track_coupon_use', 'track_coupon_use');
add_action('wp_ajax_nopriv_track_coupon_use', 'track_coupon_use');
function track_coupon_use() {
    global $wpdb;
    $id = intval($_POST['id']);
    $table = $wpdb->prefix . 'exclusive_coupons';
    $wpdb->query($wpdb->prepare("UPDATE $table SET uses = uses + 1 WHERE id = %d", $id));
    wp_die();
}

// Create style.css and script.js placeholders (in real plugin, include files)
// style.css content:
/*
.exclusive-coupon { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; }
.coupon-code { font-size: 24px; font-weight: bold; color: #0073aa; background: #fff; padding: 10px; }
.coupon-btn { background: #0073aa; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; }
.coupon-expired, .coupon-maxed { background: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; }
*/

// script.js content:
/*
jQuery(document).ready(function($) {
    $('.coupon-btn').click(function() {
        var $coupon = $(this).closest('.exclusive-coupon');
        $.post(ajaxurl, {action: 'track_coupon_use', id: $coupon.data('id')});
    });
});
*/