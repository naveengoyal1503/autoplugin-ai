/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Affiliate Pro
 * Plugin URI: https://example.com/custom-coupon-affiliate-pro
 * Description: Create, manage, and track custom affiliate coupons to boost conversions and monetize your site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CustomCouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('coupon_display', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_post_save_coupon', array($this, 'save_coupon'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('coupon-script', plugin_dir_url(__FILE__) . 'coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('coupon-style', plugin_dir_url(__FILE__) . 'coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_menu_page('Coupons', 'Coupons', 'manage_options', 'custom-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            $this->save_coupon();
        }
        $coupons = get_option('custom_coupons', array());
        ?>
        <div class="wrap">
            <h1>Manage Custom Coupons</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_coupon">
                <?php wp_nonce_field('save_coupon_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>Code</th>
                        <td><input type="text" name="coupon_code" value="" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="affiliate_link" value="" size="50" /></td>
                    </tr>
                    <tr>
                        <th>Discount</th>
                        <td><input type="text" name="discount" value="" placeholder="e.g., 20% OFF" /></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><textarea name="description" rows="4" cols="50"></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Add Coupon'); ?>
            </form>
            <h2>Existing Coupons</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Code</th><th>Discount</th><th>Link</th><th>Shortcode</th><th>Uses</th></tr></thead>
                <tbody>
        <?php foreach ($coupons as $id => $coupon): ?>
                    <tr>
                        <td><?php echo esc_html($coupon['code']); ?></td>
                        <td><?php echo esc_html($coupon['discount']); ?></td>
                        <td><a href="<?php echo esc_url($coupon['link']); ?>" target="_blank">View</a></td>
                        <td>[coupon_display id="<?php echo $id; ?>"]</td>
                        <td><?php echo isset($coupon['uses']) ? $coupon['uses'] : 0; ?></td>
                    </tr>
        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function save_coupon() {
        if (!wp_verify_nonce($_POST['save_coupon_nonce'], 'save_coupon_nonce') || !current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $coupons = get_option('custom_coupons', array());
        $id = uniqid();
        $coupons[$id] = array(
            'code' => sanitize_text_field($_POST['coupon_code']),
            'link' => esc_url_raw($_POST['affiliate_link']),
            'discount' => sanitize_text_field($_POST['discount']),
            'description' => sanitize_textarea_field($_POST['description']),
            'uses' => 0
        );
        update_option('custom_coupons', $coupons);
        wp_redirect(admin_url('admin.php?page=custom-coupons'));
        exit;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('custom_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return '';
        }
        $coupon = $coupons[$atts['id']];
        $coupon['uses'] = isset($coupon['uses']) ? $coupon['uses'] + 1 : 1;
        $coupons[$atts['id']]['uses'] = $coupon['uses'];
        update_option('custom_coupons', $coupons);
        ob_start();
        ?>
        <div class="coupon-box">
            <h3><?php echo esc_html($coupon['discount']); ?></h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank">Get Deal</a>
            <small>Used <?php echo $coupon['uses']; ?> times</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('custom_coupons', array());
    }
}

new CustomCouponAffiliatePro();

// Inline styles and scripts for self-contained
add_action('wp_head', function() { ?>
<style>
.coupon-box { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; border-radius: 8px; }
.coupon-code { font-size: 24px; font-weight: bold; color: #e74c3c; background: white; padding: 10px; margin: 10px 0; display: inline-block; letter-spacing: 3px; }
.coupon-btn { display: inline-block; background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px; }
.coupon-btn:hover { background: #005a87; }
</style>
<script>jQuery(document).ready(function($) { $('.coupon-code').click(function() { var code = $(this).text(); navigator.clipboard.writeText(code).then(function() { $(this).after('<span>Copied!</span>'); }); }); });</script>
<?php });