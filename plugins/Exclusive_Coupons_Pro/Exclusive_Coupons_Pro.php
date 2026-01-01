/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons for your WordPress site, boosting conversions with personalized discount codes and tracking.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ecp_version') !== '1.0.0') {
            update_option('ecp_version', '1.0.0');
            $this->create_table();
        }
    }

    public function activate() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url text NOT NULL,
            discount varchar(50) DEFAULT '',
            brand varchar(255) DEFAULT '',
            expiry date DEFAULT NULL,
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            clicks int DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'ecp-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['add_coupon'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'exclusive_coupons';
            $code = sanitize_text_field($_POST['code']);
            $wpdb->insert($table_name, array(
                'title' => sanitize_text_field($_POST['title']),
                'code' => $code,
                'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                'discount' => sanitize_text_field($_POST['discount']),
                'brand' => sanitize_text_field($_POST['brand']),
                'expiry' => sanitize_text_field($_POST['expiry']),
                'max_uses' => intval($_POST['max_uses'])
            ));
            echo '<div class="notice notice-success"><p>Coupon added!</p></div>';
        }

        $coupons = $this->get_coupons();
        include plugin_dir_path(__FILE__) . 'admin-template.php';
    }

    private function get_coupons() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . 'exclusive_coupons' . " ORDER BY created_at DESC");
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupon = $this->get_coupon_by_id($atts['id']);
        if (!$coupon || !$coupon->is_active) return '';

        $clicks = $this->track_click($coupon->id);
        $uses_left = $coupon->max_uses > 0 ? max(0, $coupon->max_uses - $coupon->uses) : 'Unlimited';

        ob_start();
        ?>
        <div class="ecp-coupon" data-id="<?php echo $coupon->id; ?>">
            <h3><?php echo esc_html($coupon->title); ?></h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($coupon->code); ?></code></p>
            <p><strong>Discount:</strong> <?php echo esc_html($coupon->discount); ?></p>
            <p><strong>Brand:</strong> <?php echo esc_html($coupon->brand); ?></p>
            <?php if ($coupon->expiry): ?>
            <p><strong>Expires:</strong> <?php echo date('Y-m-d', strtotime($coupon->expiry)); ?></p>
            <?php endif; ?>
            <p><strong>Uses left:</strong> <?php echo $uses_left; ?></p>
            <p><strong>Clicks:</strong> <?php echo $coupon->clicks; ?></p>
            <a href="<?php echo esc_url(add_query_arg('ecp_code', $coupon->code, $coupon->affiliate_url)); ?>" class="ecp-button" target="_blank">Get Deal & Use Code</a>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_coupon_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . 'exclusive_coupons' . " WHERE id = %d", $id));
    }

    private function track_click($id) {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . 'exclusive_coupons' . " SET clicks = clicks + 1 WHERE id = %d", $id));
        $coupon = $this->get_coupon_by_id($id);
        if ($coupon->max_uses > 0 && $coupon->uses >= $coupon->max_uses) {
            $wpdb->update($wpdb->prefix . 'exclusive_coupons', array('is_active' => 0), array('id' => $id));
        }
        return $coupon->clicks + 1;
    }
}

new ExclusiveCouponsPro();

// Premium upsell notice
function ecp_premium_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Upgrade to premium for unlimited coupons, analytics dashboard, and API integrations! <a href="https://example.com/premium" target="_blank">Learn more</a></p></div>';
    }
}
add_action('admin_notices', 'ecp_premium_notice');

// Minimal JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.ecp-button').on('click', function() {
        ga('send', 'event', 'Coupon', 'Click', $(this).data('id'));
    });
});
</script>
<?php });

// Basic CSS
add_action('wp_head', function() { ?>
<style>
.ecp-coupon { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; }
.ecp-coupon code { background: #fff; padding: 5px 10px; border-radius: 4px; font-weight: bold; }
.ecp-button { display: inline-block; background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
.ecp-button:hover { background: #005a87; }
</style>
<?php });

// Admin template placeholder
if (!file_exists(plugin_dir_path(__FILE__) . 'admin-template.php')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'admin-template.php', '<?php
<h1>Exclusive Coupons Pro</h1>
<form method="post">
    <table class="form-table">
        <tr><th>Title</th><td><input type="text" name="title" required /></td></tr>
        <tr><th>Code</th><td><input type="text" name="code" required /></td></tr>
        <tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width:100%;" required /></td></tr>
        <tr><th>Discount</th><td><input type="text" name="discount" placeholder="50% OFF" /></td></tr>
        <tr><th>Brand</th><td><input type="text" name="brand" /></td></tr>
        <tr><th>Expiry</th><td><input type="date" name="expiry" /></td></tr>
        <tr><th>Max Uses</th><td><input type="number" name="max_uses" value="0" /></td></tr>
    </table>
    <p><input type="submit" name="add_coupon" class="button-primary" value="Add Coupon" /></p>
</form>
<h2>Your Coupons</h2>
<table class="wp-list-table widefat fixed striped">
    <thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Clicks</th><th>Active</th></tr></thead>
    <tbody>
    <?php foreach($coupons as $c): ?>
    <tr><td><?php echo $c->id; ?></td><td><?php echo esc_html($c->title); ?></td><td><?php echo esc_html($c->code); ?></td><td><?php echo $c->clicks; ?></td><td><?php echo $c->is_active ? 'Yes' : 'No'; ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
');
}
