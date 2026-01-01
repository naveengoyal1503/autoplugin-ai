/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and analytics to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('ECP_VERSION', '1.0.0');
define('ECP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ECP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Pro check (simulate license)
function ecp_is_pro() {
    return false; // Set to true for pro features in paid version
}

// Admin menu
add_action('admin_menu', 'ecp_admin_menu');
function ecp_admin_menu() {
    add_menu_page(
        'Exclusive Coupons',
        'Coupons Pro',
        'manage_options',
        'exclusive-coupons-pro',
        'ecp_admin_page',
        'dashicons-cart',
        30
    );
}

function ecp_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php _e('Exclusive Coupons Pro', 'exclusive-coupons-pro'); ?></h1>
        <?php
        if (isset($_POST['ecp_save_coupon'])) {
            ecp_save_coupon();
        }
        $coupons = get_option('ecp_coupons', array());
        ?>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Code</th>
                    <td><input type="text" name="code" required /></td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td><textarea name="description"></textarea></td>
                </tr>
                <tr>
                    <th>Affiliate Link</th>
                    <td><input type="url" name="link" required style="width: 100%;" /></td>
                </tr>
                <tr>
                    <th>Discount (%)</th>
                    <td><input type="number" name="discount" /></td>
                </tr>
                <?php if (ecp_is_pro()): ?>
                <tr>
                    <th>Expiration (days)</th>
                    <td><input type="number" name="expires_days" value="30" /></td>
                </tr>
                <?php endif; ?>
            </table>
            <p><input type="submit" name="ecp_save_coupon" class="button-primary" value="Add Coupon" /></p>
        </form>
        <h2>Your Coupons</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr><th>Code</th><th>Description</th><th>Uses</th><th>Shortcode</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $id => $coupon): ?>
                <tr>
                    <td><?php echo esc_html($coupon['code']); ?></td>
                    <td><?php echo esc_html($coupon['description']); ?></td>
                    <td><?php echo isset($coupon['uses']) ? $coupon['uses'] : 0; ?></td>
                    <td>[ecp_coupon id="<?php echo $id; ?>"]</td>
                    <td>
                        <a href="#" onclick="ecpDelete(<?php echo $id; ?>)">Delete</a>
                        <?php if (ecp_is_pro()): ?>
                        <a href="#analytics-<?php echo $id; ?>">Analytics</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <script>
        function ecpDelete(id) {
            if (confirm('Delete?')) {
                window.location = '?page=exclusive-coupons-pro&delete=' + id;
            }
        }
        </script>
        <?php
        if (isset($_GET['delete'])) {
            $coupons = get_option('ecp_coupons', array());
            unset($coupons[$_GET['delete']]);
            update_option('ecp_coupons', $coupons);
            wp_redirect(admin_url('admin.php?page=exclusive-coupons-pro'));
            exit;
        }
        ?>
    </div>
    <?php
}

function ecp_save_coupon() {
    $coupons = get_option('ecp_coupons', array());
    $id = time();
    $coupons[$id] = array(
        'code' => sanitize_text_field($_POST['code']),
        'description' => sanitize_textarea_field($_POST['description']),
        'link' => esc_url_raw($_POST['link']),
        'discount' => intval($_POST['discount']),
        'uses' => 0,
        'created' => current_time('mysql')
    );
    if (ecp_is_pro() && isset($_POST['expires_days'])) {
        $coupons[$id]['expires'] = strtotime('+' . intval($_POST['expires_days']) . ' days');
    }
    update_option('ecp_coupons', $coupons);
}

// Shortcode
add_shortcode('ecp_coupon', 'ecp_coupon_shortcode');
function ecp_coupon_shortcode($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts);
    $coupons = get_option('ecp_coupons', array());
    if (!isset($coupons[$atts['id']])) {
        return '';
    }
    $coupon = $coupons[$atts['id']];
    $expired = ecp_is_pro() && isset($coupon['expires']) && $coupon['expires'] < time();
    ob_start();
    ?>
    <div class="ecp-coupon" style="border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9;">
        <?php if ($expired): ?>
            <p style="color: red;">Coupon Expired!</p>
        <?php else: ?>
            <h3>Exclusive Deal: <strong><?php echo esc_html($coupon['code']); ?></strong></h3>
            <p><?php echo esc_html($coupon['description']); ?><?php echo $coupon['discount'] ? ' (' . $coupon['discount'] . '% OFF)' : ''; ?></p>
            <a href="<?php echo esc_url(add_query_arg('ecp', $atts['id'], $coupon['link'])); ?>" class="button" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none;" target="_blank">Redeem Now</a>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Track clicks
add_action('init', 'ecp_track_click');
function ecp_track_click() {
    if (isset($_GET['ecp'])) {
        $id = intval($_GET['ecp']);
        $coupons = get_option('ecp_coupons', array());
        if (isset($coupons[$id])) {
            $coupons[$id]['uses'] = intval($coupons[$id]['uses']) + 1;
            update_option('ecp_coupons', $coupons);
        }
    }
}

// Pro upsell notice
if (!ecp_is_pro()) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-info"><p>Unlock <strong>Pro features</strong> like expiration timers, analytics, and unlimited coupons for $49/year! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    });
}

// Enqueue styles
add_action('wp_enqueue_scripts', 'ecp_styles');
add_action('admin_enqueue_scripts', 'ecp_styles');
function ecp_styles() {
    wp_enqueue_style('ecp-style', ECP_PLUGIN_URL . 'style.css', array(), ECP_VERSION);
}

// Create style.css placeholder
add_action('init', function() {
    $css_dir = ECP_PLUGIN_DIR . 'css/';
    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
    }
    if (!file_exists($css_dir . 'style.css')) {
        file_put_contents($css_dir . 'style.css', '/* Exclusive Coupons Pro Styles */');
    }
});