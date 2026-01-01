/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Vault
 * Plugin URI: https://example.com/custom-coupon-vault
 * Description: Generate and manage exclusive custom coupons for your WordPress site, boosting affiliate conversions with personalized discount codes and tracking.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('coupon_vault', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url text,
            discount varchar(50),
            expiry_date datetime DEFAULT NULL,
            usage_limit int DEFAULT 0,
            used_count int DEFAULT 0,
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
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_coupons';
        if (isset($_POST['add_coupon'])) {
            $wpdb->insert($table_name, array(
                'title' => sanitize_text_field($_POST['title']),
                'code' => strtoupper(sanitize_text_field($_POST['code'])),
                'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                'discount' => sanitize_text_field($_POST['discount']),
                'expiry_date' => $_POST['expiry_date'],
                'usage_limit' => intval($_POST['usage_limit']),
            ));
        }
        if (isset($_GET['delete'])) {
            $wpdb->delete($table_name, array('id' => intval($_GET['delete'])));
        }
        $coupons = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        include $this->get_template('admin-page');
    }

    private function get_template($name) {
        $path = plugin_dir_path(__FILE__) . 'templates/' . $name . '.php';
        ob_start();
        if (file_exists($path)) {
            include $path;
        }
        return ob_get_clean();
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND is_active = 1", $atts['id']));
        if (!$coupon) return '';
        $now = current_time('mysql');
        if ($coupon->expiry_date && $now > $coupon->expiry_date) return '<p>Coupon expired.</p>';
        if ($coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit) return '<p>Coupon usage limit reached.</p>';
        return $this->get_coupon_display($coupon);
    }

    private function get_coupon_display($coupon) {
        ob_start();
        ?>
        <div style="border: 2px dashed #0073aa; padding: 20px; background: #f9f9f9; text-align: center;">
            <h3><?php echo esc_html($coupon->title); ?></h3>
            <div style="font-size: 2em; color: #0073aa; margin: 10px 0;"><?php echo esc_html($coupon->code); ?></div>
            <?php if ($coupon->discount): ?>
                <p><strong>Discount:</strong> <?php echo esc_html($coupon->discount); ?></p>
            <?php endif; ?>
            <?php if ($coupon->affiliate_url): ?>
                <a href="<?php echo esc_url($coupon->affiliate_url); ?>" target="_blank" class="button button-primary" style="padding: 10px 20px;">Redeem Now</a>
            <?php endif; ?>
            <p style="font-size: 0.9em; margin-top: 10px;">Used: <?php echo intval($coupon->used_count); ?>/<?php echo $coupon->usage_limit ?: 'Unlimited'; ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('coupon-vault', plugin_dir_url(__FILE__) . 'coupon-vault.css');
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook != 'toplevel_page_coupon-vault') return;
        wp_enqueue_style('coupon-vault-admin', plugin_dir_url(__FILE__) . 'admin.css');
    }
}

new CustomCouponVault();

// Pro upsell notice
function custom_coupon_vault_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>Custom Coupon Vault Pro</strong>: Unlimited coupons, analytics, auto-expiration & more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'custom_coupon_vault_notice');

// Create directories for templates and CSS on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = plugin_dir_path(__FILE__);
    @mkdir($upload_dir . 'templates', 0755, true);
    file_put_contents($upload_dir . 'templates/admin-page.php', '<?php
<h2>Custom Coupon Vault</h2>
<form method="post">
    <table class="form-table">
        <tr><th>Title</th><td><input type="text" name="title" required /></td></tr>
        <tr><th>Code</th><td><input type="text" name="code" required /></td></tr>
        <tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" /></td></tr>
        <tr><th>Discount</th><td><input type="text" name="discount" placeholder="e.g. 20% OFF" /></td></tr>
        <tr><th>Expiry Date</th><td><input type="datetime-local" name="expiry_date" /></td></tr>
        <tr><th>Usage Limit</th><td><input type="number" name="usage_limit" min="0" /></td></tr>
    </table>
    <p><input type="submit" name="add_coupon" class="button-primary" value="Add Coupon" /></p>
</form>
<table class="wp-list-table widefat fixed striped">
    <thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Discount</th><th>Used</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($coupons as $c): ?>
    <tr>
        <td><?php echo $c->id; ?></td>
        <td><?php echo esc_html($c->title); ?></td>
        <td><?php echo esc_html($c->code); ?></td>
        <td><?php echo esc_html($c->discount); ?></td>
        <td><?php echo $c->used_count; ?>/<?php echo $c->usage_limit ?: 'Unlimited'; ?></td>
        <td><a href="?page=coupon-vault&delete=<?php echo $c->id; ?>" onclick="return confirm('Delete?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>');
    file_put_contents(plugin_dir_path(__FILE__) . 'coupon-vault.css', '.coupon-vault { /* styles */ }');
    file_put_contents(plugin_dir_path(__FILE__) . 'admin.css', '.form-table { /* styles */ }');
});