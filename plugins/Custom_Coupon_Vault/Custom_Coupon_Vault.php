/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Vault
 * Plugin URI: https://example.com/custom-coupon-vault
 * Description: Generate, manage, and display exclusive custom coupons to boost affiliate conversions and reader loyalty.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class CustomCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('coupon_vault', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_post_save_coupon', array($this, 'save_coupon'));
        }
    }

    public function admin_menu() {
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            $this->save_coupon();
        }
        $coupons = get_option('ccv_coupons', array());
        ?>
        <div class="wrap">
            <h1>Custom Coupon Vault</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_coupon">
                <?php wp_nonce_field('ccv_save_coupon'); ?>
                <table class="form-table">
                    <tr>
                        <th>Brand</th>
                        <td><input type="text" name="brand" required></td>
                    </tr>
                    <tr>
                        <th>Code</th>
                        <td><input type="text" name="code" required></td>
                    </tr>
                    <tr>
                        <th>Discount</th>
                        <td><input type="text" name="discount" required></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="link" placeholder="https://"></td>
                    </tr>
                    <tr>
                        <th>Expiry Date</th>
                        <td><input type="date" name="expiry"></td>
                    </tr>
                </table>
                <p><input type="submit" name="save_coupon" class="button-primary" value="Add Coupon"></p>
            </form>
            <h2>Your Coupons</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Brand</th><th>Code</th><th>Discount</th><th>Link</th><th>Expiry</th><th>Shortcode</th></tr></thead>
                <tbody>
        <?php foreach ($coupons as $id => $coupon): ?>
                    <tr>
                        <td><?php echo esc_html($coupon['brand']); ?></td>
                        <td><?php echo esc_html($coupon['code']); ?></td>
                        <td><?php echo esc_html($coupon['discount']); ?></td>
                        <td><?php echo esc_html($coupon['link']); ?></td>
                        <td><?php echo esc_html($coupon['expiry']); ?></td>
                        <td>[coupon_vault id="<?php echo $id; ?>"]</td>
                    </tr>
        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function save_coupon() {
        if (!wp_verify_nonce($_POST['ccv_save_coupon'], 'ccv_save_coupon') || !current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $coupons = get_option('ccv_coupons', array());
        $id = uniqid();
        $coupons[$id] = array(
            'brand' => sanitize_text_field($_POST['brand']),
            'code' => sanitize_text_field($_POST['code']),
            'discount' => sanitize_text_field($_POST['discount']),
            'link' => esc_url_raw($_POST['link']),
            'expiry' => sanitize_text_field($_POST['expiry'])
        );
        update_option('ccv_coupons', $coupons);
        wp_redirect(admin_url('admin.php?page=coupon-vault'));
        exit;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('ccv_coupons', array());
        if (!isset($coupons[$atts['id']])) return '';
        $coupon = $coupons[$atts['id']];
        $expired = !empty($coupon['expiry']) && strtotime($coupon['expiry']) < current_time('timestamp');
        ob_start();
        ?>
        <div class="ccv-coupon" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9;">
            <h3><?php echo esc_html($coupon['brand']); ?> Exclusive Deal!</h3>
            <?php if ($expired): ?>
                <p style="color: red;">Coupon Expired</p>
            <?php else: ?>
                <p><strong>Code:</strong> <code style="background: #fff; padding: 5px 10px; font-size: 1.2em;"><?php echo esc_html($coupon['code']); ?></code></p>
                <p><strong>Discount:</strong> <?php echo esc_html($coupon['discount']); ?></p>
                <?php if ($coupon['link']): ?>
                    <p><a href="<?php echo esc_url($coupon['link']); ?>" target="_blank" class="button" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none;">Shop Now & Save</a></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ccv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function activate() {
        if (!get_option('ccv_coupons')) {
            update_option('ccv_coupons', array());
        }
    }
}

new CustomCouponVault();

// Premium upsell notice
function ccv_premium_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Custom Coupon Vault Pro</strong> for unlimited coupons, click tracking, and analytics! <a href="https://example.com/pro" target="_blank">Learn More</a></p></div>';
    }
}
add_action('admin_notices', 'ccv_premium_notice');

// Create style.css placeholder
if (!file_exists(plugin_dir_path(__FILE__) . 'style.css')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'style.css', '/* Custom Coupon Vault Styles */');
}