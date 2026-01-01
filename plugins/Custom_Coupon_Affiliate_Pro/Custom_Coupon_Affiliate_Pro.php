/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Affiliate Pro
 * Plugin URI: https://example.com/coupon-pro
 * Description: Generate custom affiliate coupons with tracking, expiration, and easy sharing for monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class CustomCouponAffiliatePro {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('coupon_display', [$this, 'coupon_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        wp_register_style('ccap-style', plugin_dir_url(__FILE__) . 'style.css', [], '1.0');
        wp_register_script('ccap-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], '1.0', true);
    }

    public function admin_menu() {
        add_menu_page('Coupons', 'Coupons', 'manage_options', 'custom-coupons', [$this, 'admin_page']);
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            $this->save_coupon($_POST);
        }
        $coupons = get_option('ccap_coupons', []);
        include plugin_dir_path(__FILE__) . 'admin.html';
    }

    private function save_coupon($data) {
        $coupons = get_option('ccap_coupons', []);
        $id = uniqid();
        $coupons[$id] = [
            'title' => sanitize_text_field($data['title']),
            'code' => sanitize_text_field($data['code']),
            'affiliate_link' => esc_url_raw($data['affiliate_link']),
            'discount' => sanitize_text_field($data['discount']),
            'expires' => sanitize_text_field($data['expires']),
            'id' => $id
        ];
        update_option('ccap_coupons', $coupons);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(['id' => ''], $atts);
        $coupons = get_option('ccap_coupons', []);
        if (!isset($coupons[$atts['id']])) return '';
        $coupon = $coupons[$atts['id']];
        $expired = !empty($coupon['expires']) && strtotime($coupon['expires']) < current_time('timestamp');
        ob_start();
        ?>
        <div class="ccap-coupon <?php echo $expired ? 'expired' : ''; ?>">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p><strong>Code:</strong> <span class="coupon-code"><?php echo esc_html($coupon['code']); ?></span></p>
            <p><strong>Discount:</strong> <?php echo esc_html($coupon['discount']); ?>%</p>
            <?php if (!$expired): ?>
            <a href="<?php echo esc_url(add_query_arg('ref', 'youraffiliateid', $coupon['affiliate_link'])); ?>" class="coupon-btn" target="_blank">Get Deal</a>
            <?php else: ?>
            <p>Expired</p>
            <?php endif; ?>
        </div>
        <?php
        wp_enqueue_style('ccap-style');
        wp_enqueue_script('ccap-script');
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ccap_coupons')) {
            update_option('ccap_coupons', []);
        }
    }
}

new CustomCouponAffiliatePro();

/* Pro Teaser */
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && !get_option('ccap_pro_activated')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Custom Coupon Affiliate Pro</strong> for unlimited coupons and analytics! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
});

// Create assets directories
add_action('init', function() {
    $css = plugin_dir_path(__FILE__) . 'style.css';
    if (!file_exists($css)) {
        file_put_contents($css, '.ccap-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 10px 0; text-align: center; background: #f9f9f9; }.coupon-code { font-size: 24px; color: #e74c3c; font-weight: bold; }.coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }.expired { opacity: 0.5; }');
    }
    $js = plugin_dir_path(__FILE__) . 'script.js';
    if (!file_exists($js)) {
        file_put_contents($js, 'jQuery(document).ready(function($) { $(".coupon-code").click(function() { $(this).select(); }); $(".coupon-btn").click(function(e) { /* Track click */ console.log("Coupon clicked"); }); });');
    }
});

// admin.html placeholder
$admin_html = plugin_dir_path(__FILE__) . 'admin.html';
if (!file_exists($admin_html)) {
    file_put_contents($admin_html, '<div class="wrap"><h1>Manage Coupons</h1><form method="post"><table class="form-table"><tr><th>Title</th><td><input type="text" name="title" required></td></tr><tr><th>Code</th><td><input type="text" name="code" required></td></tr><tr><th>Affiliate Link</th><td><input type="url" name="affiliate_link" required style="width:100%"></td></tr><tr><th>Discount %</th><td><input type="number" name="discount" required></td></tr><tr><th>Expires (YYYY-MM-DD)</th><td><input type="date" name="expires"></td></tr></table><p><input type="submit" name="save_coupon" class="button-primary" value="Add Coupon"></p></form><h2>Your Coupons</h2><ul><?php foreach(get_option("ccap_coupons", []) as $c): ?><li><?php echo esc_html($c["title"]); ?> - [coupon_display id="<?php echo $c["id"]; ?>"] <a href="#" onclick="return confirm('Delete?')">Delete</a></li><?php endforeach; ?></ul></div>');
}