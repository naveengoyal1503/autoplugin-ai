/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Partner_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Partner Pro
 * Plugin URI: https://example.com/custom-coupon-partner-pro
 * Description: Generate and manage exclusive custom coupons for affiliate partnerships, boosting conversions and revenue with personalized discount codes and tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: custom-coupon-partner-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CustomCouponPartnerPro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ccpp_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_post_ccpp_save_coupon', array($this, 'save_coupon'));
        }
        load_plugin_textdomain('custom-coupon-partner-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ccpp-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ccpp-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_menu_page(
            'Coupon Partner Pro',
            'Coupons Pro',
            'manage_options',
            'ccpp-coupons',
            array($this, 'admin_page'),
            'dashicons-cart',
            30
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $coupons = get_option('ccpp_coupons', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Custom Coupon Partner Pro', 'custom-coupon-partner-pro'); ?></h1>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics & integrations for $49/year!</p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="ccpp_save_coupon">
                <?php wp_nonce_field('ccpp_save_coupon'); ?>
                <table class="form-table">
                    <tr>
                        <th>Brand</th>
                        <td><input type="text" name="brand" required /></td>
                    </tr>
                    <tr>
                        <th>Code</th>
                        <td><input type="text" name="code" required /></td>
                    </tr>
                    <tr>
                        <th>Discount</th>
                        <td><input type="text" name="discount" required /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="link" style="width: 300px;" /></td>
                    </tr>
                    <tr>
                        <th>Expiry</th>
                        <td><input type="date" name="expiry" /></td>
                    </tr>
                </table>
                <?php submit_button('Add Coupon'); ?>
            </form>
            <h2>Existing Coupons (Free: Max 5)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr><th>Brand</th><th>Code</th><th>Discount</th><th>Link</th><th>Shortcode</th><th>Uses</th></tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($coupons, 0, 5) as $i => $coupon): ?>
                    <tr>
                        <td><?php echo esc_html($coupon['brand']); ?></td>
                        <td><?php echo esc_html($coupon['code']); ?></td>
                        <td><?php echo esc_html($coupon['discount']); ?></td>
                        <td><a href="<?php echo esc_url($coupon['link']); ?>" target="_blank">View</a></td>
                        <td><code>[ccpp_coupon id="<?php echo $i; ?>"]</code></td>
                        <td><?php echo isset($coupon['uses']) ? $coupon['uses'] : 0; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <style>
            .wrap { max-width: 800px; }
        </style>
        <script>
            jQuery(document).ready(function($) {
                $('form').on('submit', function() {
                    alert('Coupon added! Use shortcode on your site. Pro unlocks more!');
                });
            });
        </script>
        <?php
    }

    public function save_coupon() {
        if (!wp_verify_nonce($_POST['ccpp_save_coupon'], 'ccpp_save_coupon') || !current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $coupons = get_option('ccpp_coupons', array());
        $coupon = array(
            'brand' => sanitize_text_field($_POST['brand']),
            'code' => sanitize_text_field($_POST['code']),
            'discount' => sanitize_text_field($_POST['discount']),
            'link' => esc_url_raw($_POST['link']),
            'expiry' => sanitize_text_field($_POST['expiry']),
            'uses' => isset($coupons['uses']) ? $coupons['uses'] : 0
        );
        array_unshift($coupons, $coupon);
        // Limit free to 5
        update_option('ccpp_coupons', array_slice($coupons, 0, 5));
        wp_redirect(admin_url('admin.php?page=ccpp-coupons'));
        exit;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('ccpp_coupons', array());
        $id = intval($atts['id']);
        if (!isset($coupons[$id])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$id];
        $uses = isset($coupon['uses']) ? $coupon['uses'] + 1 : 1;
        $coupon['uses'] = $uses;
        $coupons[$id] = $coupon;
        update_option('ccpp_coupons', $coupons);

        $style = 'background: linear-gradient(45deg, #ff6b6b, #feca57); color: white; padding: 15px; border-radius: 10px; text-align: center; font-weight: bold; margin: 20px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.2);';
        $expiry = !empty($coupon['expiry']) && strtotime($coupon['expiry']) < time() ? '<br><small style="color: #ff4444;">Expired!</small>' : '';

        return '<div style="' . $style . '">
                    <h3>Exclusive Deal: ' . esc_html($coupon['brand']) . '</h3>
                    <div style="font-size: 2em; margin: 10px 0;">Code: <strong>' . esc_html($coupon['code']) . '</strong></div>
                    Save <strong>' . esc_html($coupon['discount']) . '</strong>!' . $expiry . '
                    <br><a href="' . esc_url($coupon['link']) . '" target="_blank" style="background: white; color: #333; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;">Shop Now & Save</a>
                    <p style="font-size: 0.8em; margin-top: 10px;">Used ' . $uses . ' times</p>
                </div>';
    }

    public function activate() {
        if (!get_option('ccpp_coupons')) {
            update_option('ccpp_coupons', array());
        }
    }
}

new CustomCouponPartnerPro();

// Pro upsell notice
function ccpp_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Custom Coupon Partner Pro:</strong> Upgrade to Pro for unlimited coupons, detailed analytics, and auto-expiry! <a href="https://example.com/pro" target="_blank">Get Pro ($49)</a></p></div>';
}
add_action('admin_notices', 'ccpp_pro_notice');

// Frontend assets placeholder (create empty folders: assets/frontend.js, assets/frontend.css)
// Note: In production, add minimal JS/CSS files.