/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Create and display exclusive coupons to monetize your WordPress site with affiliate deals and custom discounts.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_delete_coupon', array($this, 'ajax_delete_coupon'));
        add_shortcode('exclusive_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('ecp-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_register_script('ecp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ecp-script', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function enqueue_scripts() {
        if (is_page() || is_single()) {
            wp_enqueue_style('ecp-style');
            wp_enqueue_script('ecp-script');
        }
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            $this->save_coupon($_POST);
        }
        $coupons = get_option('ecp_coupons', array());
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Brand</th>
                        <td><input type="text" name="brand" required /></td>
                    </tr>
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="code" required /></td>
                    </tr>
                    <tr>
                        <th>Discount</th>
                        <td><input type="text" name="discount" required /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="link" style="width: 400px;" /></td>
                    </tr>
                    <tr>
                        <th>Expiration Date</th>
                        <td><input type="date" name="expires" /></td>
                    </tr>
                </table>
                <p><input type="submit" name="save_coupon" class="button-primary" value="Add Coupon" /></p>
            </form>
            <h2>Active Coupons</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Brand</th><th>Code</th><th>Discount</th><th>Link</th><th>Expires</th><th>Action</th></tr></thead>
                <tbody>
        <?php foreach ($coupons as $id => $coupon): ?>
                    <tr>
                        <td><?php echo esc_html($coupon['brand']); ?></td>
                        <td><?php echo esc_html($coupon['code']); ?></td>
                        <td><?php echo esc_html($coupon['discount']); ?></td>
                        <td><a href="<?php echo esc_url($coupon['link']); ?>" target="_blank">View</a></td>
                        <td><?php echo esc_html($coupon['expires']); ?></td>
                        <td><button class="button button-delete" onclick="deleteCoupon(<?php echo $id; ?>)" data-id="<?php echo $id; ?>">Delete</button></td>
                    </tr>
        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
        function deleteCoupon(id) {
            if (confirm('Delete this coupon?')) {
                jQuery.post(ecp_ajax.ajax_url, {action: 'delete_coupon', id: id}, function() {
                    location.reload();
                });
            }
        }
        </script>
        <?php
    }

    public function ajax_save_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        $coupons = get_option('ecp_coupons', array());
        $id = time();
        $coupons[$id] = array(
            'brand' => sanitize_text_field($_POST['brand']),
            'code' => sanitize_text_field($_POST['code']),
            'discount' => sanitize_text_field($_POST['discount']),
            'link' => esc_url_raw($_POST['link']),
            'expires' => sanitize_text_field($_POST['expires'])
        );
        update_option('ecp_coupons', $coupons);
        wp_send_json_success();
    }

    public function ajax_delete_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        $coupons = get_option('ecp_coupons', array());
        $id = intval($_POST['id']);
        unset($coupons[$id]);
        update_option('ecp_coupons', $coupons);
        wp_send_json_success();
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = get_option('ecp_coupons', array());
        $active_coupons = array_filter($coupons, function($c) {
            return empty($c['expires']) || $c['expires'] > date('Y-m-d');
        });
        $display = array_slice($active_coupons, 0, $atts['limit']);
        ob_start();
        ?>
        <div class="ecp-coupons">
            <h3>Exclusive Deals</h3>
            <div class="coupons-grid">
        <?php foreach ($display as $coupon): 
            $expiry = !empty($coupon['expires']) ? 'Expires: ' . $coupon['expires'] : 'No expiry';
        ?>
                <div class="coupon-card">
                    <h4><?php echo esc_html($coupon['brand']); ?></h4>
                    <p><strong>Code:</strong> <code><?php echo esc_html($coupon['code']); ?></code></p>
                    <p><strong><?php echo esc_html($coupon['discount']); ?></strong></p>
                    <p><small><?php echo esc_html($expiry); ?></small></p>
                    <a href="<?php echo esc_url($coupon['link']); ?>" class="button coupon-btn" target="_blank">Grab Deal</a>
                </div>
        <?php endforeach; ?>
            </div>
        </div>
        <style>
        .ecp-coupons { max-width: 800px; margin: 20px 0; }
        .coupons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; }
        .coupon-card h4 { margin: 0 0 10px; color: #333; }
        .coupon-card code { background: #ffeb3b; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .coupon-btn { display: inline-block; background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        .coupon-btn:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            add_option('ecp_coupons', array());
        }
    }
}

new ExclusiveCouponsPro();

// Premium teaser
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && !defined('ECP_PREMIUM')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Exclusive Coupons Pro Premium</strong> for analytics, unlimited coupons, and auto-expiration! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
    }
});